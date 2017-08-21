<?php

namespace App\Controllers\Api;

use Swift_Validate;
use App\Models\User;
use App\Models\NewsletterSubscription;


/**
 * @RoutePrefix("/api/auth")
 */
class AuthController extends ApiController
{

    /**
     * @return void
     */
    public function onConstruct()
    {
        parent::initialize();
    }

    // --------------------------------------------------------------

    /**
     * @return string JSON
     */
    public function loginAction()
    {
        if (!$this->component->helper->csrf(false, true)) {
            return $this->output(0, 'Invalid CSRF');
        }

        // POST Data
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        // Cannot have Empty Fields
        if (!$email || !$password) {
            return $this->output(0, 'email and password field(s) are required.');
        }

        // Find the user based on the email
        $user = User::findFirstByEmail($email);
        if ($user) {
            if ($user->is_deleted == 1) {
                return $this->output(0, 'This user has been permanently removed.');
            }

            // Prevent Spam logins
            if ($user->login_attempt >= 5) {
                if (strtotime('now') < strtotime($user->login_attempt_at) + 600) {
                    return $this->output(0, 'Too many login attempts. Timed out for 10 minutes.');
                }

                // Clear the login attempts if time has expired
                $user->login_attempt = null;
                $user->login_attempt_at = null;
                $user->save();
            }

            if ($this->security->checkHash($password, $user->password)) {
                if ($user->isBanned()) {
                    return $this->output(0, 'Sorry, your account has been locked due to suspicious activity.
                                For support, contact <strong>hello@jream.com</strong>.');
                }

                // $this->createSession($user, [], $remember_me);
                $this->createSession($user);
                return $this->output(1, 'Login Success.');
            }

            // Track the login attempts
            $user->login_attempt = $user->login_attempt + 1;
            $user->login_attempt_at = date('Y-m-d H:i:s', strtotime('now'));
            $user->save();
        }

        return $this->output(0, 'Incorrect Credentials');
    }

    /**
     * @return string JSON
     */
    public function registerAction()
    {
        $this->component->helper->csrf(self::REGISTER_REDIRECT_FAILURE);

        $alias = $this->request->getPost('alias');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $confirm_password = $this->request->getPost('confirm_password');

        if ($password != $confirm_password) {
            return $this->output(0, 'Your passwords do not match.');
        }

        if (strlen($alias) < 4 || !ctype_alpha($alias)) {
            return $this->output(0, 'Alias must be atleast 4 characters and only alphabetical.');
        }

        if (strlen($password) < 4 || strlen($password) > 128) {
            return $this->output(0, 'Your password must be 4-128 characters.');
        }

        if (User::findFirstByAlias($alias)) {
            return $this->output(0, 'Your alias cannot be used.');
        }

        if (User::findFirstByEmail($email)) {
            return $this->output(0, 'This email is already in use.');
        }

        if (!Swift_Validate::email($email)) {
            return $this->output(0, 'Your email is invalid.');
        }

        $user = new User();
        $user->role = 'user';
        $user->account_type = 'default';
        $user->alias = $alias;
        $user->email = $email;
        $user->password = $this->security->hash($password);
        // Create a unique hash per user
        $user->password_salt = $this->security->hash(random_int(5000, 100000));

        $result = $user->save();

        if (!$result) {
            return $this->output(0, $user->getMessagesList());
        }

        // Save them in the mailing list
        $newsletterSubscription = new NewsletterSubscription();
        $newsletterSubscription->email = $email;
        $newsletterSubscription->is_subscribed = 1; // @TODO is tihs right?
        $newsletterSubscription->save();

        // Where'd they signup from?
        $user->saveReferrer($user->id, $this->request);

        // Send an email!
        $mail_result = $this->di->get('email', [
            [
                'to_name'    => $user->getAlias($user->id),
                'to_email'   => $user->getEmail($user->id),
                'from_name'  => $this->config->email->from_name,
                'from_email' => $this->config->email->from_address,
                'subject'    => 'JREAM Registration',
                'content'    => $this->component->email->create('register', []),
            ],
        ]);

        // If email error, oh well still success
        if (!in_array($mail_result->statusCode(), [200, 201, 202])) {
            $message = 'You have successfully registered!
                                 However, there was a problem sending
                                 your welcome email.
                ';
        } else {
            $message = 'You have successfully registered!';
        }

        // Create the User Session
        $this->createSession($user);

        return $this->output(1, $message);
    }

    /**
     * @return string JSON
     */
    public function logoutAction()
    {
        if ($this->session->has('facebook_id')) {
            $this->session->destroy();
            $this->facebook->destroySession();
            $this->facebook->setAccessToken('');
            return $this->output(1, [
                'redirect' => $this->response->redirect($this->facebook->getLogoutUrl(), true)
            ]);
        }

        $this->session->destroy();
        return $this->output(1, ['redirect' => 'home']);
    }

    /**
     * @return string JSON
     */
    public function passwordResetAction()
    {
        $user_id = $this->session->get('user_id');
        $this->component->helper->csrf(self::PASSWORD_REDIRECT_FAILURE);

        $email = $this->request->getPost('email');
        $user = User::findFirstByEmail($email);

        if (!$user) {
            return $this->output(0, 'No email associated.');
        }

        $user->password_reset_key = hash('sha512', time() * random_int(1, 9999));
        $user->password_reset_expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $user->update();

        if ($user->getMessages())
        {
            return $this->output(0, 'An internal update to the user occurred.');
        }

        // Email: Generate
        $content = $this->component->email->create('confirm-password-change', [
            'reset_link' => getBaseUrl('user/passwordcreate/' . $user->password_reset_key),
        ]);

        // Email: Send
        $mail_result = $this->di->get('email', [
            [
                'to_name'    => $user->getAlias($user->id),
                'to_email'   => $user->getEmail($user->id),
                'from_name'  => $this->config->email->from_name,
                'from_email' => $this->config->email->from_address,
                'subject'    => 'JREAM Password Reset',
                'content'    => $content,
            ],
        ]);

        // Email: If the status code is not 200 the mail didn't send.
        if (!in_array($mail_result->statusCode(), [200, 201, 202]))
        {
            return $this->output(0, 'There was a problem sending the email.');
        }

        return $this->output(0,
            'A reset link has been sent to your email.
            You have 10 minutes to change your
            password before the link expires.
        ');
    }

    /**
     * @return string JSON
     */
    public function passwordResetConfirmAction()
    {
        $confirmEmail = $this->request->getPost('email');
        $resetKey = $this->request->getPost('reset_key');

        $this->component->helper->csrf(self::PASSWORD_REDIRECT_FAILURE_PASSWD . $resetKey);

        $user = User::findFirst([
            "email = :email: AND password_reset_key = :key: AND password_reset_expires_at > :date:",
            "bind" => [
                "email" => $confirmEmail,
                "key"   => $resetKey,
                "date"  => getDateTime(),
            ],
        ]);

        if (!$user) {
            return $this->output(0, 'Invalid email and key combo, or time has expired.');
        }

        $password = $this->request->getPost('password');
        $confirm_password = $this->request->getPost('confirm_password');

        if ($password != $confirm_password) {
            return $this->output(0, 'Your passwords do not match.');
        }

        // Create the new password, set a new salt and reset key
        $user->password = $this->security->hash($password);
        $user->password_salt = $this->security->hash(random_int(5000, 100000));
        $user->password_reset_key = null;
        $user->password_reset_expires_at = null;
        $user->save();

        if ($user->getMessages()) {
            return $this->output(0, 'There was an internal error updating.');
        }

        return $this->output(1, 'Your password has changed, please login.');
    }

    /**
     * Creates a User Session
     *
     * @param User $user  User Model
     * @param array $additional  Additional values to add to session
     *
     * @return void
     */
    protected function createSession(User $user, array $additional = [])
    {
        // Clear the login attempts
        $user->login_attempt    = null;
        $user->login_attempt_at = null;

        $this->session->set('id', $user->id);
        $this->session->set('role', $user->role);
        $this->session->set('alias', $user->getAlias());

        $use_timezone = 'utc';
        if (property_exists($user, 'timezone')) {
            $use_timezone = $user->timezone;
        }

        $this->session->set('timezone', $use_timezone);

        if (is_array($additional))
        {
            foreach ($additional as $_key => $_value)
            {
                $this->session->set($_key, $_value);
            }
        }

        // Delete old session so multiple logins aren't allowed
        session_regenerate_id(true);

        $user->session_id = $this->session->getId();
        $user->save();

        // If the user changes web browsers, prevent a hijacking attempt
        $this->session->set('agent', $_SERVER['HTTP_USER_AGENT']);
    }
}

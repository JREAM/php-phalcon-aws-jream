<?php

class ApiController extends \BaseController
{
    /**
     * Renders a markdown preview
     *
     * @return  string  json
     */
    public function markdownAction()
    {
        if (!$this->session->has('id')) {
            throw new \DomainException('Only Logged in users can call this.');
        }

        $parsedown = new \Parsedown();
        $content = trim($this->request->getPost('content'));
        if ($content) {
            $content = $parsedown->parse($content);
        }

        $this->output(1, $content);
    }

    // --------------------------------------------------------------

    /**
     * Googles ReCaptcha Verification
     *
     * @return JSON
     */
    public function recaptchaAction()
    {
        if ($this->session->has('recaptcha') && $this->session->get('recaptcha')) {
            $this->output(1, 'Recaptcha already approved');
            return;
        }


        if (\STAGE == 'local') {
            $this->session->set('recaptcha', 1);
            $this->output(1, 'Local Development Auto-Pass');
            return;
        }

        // Verify Recaptcha
        $recaptcha = $this->request->getPost('g-recaptcha-response');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_POST           => 1,
            CURLOPT_URL            => "https://www.google.com/recaptcha/api/siteverify",
            CURLOPT_POSTFIELDS     => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => "secret={$this->api->google['recaptchaSecret']}&response={$recaptcha}"
        ]);
        $response = json_decode(curl_exec($ch));
        curl_close($ch);

        $result = (bool) $response->success;

        // Set a session so they don't try to work-around it..
        $this->session->set('recaptcha', $result);
        $this->output($result, 'Invalid Recaptcha');
    }

    // --------------------------------------------------------------

    /**
     * Return some JSON stuff
     *
     * @return JSON
     */
    public function contactAction()
    {
        // Make sure recaptcha called and all
        // STAGE==LOCAL mode sets a value
        if (!$this->session->has('recaptcha')) {
            $this->output(0, 'Recaptcha not tried');
        }

        if (!$this->session->get('recaptcha')) {
            $this->output(0, 'Recaptcha not valid');
        }

        $form = new \ContactForm();

        // Make sure the form is valid
        if (!$form->isValid($_POST)) {
            $errors = [];
            foreach ($form->getMessages() as $message) {
                $errors[] = $message->getMessage();
            }
            $this->output(0, $errors);
        }

        // Gather the POST stuff
        $name = $this->request->getPost('name');
        $email = $this->request->getPost('email');
        $message = $this->request->getPost('message');
        // Create the Message from a template
        $content = $this->component->email->create('contact', [
            'name'    => $name,
            'email'   => $email,
            'message' => $message
        ]);

        $mail_result = $this->di->get('email', [
            [
            'to_name' => 'JREAM',
            'to_email' => 'hello@jream.com',
            'from_name' => $name,
            'from_email' => $email,
            'subject' => 'JREAM Contact Form',
            'content' => $content
            ]
        ]);

        if (! in_array($mail_result->_status_code, [200, 201, 202])) {
            $this->output(0, 'Error sending email');
        }

        // Succcess
        $this->session->set('recaptcha', 0);
        $this->output(1, 'Email Sent');
    }

    // --------------------------------------------------------------

    public function emailUnsubscribe($shaUserId, $emailId)
    {
        // Update DB or some shyt.
    }

    // --------------------------------------------------------------

    /**
     * Updates a single field.
     * @param  [type] $table [description]
     * @param  [type] $field [description]
     * @param  [type] $value [description]
     * @return [type]        [description]
     */
    public function adminUpdate($model, $primary_key, $column, $value)
    {
        if (!$this->session->has('id') || $this->session->get('role') != 'admin') {
            die;
        }
    }
}

// End of File
// --------------------------------------------------------------
<?php
use \Phalcon\Mvc\Model\Behavior\SoftDelete,
    \Phalcon\Mvc\Model\Validator;

class User extends BaseModel
{

    /** @const SOURCE the table name */
    const SOURCE = 'user';

    /** @var array Saves on Memcached Queries */
    public static $_cache;

    public function initialize()
    {
        $this->addBehavior(new SoftDelete([
            'field' => 'is_deleted',
            'value' => 1
        ]));

        $this->setSource(self::SOURCE);
        $this->skipAttributesOnCreate(['reset_key']);
        $this->hasMany('id', 'Project', 'user_id');
        $this->hasMany('id', 'UserAction', 'user_id');
        $this->hasMany('id', 'UserPurchase', 'user_id');
        $this->hasMany('id', 'UserSupport', 'user_id');
        $this->hasOne('id', 'UserReferrer', 'user_id');
        $this->hasOne('id', 'ForumThread', 'user_id');
    }

   // --------------------------------------------------------------

    /**
     * This fixes an odd bug.
     * @return string Class Name in lowercase
     */
    public function getSource()
    {
        return self::SOURCE;
    }

    // --------------------------------------------------------------

    /**
     * Gets a users Email since there are multiple clients
     *
     * @param  integer $id (Optional) Will uses current session by default
     *
     * @return string
     */
    public function getEmail($id = false)
    {
        if (!$id) {
            $id = $this->session->get('id');
        }

        // Only do this locally!
        if (\STAGE == 'local') {
            if (!$this->email && !$this->facebook_email) {
                return '&lt;&lt;no email&gt;&gt;';
            }
        }

        if (!$this->email) {
            return $this->facebook_email;
        }

        return $this->email;
    }

    // --------------------------------------------------------------

    /**
     * Gets a users Alias since there are multiple clients
     *
     * @param  integer $id (Optional) Will uses current session by default
     *
     * @return string
     */
    public function getAlias($id = false)
    {
        if (!$id) {
            $id = $this->session->get('id');
        }

        if (!$this->alias) {
            return $this->facebook_alias;
        }

        return $this->alias;
    }

    // --------------------------------------------------------------

    /**
     * Gets a users Icon from a service
     *
     * @param  integer $id (Optional) Will uses current session by default
     * @param  size $size (Optional) Will change the HTML width
     *
     * @return string
     */
    public function getIcon($id = false, $size = false)
    {
        if (!$id) {
            $id = $this->session->get('id');
        }

        if ($this->facebook_id) {
            $size = ($size) ? "width=$size" : false;
            return sprintf("<img $size src='https://graph.facebook.com/%s/picture?type=small' alt='facebook' />",
                    $this->facebook_id);
        }

        $email = ($this) ? $this->email : 'none@none.com';
        $default = "";
        $size = ($size) ? $size : 40;
        $url = sprintf('https://www.gravatar.com/avatar/%s?d=%s&s=%s',
                        md5(strtolower(trim($email))),
                        urlencode($default),
                        $size
        );
        return "<img src='$url' alt='Gravatar' />";
    }

    // --------------------------------------------------------------

    /**
     * Is a user banned?
     *
     * @param  object  $user
     * @return boolean
     */
    public function isBanned()
    {
        if (property_exists($this, 'banned') && $this->banned == 1) {
            return true;
        }
        return false;
    }

    // --------------------------------------------------------------

    /**
     * Captures where the user signed up from
     * @return [type] [description]
     */
    public function saveReferrer($userId, $request)
    {
        $referrer = new \UserReferrer();
        $referrer->user_id = $userId;
        $referrer->referrer = $request->getHTTPReferer();
        $referrer->data = json_encode([
            'page' => basename($_SERVER['PHP_SELF']),
            'query_string' => $request->getQuery(),
            'is_ajax' => $request->isAjax(),
            'is_ssl' => $request->isSecure(),
            'server_address' => $request->getServerAddress(),
            'server_name' => $request->getServerName(),
            'http_host' => $request->getHttpHost(),
            'client_address' => $request->getClientAddress(),
        ]);
        return $referrer->save();
    }

}

// End of File
// --------------------------------------------------------------

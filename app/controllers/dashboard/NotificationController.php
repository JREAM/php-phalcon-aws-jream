<?php

namespace Controllers\Dashboard;

use \Phalcon\Tag;

/**
 * @RoutePrefix("/dashboard/notification")
 */
class NotificationController extends \BaseController
{

    /**
     * @return void
     */
    public function onConstruct()
    {
        parent::initialize();
        Tag::setTitle('Notifications | ' . $this->di['config']['title']);
    }

    // --------------------------------------------------------------

    /**
     * @return void
     */
    public function indexAction()
    {
        $notifications = \UserNotification::findByUserId($this->session->get('id'));

        $this->view->setVars([
            'notifications' => $notifications,
        ]);

        $this->view->pick("dashboard/notification");
    }

    // --------------------------------------------------------------
}

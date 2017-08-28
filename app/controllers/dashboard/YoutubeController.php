<?php

namespace Controllers\Dashboard;

use Controllers\BaseController;
use \Phalcon\Tag;

class YoutubeController extends BaseController
{

    const REDIRECT_SUCCESS = '';
    const REDIRECT_FAILURE = 'dashboard';
    const REDIRECT_FAILURE_COURSE = 'dashboard';

    /**
     * @return void
     */
    public function onConstruct()
    {
        parent::initialize();
        Tag::setTitle('Youtube Videos | ' . $this->di['config']['title']);
    }

    /**
     * @param int $youtubeId
     *
     * @return mixed
     */
    public function indexAction($youtubeId = false)
    {
        $video = \Youtube::findFirstById($youtubeId);

        if (!$youtubeId) {
            $this->flash->error('There is no record of this item.');
            return $this->redirect(self::REDIRECT_FAILURE);
        }

        $this->view->setVars([
            'youtube'  => $video,
        ]);

        $this->view->pick("dashboard/youtube");
    }
}

<?php
declare(strict_types=1);

namespace Controllers\Api;

use \User;
use \Promotion;

class TestController extends ApiController
{
    /**
     * @return void
     */
    public function onConstruct()
    {
        parent::initialize();
    }

    public function indexAction()
    {
        return $this->output(1, 'This works, maybe?');
    }
}

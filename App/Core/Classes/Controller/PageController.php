<?php
namespace App\Core\Controller;

/**
 * Class PageController
 * @package App\Core\Controller
 */
class PageController extends AbstractController {

    /**
     * @return void
     */
    public function indexAction() {

        $this->getView()->render();
    }

    /**
     * @return void
     */
    public function show404Action() {
        $this->getView()->assign('message', 'Error Page not found');
        $this->getView()->render();
    }

    /**
     * @return void
     */
    public function show50xAction($message = 'Error, something went wrong.') {
        $this->getView()->assign('message', $message);
        $this->getView()->render();
    }
}
<?php

namespace App\Core\Controller;

/**
 * Class AbstractController
 * @package App\Core\Controller
 */
abstract class AbstractController {

    /** @var \App\Core\View\View | null */
    protected $view = null;

    /** @var \App\Core\Configuration\Configuration */
    protected $configuration = null;

    /** @var \Doctrine\ORM\EntityManager */
    protected $em = null;

    /** @var \Klein\Klein | null */
    private $klein = null;
    /**
     * myTreeView constructor.
     * @param \App\Core\View\View $view
     * @param \App\Core\Configuration\Configuration $configuration
     */
    public function __construct(
        \App\Core\View\View $view,
        \App\Core\Configuration\Configuration $configuration,
        \Klein\Klein $klein
    ) {
        if (session_id() === '') {
            session_start();
        }
        $this->view = $view;
        $this->configuration = $configuration;
        $this->klein = $klein;
        $doctrineService = \App\Core\Service\Doctrine::getInstance();
        $this->em = $doctrineService->getEntityManager();
    }

    /**
     * @return \Klein\Klein|null
     */
    public function getKlein() {
        return $this->klein;
    }

    /**
     * @param \Klein\Klein|null $klein
     */
    public function setKlein($klein) {
        $this->klein = $klein;
    }

    /**
     * @return \App\Core\View\View|null
     */
    public function getView() {
        return $this->view;
    }

    /**
     * @param \App\Core\View\View|null $view
     */
    public function setView($view) {
        $this->view = $view;
    }

    /**
     * @return \App\Core\Configuration\Configuration
     */
    public function getConfiguration() {
        return $this->configuration;
    }

    /**
     * @param \App\Core\Configuration\Configuration $configuration
     */
    public function setConfiguration($configuration) {
        $this->configuration = $configuration;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm() {
        return $this->em;
    }

    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function setEm($em) {
        $this->em = $em;
    }

    /**
     * @return string
     */
    public function getBaseUrl() {
        // output: /myproject/index.php
        $currentPath = $_SERVER['PHP_SELF'];

        // output: Array ( [dirname] => /myproject [basename] => index.php [extension] => php [filename] => index )
        $pathInfo = pathinfo($currentPath);

        // output: localhost
        $hostName = $_SERVER['HTTP_HOST'];

        // output: http://
        $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) == 'https://' ? 'https://' : 'http://';

        // return: http://localhost/myproject/
        $url = $protocol . $hostName . $pathInfo['dirname'] . '/';
        return str_replace('\\', '/', $url);
    }

    /**
     * @param string $url
     * @param int $code
     */
    public function redirect($url, $code = 302) {
        $redirectUrl = strpos($url, 'http') === 0 ? $url : $this->getBaseUrl() . $url;
//        $this->getKlein()->response()->redirect($redirectUrl, $code);
        header('Location: ' . $redirectUrl, TRUE, $code);
        exit;
    }

    /**
     * @param $actionName
     * @param array $actionArguments
     * @param null $method
     */
    public function redirectToAction($actionName, $actionArguments = array(), $method = NULL) {
        if ($method !== NULL && is_string($method)) {
            $this->getKlein()->request()->_method = $method;
        }
        if (method_exists($this, $actionName) && is_callable(array($this, $actionName))) {
            call_user_func_array(array($this, $actionName), $actionArguments);
        }
    }

    public function callBeforeAnyAction($format) {

    }

}
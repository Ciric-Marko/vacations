<?php

namespace App\Core\Bootstrap;

/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 12.08
 */

/**
 * Class Bootstrap
 * @package App\Core\Bootstrap
 */
class Bootstrap {

    /** @var \Klein\Klein | NULL */
    private $klein = NULL;

    /** @var \App\Core\Configuration\Configuration */
    private $configuration = NULL;

    /**
     * Bootstrap constructor.
     */
    public function __construct() {
        /** @var \App\Core\Configuration\Configuration $configuration */
        $this->configuration = \App\Core\Configuration\Configuration::getInstance();
        $this->klein = new \Klein\Klein();
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
     * @param $className
     * @param array $args
     * @return object
     */
    protected function getObject($className, $args = array()) {
        $reflect = new \ReflectionClass($className);
        return $reflect->newInstanceArgs($args);
    }

    /**
     * @return \Klein\Klein|NULL
     */
    protected function getKlein() {
        return $this->klein;
    }

    /**
     *  renders 404 page
     */
    protected function call404() {
        $pluginName = $this->getConfiguration()->get404Plugin();
        $controllerName = $this->getConfiguration()->get404Controller();
        $actionName = $this->getConfiguration()->get404Action();
        $arguments = $this->getConfiguration()->get404Arguments();
        $this->callControllerAction($pluginName, $controllerName, $actionName, $arguments);
    }

    /**
     *  renders 50x page
     */
    protected function call50x($message = 'Error, something went wrong.') {
        $pluginName = $this->getConfiguration()->get50xPlugin();
        $controllerName = $this->getConfiguration()->get50xController();
        $actionName = $this->getConfiguration()->get50xAction();
        $arguments = array('message' => $message);
        $this->callControllerAction($pluginName, $controllerName, $actionName, $arguments);
    }

    /**
     * @param string $pluginName
     * @param string $controllerName
     * @param string $actionName
     * @param mixed $arguments
     * @return void
     */
    protected function callControllerAction($pluginName, $controllerName, $actionName, $arguments = array()) {

        $controllerClass = $this->getConfiguration()->getControllerClass($controllerName, $pluginName);
        if (class_exists(ucfirst($controllerClass))) {
            $layoutPath = $this->getConfiguration()->getLayoutForControllerAction($actionName, $controllerName,
                $pluginName);
            $templatePath = $this->getConfiguration()->getTemplateForControllerAction($actionName, $controllerName,
                $pluginName);
            $viewClass = $this->getConfiguration()->getControllerViewClass($controllerName, $pluginName);
            $view = $this->getObject($viewClass, array($this->getConfiguration()->getConfig(), $layoutPath, $templatePath));
            $controller = $this->getObject($controllerClass, array($view, $this->getConfiguration(), $this->getKlein()));
            $action =  $actionName .= 'Action';
            $callBeforeAnyAction = 'callBeforeAnyAction';
            $beforeAction = 'before' . ucfirst($actionName) . 'Action';
            if (method_exists($controller, $action) && is_callable(array($controller, $action))) {
                $actionArguments = array();
                $reflectionMethod = new \ReflectionMethod($controller, $action);
                $parameters = $reflectionMethod->getParameters();
                foreach ($parameters as $arg) {
                    if (isset($arguments[$arg->name])) {
                        $actionArguments[$arg->name] = $arguments[$arg->name];
                    } else {
                        if ($arg->isDefaultValueAvailable()) {
                            $actionArguments[$arg->name] = $arg->getDefaultValue();
                        } else {
                            // if single argument is passed without name
                            if (count($parameters) === 1 && isset($arguments[0])) {
                                $actionArguments[$arg->name] = $arguments[0];
                            } else {
                                $actionArguments[$arg->name] = NULL;
                            }
                        }
                    }
                }
                if (method_exists($controller, $callBeforeAnyAction) && is_callable(array($controller, $callBeforeAnyAction))) {
                    $format = isset($actionArguments['format']) ? $actionArguments['format'] : '.html';
                    call_user_func_array(array($controller, $callBeforeAnyAction), array('format' => $format));
                }
                if (method_exists($controller, $beforeAction) && is_callable(array($controller, $beforeAction))) {
                    call_user_func_array(array($controller, $beforeAction), $actionArguments);
                }
                call_user_func_array(array($controller, $actionName), $actionArguments);
            } else {
                $this->getKlein()->abort(404);
            }
        } else {
            $this->getKlein()->abort(404);
        }
    }

    /**
     * @param bool $callDefaultOnEmpty
     * @throws \Exception
     */
    public function run() {
        $currentPath = $_SERVER['PHP_SELF'];
        $pathInfo = pathinfo($currentPath);
        $dirName = $pathInfo['dirname'];
        $dirName = $dirName === '\\' ? '' : $dirName;
        $namespaces = array($dirName);
        $bootstrap = $this;
        foreach ($namespaces as $namespace) {
            $this->getKlein()->with($namespace, function (\Klein\Klein $router) use ($bootstrap) {
                $router->respond('/', function () {
                    $pluginName = $this->getConfiguration()->getDefaultPlugin();
                    $controllerName = $this->getConfiguration()->getDefaultController();
                    $actionName = $this->getConfiguration()->getDefaultAction();
                    $arguments = $this->getConfiguration()->getDefaultArguments();
                    $this->callControllerAction($pluginName, $controllerName, $actionName, $arguments);
                });

                $router->respond(
                    '/[:plugin]/[:controller]/[:action]?/[*:value]?[.json|.html:format]?/?',
                    function (
                        \Klein\Request $request,
                        \Klein\Response $response,
                        \Klein\ServiceProvider $service,
                        \Klein\App $app
                    ) {
                        $pluginName = $request->plugin;
                        $controllerName = $request->controller;
                        $actionName = $request->action ?: 'index';
                        $format = $request->format ? strtolower($request->format) : '.html';
                        $arguments = array();
                        if ($request->value) {
                            /*
                             * transform /name1/val1/name2/val2/name3/val3
                             * to array('name1' => val1', 'name2' => val2, 'name3' => val3)
                             * to pass it as named arguments
                             */
                            $argChunks = array_chunk(explode('/', $request->value), 2);
                            $arguments = array_reduce($argChunks, function ($result, $item) {
                                if (isset($item[0])) {
                                    if (isset($item[1])) {
                                        $result[$item[0]] = $item[1];
                                    } else {
                                        $result['id'] = $item[0];
                                    }
                                }
                                return $result;
                            }, array());
                        }
                        $arguments = \array_merge(
                            $arguments,
                            $request->paramsGet()->all(),
                            $request->paramsPost()->all(),
                            array('format' => $format)
                        );
                        $this->callControllerAction($pluginName, $controllerName, $actionName, $arguments);
                    }
                );
            });
        }
        // handle http status errors
        $this->getKlein()->onHttpError(
            function ($code, \Klein\Klein $klein) {
                if ($code >= 400 && $code < 500) {
                    $this->call404();
                } else {
                    if ($code >= 500 && $code <= 599) {
                        $this->call50x();
                    }
                }
            }
        );
        /**
         * Handle php exception errors
         */
        $this->getKlein()->onError(
            function (
                \Klein\Klein $klein,
                $msg,
                $type,
                \Exception $err
            ) {
                $this->call50x($msg);
                $klein->response()->code(503)->send();
            });
        $this->getKlein()->dispatch();
    }
}
<?php

namespace App\Core\Configuration;

/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 12.33
 */

use Symfony\Component\Yaml\Yaml;

/**
 * Class Configuration
 * @package App\Core\Configuration
 */
final class Configuration {

    /**
     * @var string | null
     */
    protected $rootPath = null;
    /**
     * @var array | null
     */
    protected $config = null;

    /** @var \App\Core\Configuration\Configuration  */
    protected static $instance = null;

    /**
     * Make constructor private, so nobody can call "new Class".
     */
    private function __construct() {
    }

    /**
     * Make clone magic method private, so nobody can clone instance.
     */
    private function __clone() {
    }

    /**
     * Make sleep magic method private, so nobody can serialize instance.
     */
    private function __sleep() {
    }

    /**
     * Make wakeup magic method private, so nobody can unserialize instance.
     */
    private function __wakeup() {
    }

    /**
     * @return Configuration
     */
    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * @param string $path
     * @param $default
     * @return array|mixed
     */
    public function getConfig($path = '', $default = null) {

        if ($this->config === null) {
            $this->config = Yaml::parse(file_get_contents(__DIR__ . '/../../../config.yml'));
            if (isset($this->config['imports'])) {
                $imports = $this->config['imports'];
                foreach ($imports as $importPath) {
                    $import = Yaml::parse(file_get_contents(__DIR__ . '/../../../' . $importPath));
                    if (is_array($import)) {
                        $this->config = array_merge_recursive($this->config, $import);
                    }
                }
            }
        }
        //var_dump($this->config);
        if (!empty($path)) {
            $ret = $this->getArrayPath($path, $this->config);
            return $ret ? $ret : $default;
        } else {
            return $this->config;
        }
    }

    /**
     * @param string $path
     * @param array $deepArray
     * @return mixed
     */
    protected function getArrayPath($path, array $deepArray) {

       // var_dump($path, $deepArray);
        $reduce = function (array $xs, $x) {
            return (
            array_key_exists($x, $xs)
            ) ? $xs[$x] : array();
        };
        $pathArr = \explode('/', $path);
        return array_reduce($pathArr, $reduce, $deepArray);
    }

    /**
     * @return array
     */
    public function getPlugins() {
        return array_keys($this->config['plugins']);
    }

    /**
     * @return string|null
     */
    public function getDefaultPlugin() {
        return $this->getConfig('default/plugin');
    }

    /**
     * @return string|null
     */
    public function getDefaultController() {
        return $this->getConfig('default/controller');
    }

    /**
     * @return string|null
     */
    public function getDefaultAction() {
        return $this->getConfig('default/action');
    }

    /**
     * @return string|null
     */
    public function getDefaultArguments() {
        return $this->getConfig('default/arguments');
    }

    /**
     * @return string|null
     */
    public function getLoginUrl() {
        return $this->getConfig('loginUrl');
    }
    /**
     * @return string|null
     */
    public function get404Plugin() {
        return $this->getConfig('error404/plugin');
    }

    /**
     * @return string|null
     */
    public function get404Controller() {
        return $this->getConfig('error404/controller');
    }

    /**
     * @return string|null
     */
    public function get404Action() {
        return $this->getConfig('error404/action');
    }

    /**
     * @return string|null
     */
    public function get404Arguments() {
        return $this->getConfig('error404/arguments');
    }

    /**
     * @return string|null
     */
    public function get50xPlugin() {
        return $this->getConfig('error50x/plugin');
    }

    /**
     * @return string|null
     */
    public function get50xController() {
        return $this->getConfig('error50x/controller');
    }

    /**
     * @return string|null
     */
    public function get50xAction() {
        return $this->getConfig('error50x/action');
    }

    /**
     * @return string|null
     */
    public function get50xArguments() {
        return $this->getConfig('error50x/arguments');
    }

    /**
     * @param string $controllerName
     * @param string $pluginName
     * @return bool
     */
    public function hasController($controllerName, $pluginName = 'backend') {
        return $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName) !== null;
    }

    /**
     * @param string $actionName
     * @param string $controllerName
     * @param string $pluginName
     * @return bool
     */
    public function hasControllerAction($actionName, $controllerName, $pluginName = 'backend') {
        return $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName . '/actions/' . $actionName) !== null;
    }

    /**
     * @param string $controllerName
     * @param string $pluginName
     * @return mixed
     */
    public function getControllerClass($controllerName, $pluginName = 'backend') {
        return $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName . '/controllerClass');
    }

    /**
     * @param string $controllerName
     * @param string $pluginName
     * @return mixed
     */
    public function getControllerViewClass($controllerName, $pluginName = 'backend') {
        return $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName . '/viewClass');
    }

    /**
     * @param string $actionName
     * @param string $controllerName
     * @param string $pluginName
     * @return string
     */
    public function getLayoutForControllerAction($actionName, $controllerName, $pluginName = 'backend') {
        if ($this->hasControllerAction($actionName, $controllerName, $pluginName)) {
            $layout = $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName . '/actions/' . $actionName . '/layoutPath');
            return $layout;
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getLayoutPath() {
        return $this->getRootPath() . 'Resources/Private/Layouts/';
    }

    /**
     * @param string $actionName
     * @param string $controllerName
     * @param string $pluginName
     * @return string
     */
    public function getTemplateForControllerAction($actionName, $controllerName, $pluginName = 'backend') {
        if ($this->hasControllerAction($actionName, $controllerName, $pluginName)) {
            $template = $this->getConfig('plugins/' . $pluginName . '/controllers/' . $controllerName . '/actions/' . $actionName . '/templatePath');
            return $template;
        } else {
            return '';
        }
    }

    /**
     * @return string
     */
    public function getTemplatePath() {
        return $this->getRootPath() . 'Resources/Private/Templates/';
    }

    /**
     * @return string
     */
    public function getDefaultLanguage() {
        return $this->getConfig('default/language', 'ger');
    }

    /**
     * @return string
     */
    public function getFallbackLanguage() {
        return $this->getConfig('default/fallbackLanguage', 'eng');
    }

    /**
     * @return string
     */
    public function getRootPath() {
        if ($this->rootPath === null) {
            $this->rootPath = __DIR__ . '/../../';
        }
        return $this->rootPath;
    }
}
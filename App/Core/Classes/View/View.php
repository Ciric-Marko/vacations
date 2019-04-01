<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 22.45
 */

namespace App\Core\View;

/**
 * Class View
 * @package App\Core\View
 */
class View {

    /**
     * render/output type
     */
    const RENDER_TYPE_HTML = 0;
    const RENDER_TYPE_JSON = 1;
    /**
     * @var int
     */
    protected $renderType = self::RENDER_TYPE_HTML;

    /**
     * @var string
     */
    protected $layoutPath = '';

    /**
     * @var string
     */
    protected $templatePath = '';

    /**
     * @var array
     */
    protected $assignedVariables = array();

    /**
     * @var array
     */
    protected $config = array();

    /**
     * View constructor.
     * @param array $config
     * @param string $layoutPath
     * @param string $templatePath
     */
    public function __construct($config, $layoutPath, $templatePath) {
        $this->config = $config;
        $this->layoutPath = $layoutPath;
        $this->templatePath = $templatePath;
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function render() {
        switch ($this->getRenderType()) {
            case self::RENDER_TYPE_HTML:
                $this->assign('base', $this->getBaseUrl());
                $this->assign('templatePath', $this->getTemplatePath());
                $this->assign('config', $this->config);

                $loader = new \Twig\Loader\FilesystemLoader();
                $loader->addPath('App/Core/Resources/Private/Layouts/');
                $loader->addPath('App/Core/Resources/Private/Templates/');
                $twig = new \Twig\Environment($loader, [
//                    'cache' => '/path/to/compilation_cache',
                    'debug' => true,
                ]);
                $twig->addExtension(new \Twig\Extension\DebugExtension());
                $name = $this->getTemplatePath();
                echo $twig->render($name, $this->assignedVariables);
                break;
            case self::RENDER_TYPE_JSON:
                header("Content-type: application/json; charset=utf-8");
                /** @var array $nodes */
                echo json_encode($this->assignedVariables);
                break;
            default:
                throw new \Exception('Unknown render type.');
        }
    }

    /**
     * @param $key
     * @param $value
     */
    public function assign($key, $value) {
        $this->assignedVariables[$key] = $value;
    }

    /**
     * @return string
     */
    public function getLayoutPath() {
        return $this->layoutPath;
    }

    /**
     * @param string $layoutPath
     */
    public function setLayoutPath($layoutPath) {
        $this->layoutPath = $layoutPath;
    }

    /**
     * @return string
     */
    public function getTemplatePath() {
        return $this->templatePath;
    }

    /**
     * @param string $templatePath
     */
    public function setTemplatePath($templatePath) {
        $this->templatePath = $templatePath;
    }

    /**
     * @return int
     */
    public function getRenderType() {
        return $this->renderType;
    }

    /**
     * @param int $renderType
     */
    public function setRenderType($renderType) {
        $this->renderType = $renderType;
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
        $url = $protocol . $hostName . $pathInfo['dirname'];
        return str_replace('\\', '/', $url);
    }

}
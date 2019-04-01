<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 22.45
 */

namespace App\Vacation\View;

/**
 * Class View
 * @package App\Vacation\View
 */
class View extends \App\Core\View\View {

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
                $loader->addPath('App/Vacation/Resources/Private/Layouts/');
                $loader->addPath('App/Vacation/Resources/Private/Templates/');
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
                exit;
                break;
            default:
                throw new \Exception('Unknown render type.');
        }
    }
}
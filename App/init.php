<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 11.19
 */
defined('INITIALED') || die('Access denied.');
require(__DIR__ . "/Packages/vendor/autoload.php");

$bootstrap = new App\Core\Bootstrap\Bootstrap();
$bootstrap->run();


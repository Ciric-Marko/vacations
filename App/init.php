<?php

/**
 *
 */
defined('INITIALED') || die('Access denied.');
require(__DIR__ . "/Packages/vendor/autoload.php");

$bootstrap = new App\Core\Bootstrap\Bootstrap();
$bootstrap->run();


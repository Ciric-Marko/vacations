<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 22.3.2019.
 * Time: 23.25
 */

require(__DIR__ . "/vendor/autoload.php");

$doctrineService = \App\Core\Service\Doctrine::getInstance();
$entityManager = $doctrineService->getEntityManager();

return \Doctrine\ORM\Tools\Console\ConsoleRunner::createHelperSet($entityManager);
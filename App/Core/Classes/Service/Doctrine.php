<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 9.3.2019.
 * Time: 12.33
 */

namespace App\Core\Service;

use Symfony\Component\Yaml\Yaml;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type;
use App\Core\Helpers\DoctrineExtensions\DBAL\Types\UTCDateTimeType;


/**
 * Class Configuration
 * @package App\Core\Service
 */
final class Doctrine {

    /** @var \App\Core\Service\Doctrine|NULL */
    protected static $instance = NULL;

    /** @var \Doctrine\DBAL\Connection|NULL */
    protected $connection = NULL;

    /** @var \Doctrine\ORM\EntityManager|NULL */
    protected $entityManager = NULL;

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

    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * @return \Doctrine\DBAL\Connection|NULL
     */
    public function getConnection() {
        if (!$this->connection) {
            $dbalConfig = new \Doctrine\DBAL\Configuration();
            $dbConfigFile = __DIR__ . '/../../../db_config.yml';
            $config = Yaml::parse(file_get_contents($dbConfigFile));

            if ($config['type'] && isset($config[$config['type']])) {
                $parameters = $config[$config['type']];

                $connectionParams = array(
                    'dbname' => $parameters['dbname'],
                    'user' => $parameters['user'],
                    'password' => $parameters['password'],
                    'host' => $parameters['host'],
                    'port' => $parameters['port'],
                    'charset' => $parameters['charset'],
                    'driver' => $parameters['driver'],
                );
                $this->connection = \Doctrine\DBAL\DriverManager::getConnection($connectionParams, $dbalConfig);
            } else {
                throw new \Exception('Invalid db configuration in ' . $dbConfigFile);
            }
        }
        return $this->connection;
    }

    /**
     * @return \Doctrine\ORM\EntityManager|NULL
     */
    public function getEntityManager() {
        if (!$this->entityManager) {
//            Type::overrideType('datetime', UTCDateTimeType::class);
//            Type::overrideType('datetimetz', UTCDateTimeType::class);
            $dbConfigFile = __DIR__ . '/../../../db_config.yml';
            $config = Yaml::parse(file_get_contents($dbConfigFile));

            if ($config['type'] && isset($config[$config['type']])) {
                $parameters = $config[$config['type']];

                $connectionParams = array(
                    'dbname' => $parameters['dbname'],
                    'user' => $parameters['user'],
                    'password' => $parameters['password'],
                    'host' => $parameters['host'],
                    'port' => $parameters['port'],
                    'charset' => $parameters['charset'],
                    'driver' => $parameters['driver'],
                );
                $isDevMode = TRUE;
                $annotationMetadataConfiguration = Setup::createAnnotationMetadataConfiguration(
                    array(
                        __DIR__."/../Domain/Model/",
                        __DIR__."/../../../Vacation/Classes/Domain/Model/",
                    ),
                    $isDevMode
                );
                $this->entityManager = EntityManager::create($connectionParams, $annotationMetadataConfiguration);
            } else {
                throw new \Exception('Invalid db configuration in ' . $dbConfigFile);
            }
        }
        return $this->entityManager;
    }
}
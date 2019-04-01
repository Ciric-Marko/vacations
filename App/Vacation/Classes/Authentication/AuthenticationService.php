<?php
/**
 * Created by PhpStorm.
 * User: marko
 * Date: 30.3.2019.
 * Time: 00.36
 */

namespace App\Vacation\Authentication;

class AuthenticationService {

    /** @var AuthenticationService */
    protected static $instance = NULL;

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
     * @return AuthenticationService
     */
    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static;
        }
        return static::$instance;
    }

    /**
     * Authentication adapter
     *
     * @var AdapterInterface
     */
    protected $adapter = NULL;


    /**
     * @var Result NULL
     */
    protected $authenticationResult = NULL;

    /**
     * Returns the authentication adapter
     *
     * The adapter does not have a default if the storage adapter has not been set.
     *
     * @return AdapterInterface|NULL
     */
    public function getAdapter() {
        return $this->adapter;
    }

    /**
     * Sets the authentication adapter
     *
     * @param  AdapterInterface $adapter
     * @return self Provides a fluent interface
     */
    public function setAdapter(AdapterInterface $adapter) {
        $this->adapter = $adapter;
        return $this;
    }

    /**
     * @return Result
     */
    public function getAuthenticationResult() {
        return $this->authenticationResult ?: new Result(Result::FAILURE_UNCATEGORIZED, NULL);
    }

    /**
     * @param Result $authenticationResult
     */
    public function setAuthenticationResult($authenticationResult) {
        $this->authenticationResult = $authenticationResult;
    }

    /**
     * Authenticates against the supplied adapter
     *
     * @param  AdapterInterface $adapter
     * @return AuthenticationService
     * @throws \Exception
     */
    public function authenticate(AdapterInterface $adapter = NULL) {
        if (!$adapter) {
            $adapter = $this->getAdapter();
            if ($adapter) {
                $this->setAuthenticationResult($adapter->authenticate());
            }
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function hasIdentity() {
        return $this->getIdentity() !== NULL;
    }

    /**
     * @return mixed
     */
    public function getIdentity() {
        return $this->getAuthenticationResult()->getIdentity() !== NULL ?
            $this->getAuthenticationResult()->getIdentity() : $this->getAdapter()->getIdentity();
    }

    /**
     *
     */
    public function logOut() {
        if ($this->getAdapter() && $this->getAdapter()->hasIdentity()) {
            $this->getAdapter()->clearIdentity();
        }
    }
}
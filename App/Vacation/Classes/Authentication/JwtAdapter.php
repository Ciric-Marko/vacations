<?php
namespace App\Vacation\Authentication;

class JwtAdapter implements \App\Vacation\Authentication\AdapterInterface
{
    /**
     * @var string
     */
    private $token  =  '';

    /**
     * @var string
     */
    private $secret  =  'some_secret';

    /**
     * @var string
     */
    private $userName  =  '';

    /**
     * @var string
     */
    private $password  =  '';

    /**
     * Sets token and password for authentication
     *
     * @return void
     */
    public function __construct($secret = '', $userName = '', $password = '') {
        if (!empty($userName) && !empty($password)) {
            $this->userName = $userName;
            $this->password = $password;
        }
        if (!empty($token)) {
            $this->token = $token;
        }
        if (!empty($secret)) {
            $this->secret = $secret;
        }
    }

    /**
     * @return string
     */
    public function getToken() {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token) {
        $this->token = $token;
    }

    /**
     * @return string
     */
    public function getSecret() {
        return $this->secret;
    }

    /**
     * @param string $secret
     */
    public function setSecret($secret) {
        $this->secret = $secret;
    }


    /**
     * Performs an authentication attempt
     *
     * @return Result
     * @throws \Exception
     *               If authentication cannot be performed
     */
    public function authenticate() {

        $result = new Result(Result::FAILURE_UNCATEGORIZED, NULL);
        $jwtTokenUtility = new \App\Vacation\Utility\JwtTokenUtility($this->getSecret());
        if (!empty($this->userName)) {
            $doctrineService = \App\Core\Service\Doctrine::getInstance();
            $em = $doctrineService->getEntityManager();
            // ...
            /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
            $repository = $em->getRepository(\App\Vacation\Domain\Model\User::class);
            //The ORM internally escapes all your values, because it has lots of metadata available about the current context.
            /** @var \App\Vacation\Domain\Model\User $user */
            $user = $repository->findOneBy(array('userName' => $this->userName));
            if ($user) {
                if ($user->validatePassword($this->password)) {
                    $result = new Result(Result::SUCCESS, $user);
                    $token = $jwtTokenUtility->createToken($user->getId());
                    $this->setToken($token);
                } else {
                    $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid Password']);
                }
            } else {
                $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, ['Invalid username']);
            }
        } else {
            $token = str_replace('Bearer ', '',
                isset($_SERVER['HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION'] : '');
            $tokenData = $jwtTokenUtility->validateToken($token);
            if ($tokenData && $tokenData->uid) {
                $this->setToken($token);
                $doctrineService = \App\Core\Service\Doctrine::getInstance();
                $em = $doctrineService->getEntityManager();
                // ...
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $em->getRepository(\App\Vacation\Domain\Model\User::class);
                //The ORM internally escapes all your values, because it has lots of metadata available about the current context.
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => $tokenData->uid));
                if ($user) {
                    $result = new Result(Result::SUCCESS, $user);
                } else {
                    $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, ['Invalid token']);
                }
            } else {
                $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid token']);
            }
        }

        return $result;
    }

    /**
     * Returns true if and only if an identity is available from storage
     *
     * @return bool
     */
    public function hasIdentity() {
        return $this->authenticate()->getIdentity() !== null;
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity() {
        return $this->authenticate()->getIdentity();
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity() {

    }
}
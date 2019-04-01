<?php
namespace App\Vacation\Authentication;

class WebAdapter implements \App\Vacation\Authentication\AdapterInterface
{
    /**
     * @var string
     */
    private $userName  =  '';

    /**
     * @var string
     */
    private $password  =  '';

    /**
     * Sets username and password for authentication
     *
     * @return void
     */
    public function __construct($userName = '', $password = '') {
        if (!empty($userName) && !empty($password)) {
            $this->userName = $userName;
            $this->password = $password;
        }
        if (session_id() === '') {
            session_start();
        }
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
                } else {
                    $result = new Result(Result::FAILURE_CREDENTIAL_INVALID, null, ['Invalid Password']);
                }
            } else {
                $result = new Result(Result::FAILURE_IDENTITY_NOT_FOUND, null, ['Invalid username']);
            }

            if ($this->hasIdentity()) {
                $this->clearIdentity();
            }
            if ($result->isValid()) {
                $_SESSION['identity'] = $result->getIdentity();
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
        return isset($_SESSION['identity']);
    }

    /**
     * Returns the identity from storage or null if no identity is available
     *
     * @return mixed|null
     */
    public function getIdentity() {
        return isset($_SESSION['identity']) ? $_SESSION['identity'] : NULL;
    }

    /**
     * Clears the identity from persistent storage
     *
     * @return void
     */
    public function clearIdentity() {
        unset($_SESSION['identity']);
    }
}
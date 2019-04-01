<?php

namespace App\Vacation\Controller;

use \App\Core\Controller\AbstractController;
use vakata\database\Exception;

/**
 * Class AuthenticationController
 * @package App\Vacation\Controller
 */
class AuthenticationController extends AbstractController {


    /**
     * @param string $format
     */
    public function loginAction($format) {
        $authenticationService = \App\Vacation\Authentication\AuthenticationService::getInstance();
        $authenticationService->setAdapter(new \App\Vacation\Authentication\WebAdapter());
        $authenticationService->authenticate();
        $authenticationResult = $authenticationService->getAuthenticationResult();
        if ($authenticationResult->isValid()) {
            /** @var \App\Vacation\Domain\Model\User $user */
            $user = $authenticationService->getIdentity();
            $this->redirect('vacations/users/show/' . $user->getId());
        } else {
            $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
            $this->getView()->render();
        }
    }

    /**
     * @param string $userName
     * @param string $password
     * @param string $format
     */
    public function authAction($userName, $password, $format) {
        $success = false;
        $message = '';
        $user = null;
        try {
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST') {
                $authenticationService = \App\Vacation\Authentication\AuthenticationService::getInstance();
                if ($format === '.html') {
                    $authenticationService->setAdapter(new \App\Vacation\Authentication\WebAdapter($userName,
                        $password));
                }
                if ($format == '.json') {
                    $authenticationService->setAdapter(new \App\Vacation\Authentication\JwtAdapter($this->getConfiguration()->getConfig('jwtSecret'),
                        $userName, $password));
                }
                $authenticationService->authenticate();

                $authenticationResult = $authenticationService->getAuthenticationResult();
//                var_dump($authenticationResult);
                if ($authenticationResult->isValid()) {
                    /** @var \App\Vacation\Domain\Model\User $user */
                    $user = $authenticationService->getIdentity();
                    $this->getView()->assign('user', $user);
                    if ($format == '.json') {
                        $token = $authenticationService->getAdapter()->getToken();
                        $this->getView()->assign('token', $token);
                    }
//                    $this->getView()->assign('authenticationResult', $authenticationResult);
                    $message = 'Successful login';
                    $success = true;
                } else {
                    throw new \Exception(\join(' ', $authenticationResult->getMessages()), 403);
                }
            } else {
                throw new \Exception('Wrong Method : ' . $method, 405);
            }
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
            $this->getKlein()->response()->code($e->getCode());
        }
        if ($format === '.json') {
            $this->getView()->assign('success', $success);
            $this->getView()->assign('message', $message);
            $this->getView()->setRenderType(\App\Core\View\View::RENDER_TYPE_JSON);
            $this->getView()->render();
        } else {
            if ($success && $user) {
                $this->redirect('vacations/users/show/' . $user->getId());
            } else {
                $this->getKlein()->service()->flash($message, 'danger');
                $this->redirect($this->configuration->getLoginUrl());
            }
        }
    }


    /**
     * @param string $userName
     * @param string $password
     * @param string $format
     */
    public function logoutAction($format) {
        $authenticationService = \App\Vacation\Authentication\AuthenticationService::getInstance();
        if ($format == '.html') {
            $authenticationService->setAdapter(new \App\Vacation\Authentication\WebAdapter());
        }
        if ($format == '.json') {
            $authenticationService->setAdapter(new \App\Vacation\Authentication\JwtAdapter($this->getConfiguration()->getConfig('jwtSecret')));
        }
        $authenticationService->logOut();
        $this->redirect($this->configuration->getLoginUrl());
    }
}
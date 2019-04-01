<?php

namespace App\Vacation\Controller;

use \App\Core\Controller\AbstractController;

/**
 * Class UserController
 * @package App\Vacation\Controller
 */
class UserController extends AbstractController {

    /** @var null | \App\Vacation\Domain\Model\User */
    private $loggedInUser = null;

    /**
     * UserController constructor.
     */
    public function __construct(
        \App\Core\View\View $view,
        \App\Core\Configuration\Configuration $configuration,
        \Klein\Klein $klein
    ) {
        parent::__construct($view, $configuration, $klein);
        $authenticationService = \App\Vacation\Authentication\AuthenticationService::getInstance();
        $format = $klein->request()->format ?: '.html';
        if ($format == '.html') {
            $authenticationService->setAdapter(new \App\Vacation\Authentication\WebAdapter());
        }
        if ($format == '.json') {
            $authenticationService->setAdapter(new \App\Vacation\Authentication\JwtAdapter($this->getConfiguration()->getConfig('jwtSecret')));
        }
        $authenticationService->authenticate();
        $loggedInUser = $authenticationService->getIdentity();
        if ($loggedInUser) {
            $this->setLoggedInUser($loggedInUser);
            $this->getView()->assign('loggedInUser', $loggedInUser);
        } else {
            if ($format == '.html') {
                $this->redirect($configuration->getLoginUrl());
            }
            if ($format == '.json') {
                $this->getView()->assign('success', false);
                $this->getView()->assign('message', 'invalid token');
                $this->getView()->setRenderType(\App\Core\View\View::RENDER_TYPE_JSON);
                $this->getView()->render();
            }
        }
    }

    /**
     * @return \App\Vacation\Domain\Model\User
     */
    public function getLoggedInUser() {
        return $this->loggedInUser;
    }

    /**
     * @param \App\Vacation\Domain\Model\User $loggedInUser
     */
    public function setLoggedInUser(\App\Vacation\Domain\Model\User $loggedInUser) {
        $this->loggedInUser = $loggedInUser;
    }

    /**
     * @return void
     */
    public function indexAction($format) {
        $success = false;
        $message = '';
        try {
            /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
            $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
            $users = $repository->findAll();
            $this->getView()->assign('users', $users);
            $success = true;
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
            $this->getKlein()->response()->code($e->getCode());
        }
        if ($format === '.json') {
            $this->getView()->assign('success', $success);
            if (!empty($message)) {
                $this->getView()->assign('message', $message);
            }
            $this->getView()->setRenderType(\App\Core\View\View::RENDER_TYPE_JSON);
            $this->getView()->render();
        } else {
            if (!$success) {
                $this->getKlein()->service()->flash($message, 'danger');
            }
            $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
            $this->getView()->render();
        }
    }


    /**
     * @param int $userId
     */
    public function showAction($id, $format) {
        $success = false;
        $message = '';
        try {
            if ($id) {
                if (intval($id) !== $this->getLoggedInUser()->getId() && !$this->getLoggedInUser()->isAdmin()) {
                    throw new \Exception('Not authorized', 403);
                }
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                //The ORM internally escapes all your values, because it has lots of metadata available about the current context.
                $user = $repository->findOneBy(array('id' => intval($id)));
                if (!$user) {
                    throw new \Exception('user not found');
                }
                $this->getView()->assign('user', $user);
                $success = true;
            } else {
                throw new \Exception('user id required');
            }
        } catch (\Exception $e) {
            $success = false;
            $message = $e->getMessage();
            $this->getKlein()->response()->code($e->getCode());
        }
        if ($format === '.json') {
            $this->getView()->assign('success', $success);
            if (!empty($message)) {
                $this->getView()->assign('message', $message);
            }
            $this->getView()->setRenderType(\App\Core\View\View::RENDER_TYPE_JSON);
            $this->getView()->render();
        } else {
            if (!empty($message)) {
                $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            }
            if ($success) {
                $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
                $this->getView()->render();
            } else {
                $this->redirect('vacations/users/');
            }
        }
    }

    /**
     * @param array $user
     */
    public function newAction() {
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
            $this->getView()->render();
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash($e->getMessage(), 'danger');
            $this->redirect('vacations/users/');
        }
    }

    /**
     * @param $entity
     * @param $format
     */
    public function createAction($entity, $format) {
        $success = false;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST') {
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                //The ORM internally escapes all your values, because it has lots of metadata available about the current context.
                $existingUser = $repository->findOneBy(array('userName' => $entity['userName']));
                if ($existingUser) {
                    throw new \Exception('User already exist', 409);
                } else {
//                    $this->redirect('vacations/user/index');
                    $user = new \App\Vacation\Domain\Model\User();
                    $user->fromArray($entity);
                    $user->setUserName($entity['userName']);
                    if ($entity['password'] === $entity['repeatPassword'] && !empty($entity['password'])) {
                        $user->setPassword($entity['password']);
                    } else {
                        throw new \Exception('password and repeat password can not be empty and must have same value.',
                            406);
                    }

                    $this->getEm()->persist($user);
                    $this->getEm()->flush();
//
                    $success = true;
                    $message = 'User ' . $user->getUserName() . ' created successfully';

                    $this->getView()->assign('user', $user);

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
            if (!empty($message)) {
                $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            }
            if ($success) {
                $this->redirect('vacations/users/');
            } else {
                $this->redirect('vacations/users/new');
            }
        }
    }

    /**
     * @param int $id
     */
    public function editAction($id) {
        try {
            if ($id) {
                if (!$this->getLoggedInUser()->isAdmin()) {
                    throw new \Exception('Not authorized', 403);
                }
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                $user = $repository->findOneBy(array('id' => intval($id)));
                if (!$user) {
                    throw new \Exception('user not found');
                }
                $this->getView()->assign('user', $user);
                $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
                $this->getView()->render();
            } else {
                throw new \Exception('user id required');
            }
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash($e->getMessage(), 'danger');
            $this->redirect('vacations/users/');
        }
    }

    /**
     * @param $id
     * @param $entity
     * @param $format
     */
    public function updateAction($id, $entity, $format) {
        $success = false;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST' || $method === 'PATCH') {
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => intval($id)));
                if ($user) {
                    $user->fromArray($entity);
                    if (!empty($entity['password'])) {
                        if ($entity['password'] === $entity['repeatPassword']) {
                            $user->setPassword($entity['password']);
                        } else {
                            throw new \Exception('password and repeat password must have same value.', 406);
                        }
                    }
                    $this->getEm()->persist($user);
                    $this->getEm()->flush();
                    $success = true;
                    $message = 'User ' . $user->getUserName() . ' updated successfully';
                    $this->getView()->assign('user', $user);
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
            if (!empty($message)) {
                $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            }
            if ($success) {
                $this->redirect('vacations/users/');
            } else {
                $this->redirect('vacations/users/edit/' . $id);
            }
        }
    }

    /**
     * @param int $userId
     */
    public function deleteAction($id, $format) {
        $success = false;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST' || $method === 'DELETE') {
                if ($id) {
                    $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                    /** @var \App\Vacation\Domain\Model\User $user */
                    $user = $repository->findOneBy(array('id' => intval($id)));
                    if ($user) {
                        $userName = $user->getUserName();
                        $this->getEm()->remove($user);
                        $this->getEm()->flush();
                        $success = true;
                        $message = 'User ' . $userName . ' deleted successfully';
                    } else {
                        throw new \Exception('user not found', 400);
                    }
                } else {
                    throw new \Exception('user id required', 406);
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
            $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            $this->redirect('vacations/users/');
        }
    }

    /**
     * @throws \Exception
     */
    public function newVacationRequestForUserAction($id) {
        try {
            /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
            $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
            /** @var \App\Vacation\Domain\Model\User $user */
            $user = $repository->findOneBy(array('id' => intval($id)));
            if (intval($id) !== $this->getLoggedInUser()->getId() && !$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            } else {
                if ($user == null) {
                    throw new \Exception('user not found', 400);
                }
                $this->getView()->assign('user', $user);
            }
            $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
            $this->getView()->render();
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash($e->getMessage(), 'danger');
            $this->redirect('vacations/users/');
        }
    }

    /**
     * @param $id
     * @param $entity
     * @param $format
     */
    public function createVacationRequestAction($id, $entity, $format) {
        $success = false;
        $message = '';
        try {

            $method = $this->getKlein()->request()->method();
            if ($method === 'POST') {
                $vacationRequest = new \App\Vacation\Domain\Model\VacationRequest();
                $vacationRequest->fromArray($entity);

                if (intval($id) !== $this->getLoggedInUser()->getId() && !$this->getLoggedInUser()->isAdmin()) {
                    throw new \Exception('Not authorized', 403);
                }
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => intval($id)));
                if ($user == null) {
                    throw new \Exception('user not found', 400);
                }

                $vacationRequest = new \App\Vacation\Domain\Model\VacationRequest();
                $vacationRequest->fromArray($entity);

                $this->getEm()->persist($vacationRequest);
                if ($user) {
                    $vacationRequest->setUser($user);
                    $this->getEm()->persist($vacationRequest);
                    $user->addVacationRequest($vacationRequest);
                    $this->getEm()->persist($user);
                }
                $this->getEm()->flush();
                $success = true;
                $message = 'VacationRequest created successfully';

                $this->getView()->assign('vacationRequest', $vacationRequest);

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
            if (!empty($message)) {
                $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            }
            if ($success) {
                $this->redirect('vacations/users/show/' . $user->getId());
            } else {
                $this->redirect('vacations/users/newVacationRequestForUser/' . $user->getId());
            }
        }
    }


    /**
     * @param int $id
     */
    public function manageVacationRequestAction($id) {
        try {
            if (!$this->getLoggedInUser()->isUserWhoCanApproveVacation()) {
                throw new \Exception('Not authorized', 403);
            }
            if ($id) {
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                //The ORM internally escapes all your values, because it has lots of metadata available about the current context.
                $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                if (!$vacationRequest) {
                    throw new \Exception('vacationRequest not found');
                }
                $this->getView()->assign('vacationRequest', $vacationRequest);
                $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
                $this->getView()->render();
            } else {
                throw new \Exception('vacationRequest id required');
            }
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash($e->getMessage(), 'danger');
            $this->redirect('vacations/users/');
        }
    }

    /**
     * @param $id
     * @param $entity
     * @param $format
     */
    public function processVacationRequestAction($id, $entity, $format) {
        $success = false;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isUserWhoCanApproveVacation()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST') {
                /** @var \App\Vacation\Domain\Repository\VacationRequestRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                /** @var \App\Vacation\Domain\Model\VacationRequest $vacationRequest */
                $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => $vacationRequest->getUser()->getId()));
                if ($vacationRequest) {
                    $vacationRequest->fromArray($entity);
                    $this->getEm()->persist($vacationRequest);
                    if ($user) {
                        if (!$user->processVacationRequest($vacationRequest)) {
                            throw new \Exception('Not enough vacation days');
                        }
                        $vacationRequest->setUser($user);
                        $this->getEm()->persist($vacationRequest);
                        $user->removeVacationRequest($vacationRequest);
                        $user->addVacationRequest($vacationRequest);
                        $this->getEm()->persist($user);
                    }
                    $this->getEm()->flush();
                    $success = true;
                    $message = 'VacationRequest updated successfully';
                    $this->getView()->assign('vacationRequest', $vacationRequest);
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
            if (!empty($message)) {
                $this->getKlein()->service()->flash($message, $success ? 'success' : 'danger');
            }
            if ($success) {
                $this->redirect('vacations/users/');
            } else {
                $this->redirect('vacations/users/manageVacationRequest/' . $id);
            }
        }
    }
}
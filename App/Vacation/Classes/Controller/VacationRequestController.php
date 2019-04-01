<?php
namespace App\Vacation\Controller;

use \App\Core\Controller\AbstractController;

/**
 * Class VacationRequestController
 * @package App\Vacation\Controller
 */
class VacationRequestController extends AbstractController {

    /** @var NULL | \App\Vacation\Domain\Model\User */
    private $loggedInUser = NULL;

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
            $authenticationService->setAdapter(
                new \App\Vacation\Authentication\WebAdapter()
            );
        }
        if ($format == '.json') {
            $authenticationService->setAdapter(
                new \App\Vacation\Authentication\JwtAdapter($this->getConfiguration()->getConfig('jwtSecret'))
            );
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
                $this->getView()->assign('success', FALSE);
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
     * @param \App\Vacation\Domain\Model\User  $loggedInUser
     */
    public function setLoggedInUser(\App\Vacation\Domain\Model\User $loggedInUser) {
        $this->loggedInUser = $loggedInUser;
    }

    /**
     * @return void
     */
    public function indexAction($format) {
        $success = FALSE;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            /** @var \App\Vacation\Domain\Repository\VacationRequestRepository $repository */
            $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
            $vacationRequests = $repository->findAll();
            $this->getView()->assign('vacationRequests', $vacationRequests);
            $success = TRUE;
        } catch (\Exception $e) {
            $success = FALSE;
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
     * @param $id
     * @param $format
     */
    public function showAction($id, $format) {
        $success = FALSE;
        $message = '';
        try {
            if ($id) {
                if (intval($id) !== $this->getLoggedInUser()->getId() && !$this->getLoggedInUser()->isAdmin()) {
                    throw new \Exception('Not authorized', 403);
                }
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                if (!$vacationRequest) {
                    throw new \Exception('vacationRequest not found');
                }
                $this->getView()->assign('vacationRequest', $vacationRequest);
                $success = TRUE;
            } else {
                throw new \Exception('vacationRequest id required');
            }
        } catch (\Exception $e) {
            $success = FALSE;
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
                $this->redirect('vacations/vacationRequests/');
            }
        }
    }

    /**
     * @throws \Exception
     */
    public function newAction() {
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
            $users = $repository->findAll();
            $this->getView()->assign('userOptions', $users);
            $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
            $this->getView()->render();
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash( $e->getMessage(), 'danger');
            $this->redirect('vacations/vacationRequests/');
        }
    }

    /**
     * @param $entity
     * @param $format
     */
    public function createAction($entity, $format) {
        $success = FALSE;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST') {
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);

                $userId = isset($entity['user']) ? intval($entity['user']) : 0;
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => $userId));
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
//
                $success = TRUE;
                $message = 'VacationRequest created successfully';

                $this->getView()->assign('vacationRequest', $vacationRequest);

            } else {
                throw new \Exception('Wrong Method : ' . $method, 405);
            }
        } catch (\Exception $e) {
            $success = FALSE;
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
                if ($format == '.html') {
                    $this->redirect('vacations/vacationRequests/');
                }

            } else {
                $this->redirect('vacations/vacationRequests/new');
            }
        }
    }

    /**
     * @param int $id
     */
    public function editAction($id) {
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            if ($id) {
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                if (!$vacationRequest) {
                    throw new \Exception('vacationRequest not found');
                }
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                $users = $repository->findAll();
                $this->getView()->assign('userOptions', $users);
                $this->getView()->assign('vacationRequest', $vacationRequest);
                $this->getView()->assign('flashes', $this->getKlein()->service()->flashes());
                $this->getView()->render();
            } else {
                throw new \Exception('vacationRequest id required');
            }
        } catch (\Exception $e) {
            $this->getKlein()->service()->flash($e->getMessage(), 'danger');
            $this->redirect('vacations/vacationRequests/');
        }
    }

    /**
     * @param $id
     * @param $entity
     * @param $format
     */
    public function updateAction($id, $entity, $format) {
        $success = FALSE;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST' || $method === 'PATCH') {
                /** @var \App\Vacation\Domain\Repository\VacationRequestRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                /** @var \App\Vacation\Domain\Model\VacationRequest $vacationRequest */
                $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                /** @var \App\Vacation\Domain\Repository\UserRepository $repository */
                $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\User::class);
                /** @var \App\Vacation\Domain\Model\User $user */
                $user = $repository->findOneBy(array('id' => intval($entity['user'])));
                if ($vacationRequest) {
                    $vacationRequest->fromArray($entity);

                    $this->getEm()->persist($vacationRequest);
                    if ($user) {
                        $vacationRequest->setUser($user);
                        $this->getEm()->persist($vacationRequest);
                        $user->removeVacationRequest($vacationRequest);
                        $user->addVacationRequest($vacationRequest);
                        $this->getEm()->persist($vacationRequest);
                        $this->getEm()->persist($user);
                    }
                    $this->getEm()->flush();
                    $success = TRUE;
                    $message = 'VacationRequest updated successfully';
                    $this->getView()->assign('vacationRequest', $vacationRequest);
                }
            } else {
                throw new \Exception('Wrong Method : ' . $method, 405);
            }
        } catch (\Exception $e) {
            $success = FALSE;
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
                $this->redirect('vacations/vacationRequests/');
            } else {
                $this->redirect('vacations/vacationRequests/edit/' . $id);
            }
        }
    }

    /**
     * @param $id
     * @param $format
     */
    public function deleteAction($id, $format) {
        $success = FALSE;
        $message = '';
        try {
            if (!$this->getLoggedInUser()->isAdmin()) {
                throw new \Exception('Not authorized', 403);
            }
            $method = $this->getKlein()->request()->method();
            if ($method === 'POST' || $method === 'DELETE') {
                if ($id) {
                    $repository = $this->getEm()->getRepository(\App\Vacation\Domain\Model\VacationRequest::class);
                    /** @var \App\Vacation\Domain\Model\VacationRequest $vacationRequest */
                    $vacationRequest = $repository->findOneBy(array('id' => intval($id)));
                    if ($vacationRequest) {
                        $this->getEm()->remove($vacationRequest);
                        $this->getEm()->flush();
                        $success = TRUE;
                        $message = 'VacationRequest deleted successfully';
                    } else {
                        throw new \Exception('vacationRequest not found', 400);
                    }
                } else {
                    throw new \Exception('vacationRequest id required', 406);
                }
            } else {
                throw new \Exception('Wrong Method : ' . $method, 405);
            }
        } catch (\Exception $e) {
            $success = FALSE;
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
            $this->redirect('vacations/vacationRequests/');
        }
    }
}
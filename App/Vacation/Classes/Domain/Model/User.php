<?php

namespace App\Vacation\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="App\Vacation\Domain\Repository\UserRepository")
 * @Table(name="users")
 * @UniqueEntity("email")
 * @UniqueEntity("userName")
 */
class User implements \JsonSerializable {

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $firstName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $lastName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $email;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $userName;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $password;

    /**
     * @Column(type="boolean")
     * @var bool
     */
    protected $admin = false;

    /**
     * @Column(type="boolean")
     * @var bool
     */
    protected $userWhoCanApproveVacation = false;

    /**
     * @Column(type="integer")
     * @var int
     */
    protected $vacationDays;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection
     * @OneToMany(targetEntity="App\Vacation\Domain\Model\VacationRequest", mappedBy="user", cascade={"all"})
     */
    private $vacationRequests;


    public function __construct() {
        $this->vacationRequests = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getFirstName() {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName) {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getLastName() {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName) {
        $this->lastName = $lastName;
    }

    /**
     * @return string
     */
    public function getUserName() {
        return $this->userName;
    }

    /**
     * @param string $userName
     */
    public function setUserName($userName) {
        $this->userName = $userName;
    }

    /**
     * @return string
     */
    public function getPassword() {
        return $this->password;
    }

    /**
     * @param $password
     * @return bool
     */
    public function validatePassword($password) {
        return password_verify($password, $this->password);
    }

    /**
     * @param string $password
     */
    public function setPassword($password) {
        $options = [
            'cost' => 12
        ];
        $this->password = password_hash($password, PASSWORD_BCRYPT, $options);
    }

    /**
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * @return bool
     */
    public function isAdmin() {
        return $this->admin;
    }

    /**
     * @param bool $admin
     */
    public function setAdmin($admin) {
        $this->admin = $admin;
    }

    /**
     * @return bool
     */
    public function isUserWhoCanApproveVacation() {
        return $this->userWhoCanApproveVacation;
    }

    /**
     * @param bool $userWhoCanApproveVacation
     */
    public function setUserWhoCanApproveVacation($userWhoCanApproveVacation) {
        $this->userWhoCanApproveVacation = $userWhoCanApproveVacation;
    }

    /**
     * @return int
     */
    public function getVacationDays() {
        return $this->vacationDays;
    }

    /**
     * @param int $vacationDays
     */
    public function setVacationDays($vacationDays) {
        $this->vacationDays = $vacationDays;
    }

    /**
     * @return mixed
     */
    public function getVacationRequests() {
        if ($this->vacationRequests == null) {
            $this->vacationRequests = new ArrayCollection();
        }
        return $this->vacationRequests;
    }

    /**
     * @param VacationRequest $vacationRequest
     * @return $this
     */
    public function addVacationRequest(VacationRequest $vacationRequest) {
        if (!$this->getVacationRequests()->contains($vacationRequest)) {
            $this->getVacationRequests()->add($vacationRequest);
        }

        return $this;
    }

    /**
     * @param VacationRequest $vacationRequest
     * @return $this
     */
    public function removeVacationRequest(VacationRequest $vacationRequest) {
        if ($this->getVacationRequests()->contains($vacationRequest)) {
            $this->getVacationRequests()->removeElement($vacationRequest);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPendingVacationRequest() {
        return $this->getVacationRequests() ? $this->getVacationRequests()->filter(
            function(VacationRequest $vacationRequest) {
                return $vacationRequest->getState() === VacationRequest::STATE_PENDING;
            }
        ) : array();
    }
    /**
     * @return mixed
     */
    public function getApprovedVacationRequest() {
        return $this->getVacationRequests() ? $this->getVacationRequests()->filter(
            function(VacationRequest $vacationRequest) {
                return $vacationRequest->getState() === VacationRequest::STATE_APPROVED;
            }
        ) : array();
    }
    /**
     * @return mixed
     */
    public function getDeniedVacationRequest() {
        return $this->getVacationRequests() ? $this->getVacationRequests()->filter(
            function(VacationRequest $vacationRequest) {
                return $vacationRequest->getState() === VacationRequest::STATE_DENIED;
            }
        ) : array();
    }

    /**
     * @param VacationRequest $vacationRequest
     * @return bool
     * @throws \Exception
     */
    public function processVacationRequest(VacationRequest $vacationRequest) {
        $numberOfWorkingDays = $this->numberOfWorkingDays($vacationRequest->getStartDate(), $vacationRequest->getEndDate());
        if ($vacationRequest->getState() == VacationRequest::STATE_APPROVED) {
            if ( $this->getVacationDays() - $numberOfWorkingDays >= 0) {
                $this->setVacationDays($this->getVacationDays() - $numberOfWorkingDays);
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }

    /**
     * @param $from
     * @param $to
     * @return int
     */
    function numberOfWorkingDays($from, $to) {
        $workingDays = [1, 2, 3, 4, 5]; # date format = N (1 = Monday, ...)
        $holidayDays = ['*-12-25', '*-01-01', '2013-12-23']; # variable and fixed holidays

//        $from = new \DateTime($from);
//        $to = new \DateTime($to);
        $to->modify('+1 day');
        $interval = new \DateInterval('P1D');
        $periods = new \DatePeriod($from, $interval, $to);

        $days = 0;
        foreach ($periods as $period) {
            if (!in_array($period->format('N'), $workingDays)) continue;
            if (in_array($period->format('Y-m-d'), $holidayDays)) continue;
            if (in_array($period->format('*-m-d'), $holidayDays)) continue;
            $days++;
        }
        return $days;
    }

    /**
     * @param $data
     */
    public function fromArray($data) {
        if (is_array($data)) {
            $data['admin'] = !empty($data['admin']) ? $data['admin'] : false;
            $data['userWhoCanApproveVacation'] = !empty($data['userWhoCanApproveVacation']) ? $data['userWhoCanApproveVacation'] : false;
            foreach ($data as $key => $val) {
                if ($key == 'id' || $key == 'userName' || $key == 'password') {
                    continue;
                }
                $methodName = 'set' . ucfirst($key);
                if (method_exists($this, $methodName) && is_callable(array($this, $methodName))) {
                    $this->$methodName($val);
                }
            }

        }
    }

    public function jsonSerialize() {
        return [
            'id' => $this->getId(),
            'userName' => $this->getUserName(),
            'firstName' => $this->getFirstName(),
            'lastName' => $this->getLastName(),
            'email' => $this->getEmail(),
            'admin' => $this->isAdmin(),
            'userWhoCanApproveVacation' => $this->isUserWhoCanApproveVacation(),
            'vacationDays' => $this->getVacationDays(),
            'vacationRequests' => $this->getVacationRequests()->toArray()
        ];
    }

}
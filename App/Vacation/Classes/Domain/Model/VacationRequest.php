<?php

namespace App\Vacation\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity(repositoryClass="App\Vacation\Domain\Repository\VacationRequestRepository")
 * @Table(name="vacation_requests")
 */
class VacationRequest implements \JsonSerializable {

    const STATE_PENDING = 0;
    const STATE_APPROVED = 1;
    const STATE_DENIED = 2;

    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    protected $id;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    private $startDate;

    /**
     * @Column(type="datetime")
     * @var \DateTime
     */
    private $endDate;

    /**
     * @Column(type="string")
     * @var string
     */
    private $startDateTimezone;

    /**
     * @Column(type="string")
     * @var string
     */
    private $endDateTimezone;

    /**
     * @ManyToOne(targetEntity="App\Vacation\Domain\Model\User", inversedBy="vacationRequests", cascade={"all"})
     * @JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @Column(type="integer", options={"default" : 0})
     * @var int
     */
    private $state = self::STATE_PENDING;

    /**
     * VacationRequest constructor.
     */
    public function __construct() {

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
    public function getStartDateTimezone() {
        return $this->startDateTimezone;
    }

    /**
     * @param string $startDateTimezone
     */
    public function setStartDateTimezone($startDateTimezone) {
        $this->startDateTimezone = $startDateTimezone;
    }

    /**
     * @return string
     */
    public function getEndDateTimezone() {
        return $this->endDateTimezone;
    }

    /**
     * @param string $endDateTimezone
     */
    public function setEndDateTimezone($endDateTimezone) {
        $this->endDateTimezone = $endDateTimezone;
    }

    /**
     * @return User|NULL
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param User|NULL $user
     */
    public function setUser($user) {
        $this->user = $user;
    }

    public function getStartDate() {
        return $this->startDate;
    }

    /**
     * @return string
     */
    public function getStartDateString() {
        return $this->startDate->format(\DateTime::ATOM);
    }

    /**
     * @param \DateTime $startDate
     */
    public function setStartDate($startDate) {
        if (is_string($startDate)) {
            $startDate = new \DateTime($startDate);
        }

        $this->startDateTimezone = $startDate->getTimeZone()->getName();
        $this->startDate = $startDate;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate() {
        return $this->endDate;
    }

    /**
     * @return string
     */
    public function getEndDateString() {
        return $this->endDate->format(\DateTime::ATOM);
    }

    /**
     * @param \DateTime $endDate
     */
    public function setEndDate($endDate) {
        if (is_string($endDate)) {
            $endDate = new \DateTime($endDate);
        }
        $this->endDateTimezone = $endDate->getTimeZone()->getName();
        $this->endDate = $endDate;
    }

    /**
     * @return int
     */
    public function getState() {
        return $this->state;
    }

    /**
     * @param int $state
     */
    public function setState($state) {
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getStateString() {
        $states = array(
            self::STATE_PENDING => 'Pending',
            self::STATE_APPROVED => 'Approved',
            self::STATE_DENIED => 'Denied',
        );
        return $states[$this->state];
    }

    /**
     * @param $data
     */
    public function fromArray($data) {
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if ($key == 'id' || $key == 'user') {
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
            'startDateTimezone' => $this->getStartDateTimezone(),
            'endDateTimezone' => $this->getEndDateTimezone(),
            'startDate' => $this->getStartDateString(),
            'endDate' => $this->getStartDateString(),
            'user' => $this->getUser()? $this->getUser()->getId() : NULL,
            'state' => $this->getState(),
        ];
    }
}
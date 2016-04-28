<?php

namespace Overwatch\ResultBundle\Entity;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Overwatch\ResultBundle\Enum\ResultStatus;
use Overwatch\TestBundle\Entity\Test;

/**
 * TestResult
 *
 * @ORM\Table()
 * @ORM\Entity(readOnly=true,repositoryClass="Overwatch\ResultBundle\Entity\TestResultRepository")
 * @ORM\HasLifecycleCallbacks
 */
class TestResult implements \JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Overwatch\TestBundle\Entity\Test", inversedBy="results")
     * @ORM\JoinColumn(name="test_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $test;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=15)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="info", type="string", length=100)
     */
    private $info;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;

    /**
     * Serialise object to JSON
     */
    public function jsonSerialize()
    {
        return [
            'id'        => $this->getId(),
            'status'    => $this->getStatus(),
            'info'      => $this->getInfo(),
            'createdAt' => $this->getCreatedAt()->getTimestamp()
        ];
    }

    /**
     * Serialise object to string
     */
    public function __toString()
    {
        $test = $this->getTest();

        return sprintf(
            '[%s] %s %s (Expect %s %s %s - %s)',
            $this->getCreatedAt()->format('Y-m-d H:i:s'),
            $test->getName(),
            strtoupper($this->getStatus()),
            $test->getActual(),
            $test->getExpectation(),
            $test->getExpected(),
            $this->getInfo()
        );
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set result
     *
     * @param string $status
     * @return TestResult
     */
    public function setStatus($status)
    {
        ResultStatus::isValid($status);
        $this->status = $status;

        return $this;
    }

    /**
     * Get result
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Was the result unsucessful?
     *
     * @return bool
     */
    public function isUnsuccessful()
    {
        return in_array($this->getStatus(), [ResultStatus::ERROR, ResultStatus::FAILED]);
    }

    /**
     * Set createdAt
     *
     * @ORM\PrePersist
     * @return TestResult
     */
    public function setCreatedAt($timestamp = 'now')
    {
        if ($this->createdAt === null) {
            if ($timestamp instanceof LifecycleEventArgs) {
                $timestamp = 'now';
            }

            $this->createdAt = new \DateTime($timestamp);
        }

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set test
     *
     * @param \Overwatch\TestBundle\Entity\Test $test
     * @return TestResult
     */
    public function setTest(Test $test)
    {
        $this->test = $test;
        $test->setLastResult($this);

        return $this;
    }

    /**
     * Get test
     *
     * @return \Overwatch\TestBundle\Entity\Test
     */
    public function getTest()
    {
        return $this->test;
    }

    /**
     * Set info
     *
     * @param string $info
     * @return TestResult
     */
    public function setInfo($info)
    {
        if ($info instanceof \Exception) {
            $info = $info->getMessage();
        }

        $this->info = $info;

        return $this;
    }

    /**
     * Get info
     *
     * @return string
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * Is this test result a change from the previous one?
     *
     * @return boolean
     */
    public function isAChange()
    {
        if ($this->getTest() === null) {
            return true;
        }

        $results = $this->getTest()->getResults();
        $lastResult = $results->get($results->count() - 2);

        if ($lastResult === null) {
            return true;
        }

        return ($this->getStatus() !== $lastResult->getStatus());
    }
}

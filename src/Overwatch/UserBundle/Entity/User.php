<?php

namespace Overwatch\UserBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;

/**
 * User
 * 
 * @ORM\Entity
 * @ORM\Table(name="User")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\ManyToMany(targetEntity="Overwatch\TestBundle\Entity\TestGroup", inversedBy="users")
     * @ORM\JoinTable(name="UsersTestGroups")
     */
    protected $groups;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Set Email (and username)
     * 
     * @param string $email
     * @return User
     */
    public function setEmail($email) {
        parent::setEmail($email);
        parent::setUsername($email);
        return $this;
    }
}

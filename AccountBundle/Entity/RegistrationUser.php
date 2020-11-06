<?php

namespace Postroyka\AccountBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Submarine\UsersBundle\Entity\UserAuthAccount;

class RegistrationUser
{
    /**
     * @var string
     * @Assert\NotBlank()
     */
    private $firstName;

    /**
     * @var string
     * @Assert\NotBlank()
     */
    private $secondName;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    private $email;

    /**
     * @var string
     * @Assert\NotBlank()
     * @Assert\Length(min="6")
     */
    private $password;

    /**
     * @var UserAuthAccount[]|ArrayCollection
     */
    private $authAccount;

    public function __construct()
    {
        $this->authAccount = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * @return string
     */
    public function getSecondName()
    {
        return $this->secondName;
    }

    /**
     * @param string $secondName
     */
    public function setSecondName($secondName)
    {
        $this->secondName = $secondName;
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->firstName . ' ' . $this->secondName;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * @return ArrayCollection|UserAuthAccount[]
     */
    public function getAuthAccount()
    {
        return $this->authAccount;
    }

    /**
     * @param ArrayCollection|UserAuthAccount[] $authAccount
     */
    public function setAuthAccount($authAccount)
    {
        $this->authAccount = $authAccount;
    }
}
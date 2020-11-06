<?php

namespace Postroyka\AccountBundle\Entity;

use Submarine\CartBundle\Cart\CartInterface;
use Submarine\CoreBundle\Entity\Options\Option;
use Submarine\CoreBundle\Entity\SubmarineEntityInterface;
use Submarine\UsersBundle\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity()
 * @ORM\Table(name="postroyka_extended_users")
 * @ORM\HasLifecycleCallbacks()
 */
class ExtendedUser implements SubmarineEntityInterface
{
    /**
     * @var int
     * @ORM\Column(name="id", type="integer", unique=true, nullable=false)
     * @ORM\Id()
     */
    private $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="Submarine\UsersBundle\Entity\User", cascade={"REMOVE"})
     * @Assert\NotNull()
     */
    private $user;

    /**
     * @var string
     * @ORM\Column(name="first_name", type="string")
     */
    private $firstName;

    /**
     * @var string
     * @ORM\Column(name="second_name", type="string")
     */
    private $secondName;

    /**
     * @var string
     * @ORM\Column(name="company", type="string", nullable=true)
     */
    private $company;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", nullable=true)
     */
    private $phone;

    /**
     * @var Option
     * @ORM\ManyToOne(targetEntity="Submarine\CoreBundle\Entity\Options\Option")
     * @ORM\JoinColumn(name="discount_card", referencedColumnName="name")
     */
    private $discountCard;

    /**
     * @var int
     * @ORM\Column(name="discount_card_number", type="integer", nullable=true)
     */
    private $discountCardNumber;

    /**
     * @var \DateTime
     * @ORM\Column(name="updated_at", type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * @var CartInterface
     * @ORM\Column(name="cart", type="text", nullable=true)
     */
    private $cart;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->id = $user->getId();
        $this->updatedAt = new \DateTime();
    }

    /**
     * Имя сущности
     * @return string
     */
    static public function entityName()
    {
        return __CLASS__;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->user->getEmail();
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;
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
        $this->user->setUsername($this->firstName . ' ' . $this->secondName);
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
        $this->user->setUsername($this->firstName . ' ' . $this->secondName);
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->secondName . ' ' . $this->firstName;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     */
    public function setCompany($company)
    {
        $this->company = $company;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return Option
     */
    public function getDiscountCard()
    {
        return $this->discountCard;
    }

    /**
     * @param Option $discountCard
     */
    public function setDiscountCard(Option $discountCard = null)
    {
        $this->discountCard = $discountCard;
    }

    /**
     * @return float
     */
    public function getDiscount()
    {
        if ($this->discountCard) {
            $discount = $this->discountCard->getValue();
            $discount = str_replace([' ', ','], ['', '.'], $discount);

            return (float)$discount;
        }

        return 0;
    }

    /**
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @ORM\PreUpdate()
     */
    public function setUpdatedAt()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @return CartInterface
     */
    public function getCart()
    {
        return $this->cart ? unserialize($this->cart) : null;
    }

    /**
     * @param CartInterface $cart
     */
    public function setCart(CartInterface $cart = null)
    {
        $this->cart = $cart ? serialize($cart) : null;
    }

    /**
     * @return int
     */
    public function getDiscountCardNumber()
    {
        return $this->discountCardNumber;
    }

    /**
     * @param int $discountCardNumber
     */
    public function setDiscountCardNumber($discountCardNumber)
    {
        $this->discountCardNumber = (int)$discountCardNumber;
    }
}
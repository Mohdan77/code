<?php

namespace Postroyka\AccountBundle\EventListener;

use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\AppBundle\Provider\CartProvider;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class CartListener
{
    /**
     * @var CartProvider
     */
    private $cartProvider;

    /**
     * @var ExtendedUserProvider
     */
    private $extendedUserProvider;

    /**
     * @param CartProvider $cartProvider
     * @param ExtendedUserProvider $extendedUserProvider
     */
    public function __construct(CartProvider $cartProvider, ExtendedUserProvider $extendedUserProvider)
    {
        $this->cartProvider = $cartProvider;
        $this->extendedUserProvider = $extendedUserProvider;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $extendedUser = $this->extendedUserProvider->getByUser($user);

        if ($extendedUser->getId()) {
            $cart = $extendedUser->getCart();

            if ($cart && !$cart->isEmpty()) {
                $this->cartProvider->saveCart($user, $cart);
            } else {
                $cart = $this->cartProvider->getCart($user);

                // Принудительное обновление корзины для пересчета скидки
                $cart->setUpdatedAt(new \DateTime('01.01.2000'));

                $this->cartProvider->saveCart($user, $cart);
            }
        }
    }
}
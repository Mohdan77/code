<?php

namespace Postroyka\AppBundle\Provider;

use Postroyka\AccountBundle\Manager\ExtendedUserManager;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\AppBundle\Service\MinPricePercentDiscountStrategy;
use Postroyka\AppBundle\Service\OrderCalculator;
use Postroyka\AppBundle\Service\PriceService;
use Submarine\CartBundle\Cart\CartInterface;
use Submarine\CartBundle\DiscountStrategy\DiscountManager;
use Submarine\CartBundle\Entity\CartItem;
use Submarine\CartBundle\Entity\Option;
use Submarine\CartBundle\Provider\SessionCartProvider;
use Submarine\CoreBundle\Options\OptionsCacheProvider;
use Submarine\CoreBundle\Options\OptionsProviderInterface;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Provider\PagesProvider;
use Symfony\Component\Security\Core\User\UserInterface;

class CartProvider extends SessionCartProvider
{
    const PRICE_UPDATED_AT = 'postroyka_manager.price_updated_at';

    /**
     * @var ExtendedUserProvider
     */
    private $extendedUserProvider;

    /**
     * @var ExtendedUserManager
     */
    private $extendedUserManager;

    /**
     * @var OptionsCacheProvider
     */
    private $optionsProvider;

    /**
     * @var PagesProvider
     */
    private $pageProvider;

    /**
     * @var PriceService
     */
    private $priceService;

    /**
     * @var DiscountManager
     */
    private $discountManger;

    /**
     * @param $extendedUserProvider
     */
    public function setExtendedUserProvider(ExtendedUserProvider $extendedUserProvider)
    {
        $this->extendedUserProvider = $extendedUserProvider;
    }

    /**
     * @param ExtendedUserManager $extendedUserManager
     */
    public function setExtendedUserManager(ExtendedUserManager $extendedUserManager)
    {
        $this->extendedUserManager = $extendedUserManager;
    }

    /**
     * @param OptionsProviderInterface $optionsProvider
     */
    public function setOptionsProvider(OptionsProviderInterface $optionsProvider)
    {
        $this->optionsProvider = $optionsProvider;
    }

    /**
     * @param PagesProvider $pageProvider
     */
    public function setPageProvider(PagesProvider $pageProvider)
    {
        $this->pageProvider = $pageProvider;
    }

    /**
     * @param PriceService $priceService
     */
    public function setPriceService(PriceService $priceService)
    {
        $this->priceService = $priceService;
    }

    /**
     * @param DiscountManager $discountManger
     */
    public function setDiscountManger(DiscountManager $discountManger)
    {
        $this->discountManger = $discountManger;
    }

    public function getCart(UserInterface $user = null)
    {
        $cart = parent::getCart($user);

        $extendedUser = $this->extendedUserProvider->getByUser($user);
        $discountUpdated = false;
        $priceUpdated = false;

        if ($extendedUser->getId()) {
            if ($extendedUser->getUpdatedAt() > $cart->getUpdatedAt()) {
                $discountUpdated = true;
            }
        }

        try {
            $priceUpdatedAt = new \DateTime($this->optionsProvider->getValue(self::PRICE_UPDATED_AT));
        } catch (\Exception $e) {
            $priceUpdatedAt = new \DateTime();
        }

        if ($priceUpdatedAt > $cart->getUpdatedAt()) {
            $priceUpdated = true;
        }

        if ($discountUpdated || $priceUpdated) {
            foreach ($cart->getItems() as $item) {
                $product = $this->pageProvider->getByUrl($item->getUrl());

                if ($product->getId()) {
                    $item->setUnitPrice($product->getPrice());
                    $discount = $this->priceService->getUnitDiscount($product, $extendedUser, $item->getQuantity());
                    $item->setUnitDiscount($discount);
                    $item->getOptions()->remove('base_price');
                    $item->addOption(new Option('base_price', $product->getPrice(), 'Цена'));
                } else {
                    $cart->removeItem($item->getKey());
                }
            }

            $this->saveCart($user, $cart);
        }

        return $cart;
    }

    public function saveCart(UserInterface $user = null, CartInterface $cart = null)
    {
        $extendedUser = $this->extendedUserProvider->getByUser($user);

        // @todo-dev Оптимизировать!!!
        if ($cart !== null && !$cart->isEmpty()) {
            // Пересчет корзины для общей скидки
            foreach ($cart->getItems() as $item) {
                $product = $this->pageProvider->getByUrl($item->getUrl());

                if ($product->getId()) {
                    $discount = $this->priceService->getUnitDiscount($product, $extendedUser, $item->getQuantity());
                    $item->setUnitDiscount($discount);
                } else {
                    $cart->removeItem($item->getKey());
                }
            }

            $cart->calculate();

            // Общая скидка от суммы
            $minPrice = $this->optionsProvider->getValue(OrderCalculator::DISCOUNT_LIMIT);
            $discountPercent = $this->optionsProvider->getValue(OrderCalculator::DISCOUNT_PERCENT);
            $strategy = new MinPricePercentDiscountStrategy($minPrice, $discountPercent);
            $this->discountManger->addStrategy($strategy);
            $this->discountManger->calculate($cart);
        }

        if ($extendedUser->getId()) {
            $extendedUser->setCart(($cart === null || $cart->isEmpty()) ? null : $cart);
            $this->extendedUserManager->save($extendedUser);
        }

        parent::saveCart($user, $cart);
    }

    public function clearCart(UserInterface $user = null)
    {
        $extendedUser = $this->extendedUserProvider->getByUser($user);

        if ($extendedUser->getId()) {
            $extendedUser->setCart(null);
            $this->extendedUserManager->save($extendedUser);
        }

        parent::clearCart($user);
    }

    /**
     * Добавление товара в корзину
     * @param UserInterface|null $user
     * @param Page $product
     * @param int $quantity
     */
    public function addToCart(UserInterface $user = null, Page $product, $quantity = 1)
    {
        $cart = $this->getCart($user);
        $extendedUser = $this->extendedUserProvider->getByUser($user);

        $item = new CartItem($product, $quantity);
        $item->setImage($product->getImage());
        $item->setUrl($product->getUrl());

        $discount = $this->priceService->getUnitDiscount($product, $extendedUser, $item->getQuantity());
        $item->setUnitDiscount($discount);

        if ($product->getValue('unloading_type')) {
            $item->addOption(new Option('unloading_type', $product->getValue('unloading_type'), 'Тип'));
        }

        if ($product->getValue('weight')) {
            $item->addOption(new Option('weight', $product->getValue('weight'), 'Вес, кг'));
        }

        $item->addOption(new Option('base_price', $product->getPrice(), 'Цена'));

        $cart->addItem($item);

        $this->saveCart($user, $cart);
    }

    /**
     * Подсчет общего веса товаров в корзине
     * @param CartInterface $cart
     * @return float
     */
    public function getTotalWeight(CartInterface $cart)
    {
        $totalWeight = 0;

        foreach ($cart->getItems() as $item) {
            if ($item->hasOption('weight')) {
                $weight = $item->getOptions()['weight']->getValue();
                $weight = str_replace([' ', ','], ['', '.'], $weight);
                $totalWeight += (float)$weight * $item->getQuantity();
            }
        }

        return $totalWeight;
    }
}
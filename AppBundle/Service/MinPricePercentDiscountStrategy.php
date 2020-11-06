<?php

namespace Postroyka\AppBundle\Service;

use Submarine\CartBundle\DiscountStrategy\DiscountStrategyInterface;
use Submarine\CartBundle\Cart\CartInterface;
use Submarine\CartBundle\Cart\CartItemInterface;

/**
 * Стратегия скидок: при отсутствии скидок при достигнутой сумме давать процент скидки
 */
class MinPricePercentDiscountStrategy implements DiscountStrategyInterface
{
    /**
     * @var CartInterface
     */
    private $cart;

    /**
     * Минимальная стоимость
     *
     * @var int
     */
    private $minPrice;

    /**
     * Процент скидки
     * @var int
     */
    private $discountPercent = 0;

    /**
     * MinPricePercentDiscountStrategy constructor.
     * @param int $minPrice Минимальная стоимость
     * @param int $discountPercent Процент скидки
     */
    public function __construct($minPrice, $discountPercent)
    {
        $this->minPrice = (float)$minPrice;
        $this->discountPercent = (float)$discountPercent;
    }

    public function getName()
    {
        return 'min_price_percent';
    }

    public function handleCart(CartInterface $cart)
    {
        $this->cart = $cart;
    }

    public function getUnitDiscountPrice(CartItemInterface $item)
    {
        if ($this->cart->getTotalDiscount() === 0.0 && $this->cart->getTotal() >= $this->minPrice) {
            return $item->getUnitPrice() * $this->discountPercent / 100;
        }

        return $item->getUnitDiscount();
    }
}

<?php

namespace Postroyka\AppBundle\Service;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Submarine\PagesBundle\Entity\Page;

class PriceService
{
    /**
     * Скидка на еденицу товара
     * @param Page $product
     * @param ExtendedUser $extendedUser
     * @param int $quantity
     * @return float
     */
    public function getUnitDiscount(Page $product, ExtendedUser $extendedUser, $quantity)
    {
        $price = $product->getPrice();
        $discount = 0;

        if ($this->hasWholesalePrice($product, $extendedUser)) {
            if ($quantity >= $product->getValue('wholesale_quantity')) {
                $wholesaleDiscount = $price * $product->getValue('wholesale_discount') / 100;
                $discount += $wholesaleDiscount;
                $price -= $wholesaleDiscount;
            }
        }

        if ($extendedUser && $extendedUser->getId() && $extendedUser->getDiscount()) {
            if ($product->getValue('disable_discount')) {
                $discount += $price * OrderCalculator::FIXED_DISCOUNT / 100;
            } else {
                $discount += $price * $extendedUser->getDiscount() / 100;
            }
        }

        return round($discount, 2);
    }

    /**
     * Рассчет базовой цены со скидочной картой
     * @param float $price
     * @param Page $product
     * @param ExtendedUser|null $extendedUser
     * @return float
     */
    private function calculateRetailPrice($price, Page $product, ExtendedUser $extendedUser)
    {
        if ($extendedUser && $extendedUser->getId() && $extendedUser->getDiscount()) {
            if ($product->getValue('disable_discount')) {
                $price -= $price * OrderCalculator::FIXED_DISCOUNT / 100;
            } else {
                $price -= $price * $extendedUser->getDiscount() / 100;
            }
        }

        return round($price, 2);
    }

    /**
     * Базовая цена со скидочной картой
     * @param Page $product
     * @param ExtendedUser $extendedUser
     * @return float
     */
    public function getRetailPrice(Page $product, ExtendedUser $extendedUser)
    {
        $price = $product->getPrice();

        return $this->calculateRetailPrice($price, $product, $extendedUser);
    }

    /**
     * Старая базовая цена со скидочной картой
     * @param Page $product
     * @param ExtendedUser $extendedUser
     * @return float
     */
    public function getOldRetailPrice(Page $product, ExtendedUser $extendedUser)
    {
        $price = $product->getValue('old_price');
        $price = (float)str_replace([',', ' '], ['.', ''], $price);

        return $this->calculateRetailPrice($price, $product, $extendedUser);
    }

    /**
     * Оптовая цена
     * @param Page $product
     * @param ExtendedUser $extendedUser
     * @return float
     */
    public function getWholesalePrice(Page $product, ExtendedUser $extendedUser)
    {
        if ($this->hasWholesalePrice($product, $extendedUser)) {
            $price = $product->getPrice();
            $price -= $price * $product->getValue('wholesale_discount') / 100;

            return $this->calculateRetailPrice($price, $product, $extendedUser);
        }

        return $this->getRetailPrice($product, $extendedUser);
    }

    /**
     * Есть ли оптовая цена?
     * @param Page $product
     * @param ExtendedUser $extendedUser
     * @return bool
     */
    public function hasWholesalePrice(Page $product, ExtendedUser $extendedUser)
    {
        return $product->getValue('wholesale_discount') && $product->getValue('wholesale_quantity');
    }
}

<?php

namespace Postroyka\AppBundle\Service;

use Postroyka\AppBundle\Provider\CartProvider;
use Submarine\CartBundle\Cart\CartInterface;
use Submarine\CartBundle\Cart\CartItemInterface;
use Submarine\CoreBundle\Options\OptionsProviderInterface;

class OrderCalculator
{
    const DELIVERY_ZONES_NUMBER = 18;

    const DELIVERY_ZONE_1 = 'postroyka_manager.delivery_zone_1';
    const DELIVERY_ZONE_2 = 'postroyka_manager.delivery_zone_2';
    const DELIVERY_ZONE_3 = 'postroyka_manager.delivery_zone_3';
    const DELIVERY_ZONE_4 = 'postroyka_manager.delivery_zone_4';
    const DELIVERY_ZONE_5 = 'postroyka_manager.delivery_zone_5';
    const DELIVERY_ZONE_6 = 'postroyka_manager.delivery_zone_6';
    const DELIVERY_ZONE_7 = 'postroyka_manager.delivery_zone_7';
    const DELIVERY_ZONE_8 = 'postroyka_manager.delivery_zone_8';
    const DELIVERY_ZONE_9 = 'postroyka_manager.delivery_zone_9';
    const DELIVERY_ZONE_10 = 'postroyka_manager.delivery_zone_10';
    const DELIVERY_ZONE_11 = 'postroyka_manager.delivery_zone_11';
    const DELIVERY_ZONE_12 = 'postroyka_manager.delivery_zone_12';
    const DELIVERY_ZONE_13 = 'postroyka_manager.delivery_zone_13';
    const DELIVERY_ZONE_14 = 'postroyka_manager.delivery_zone_14';
    const DELIVERY_ZONE_15 = 'postroyka_manager.delivery_zone_15';
    const DELIVERY_ZONE_16 = 'postroyka_manager.delivery_zone_16';
    const DELIVERY_ZONE_17 = 'postroyka_manager.delivery_zone_17';
    const DELIVERY_ZONE_18 = 'postroyka_manager.delivery_zone_18';


    const DISCOUNT_LIMIT = 'postroyka_manager.discount_limit';
    const DISCOUNT_PERCENT = 'postroyka_manager.discount_percent';
    const FREE_DELIVERY_LIMIT = 'postroyka_manager.free_delivery_limit';
    const FREE_DELIVERY_WEIGHT = 'postroyka_manager.free_delivery_weight';
    const PRODUCT_TYPES_NO_ELEVATING = 'postroyka_manager.product_types_no_elevating';
    const TOWN_ZONES = 'postroyka_manager.town_zones';
    const UNLOADING_SHEETS_MINIMAL_FLOOR = 'postroyka_manager.unloading_sheets_minimal_floor';
    const UNLOADING_SHEETS_MINIMAL_NUMBER = 'postroyka_manager.unloading_sheets_minimal_number';
    const UNLOADING_LIMIT = 'postroyka_manager.unloading_limit';
    const UNLOADING_ELEVATOR = 'postroyka_manager.unloading_elevator';
    const UNLOADING_BAGS = 'postroyka_manager.unloading_bags';
    const UNLOADING_BIG_BAGS = 'postroyka_manager.unloading_big_bags';
    const UNLOADING_SHEETS = 'postroyka_manager.unloading_sheets';
    const UNLOADING_BIG_SHEETS = 'postroyka_manager.unloading_big_sheets';
    const UNLOADING_PROFILE = 'postroyka_manager.unloading_profile';
    const UNLOADING_BRICKS = 'postroyka_manager.unloading_bricks';
    const UNLOADING_BLOCKS = 'postroyka_manager.unloading_blocks';
    const EXPRESS_DELIVERY = 'postroyka_manager.express_delivery';
    const WEEKEND_DATES = 'postroyka_manager.weekend_dates';

    const ORDER_PAYMENT_CASH = 'order.payment.cash';
    const ORDER_PAYMENT_CARD = 'order.payment.card';
    const ORDER_UNLOADING_NONE = 'order.unloading.none';
    const ORDER_UNLOADING_CAR = 'order.unloading.car';
    const ORDER_UNLOADING_ROOM = 'order.unloading.room';
    const ORDER_PURCHASE_PICKUP = 'order.purchase.pickup';
    const ORDER_PURCHASE_DELIVERY = 'order.purchase.delivery';
    const ORDER_PURCHASE_EXPRESS = 'order.purchase.express';

    const SILVER_CARD = 'postroyka_manager.silver_card';
    const GOLD_CARD = 'postroyka_manager.gold_card';

    const UNLOADING_SHEETS_LOADER_FACTOR = 2;

    /**
     * Фиксированный процент скидки для товаров с отключенной скидкой для клиентов с золотой или серебрянной картой
     */
    const FIXED_DISCOUNT = 3;

    /**
     * @var OptionsProviderInterface
     */
    private $options;

    /**
     * @var CartProvider
     */
    private $cartProvider;

    /**
     * OrderCalculatorService constructor.
     * @param OptionsProviderInterface $options
     * @param CartProvider $cartProvider
     */
    public function __construct(OptionsProviderInterface $options, CartProvider $cartProvider)
    {
        $this->options = $options;
        $this->cartProvider = $cartProvider;
    }

    /**
     * Стомость доставки в зону
     * @param CartInterface $cart
     * @param $zone
     * @return float
     */
    public function getZonePrice(CartInterface $cart, $zone)
    {
        // Стоимость доставки в зоне
        $zonePrices = $this->options->getValue($zone);
        $zonePrices = explode('+', $zonePrices);
        $price = $this->castCost($zonePrices[0] ?? '');

        // Минимальная сумма заказа для бесплатной доставки
        $freeDeliveryLimit = $this->options->getValue(self::FREE_DELIVERY_LIMIT);
        $freeDeliveryLimit = $this->castCost($freeDeliveryLimit);

        // Максимальный вес заказа для бесплатной доставки
        $freeDeliveryWeight = $this->options->getValue(self::FREE_DELIVERY_WEIGHT);
        $freeDeliveryWeight = $this->castCost($freeDeliveryWeight);

        // Вес заказа
        $totalWeight = $this->cartProvider->getTotalWeight($cart);

        // Бесплатная доставка с учетом городской черты
        if (($cart->getTotal() >= $freeDeliveryLimit) && ($totalWeight <= $freeDeliveryWeight)) {
            // Список зон, входящих в городскую черту
            $townZones = $this->options->getValue(self::TOWN_ZONES);
            $townZones = str_replace(' ', '', $townZones);
            $townZones = explode(',', $townZones);

            // Номер текущей зоны
            $zoneNumber = substr($zone, -2, 2);
            $zoneNumber = str_replace('_', '', $zoneNumber);

            $price = !in_array($zoneNumber, $townZones, true) ? $price : 0;
        }

        return $price;
    }

    /**
     * Стоимость доставки
     * @param CartInterface $cart Корзина
     * @param array $data Данные
     * @return float
     * @throws \Exception
     */
    public function getDeliveryPrice(CartInterface $cart, array $data)
    {
        $purchase = $data['purchase'] ?? self::ORDER_PURCHASE_PICKUP;
        $zone = $data['delivery'] ?? null;

        if ($purchase !== self::ORDER_PURCHASE_PICKUP && !$zone) {
            throw new \InvalidArgumentException('Ошибка в форме заказа! Не выбрана зона доставки.');
        }

        return $this->calculateDelivery($cart, $purchase, $zone);
    }

    /**
     * Стоимость разгрузки
     * @param CartInterface $cart Корзина
     * @param array $data Данные
     * @return float
     * @throws \Exception
     */
    public function getUnloadingPrice(CartInterface $cart, array $data)
    {
        $unloading = $data['unloading'] ?? self::ORDER_UNLOADING_NONE;
        $zone = $data['delivery'] ?? null;
        $floor = (int)($data['floor'] ?? 0);
        $elevator = (bool)($data['elevator'] ?? false);
        $extraFloor = (bool)($data['extraFloor'] ?? false);

        return $this->calculateUnloading($cart, $zone, $unloading, $floor, $elevator, $extraFloor);
    }

    /**
     * Минимальная стоимость разгрузки
     * @param CartInterface $cart Корзина
     * @param array $data Данные
     * @return float
     */
    public function getUnloadingLimit(CartInterface $cart, array $data)
    {
        $zone = $data['delivery'] ?? null;
        $floor = (int)($data['floor'] ?? 0);
        $extraFloor = (bool)($data['extraFloor'] ?? false);

        return $this->calculateUnloadingLimit($cart, $zone, $floor, $extraFloor);
    }

    /**
     * Минимальная стоимость разгрузки
     * @param CartInterface $cart
     * @param string $zone Зона
     * @param int $floor Этаж
     * @param bool $extraFloor Дополнительный этаж
     * @return float
     */
    private function calculateUnloadingLimit(CartInterface $cart, $zone, $floor, $extraFloor)
    {
        // Базовый лимит
        $unloadingLimit = $this->options->getValue(self::UNLOADING_LIMIT);
        $unloadingLimit = $this->castCost($unloadingLimit);

        // Минимальный этаж для  разгрузки 2-мя грузчиками
        $minimalFloor = $this->options->getValue(self::UNLOADING_SHEETS_MINIMAL_FLOOR);
        $minimalFloor = $this->castCost($minimalFloor);

        // Минимальное кол-во листов для  разгрузки 2-мя грузчиками
        $minimalSheets = $this->options->getValue(self::UNLOADING_SHEETS_MINIMAL_NUMBER);
        $minimalSheets = $this->castCost($minimalSheets);

        $sheetsCount = 0;

        /** @var CartItemInterface $item */
        foreach ($cart->getItems() as $item) {
            $option = $item->getOptions()->get('unloading_type');

            if ($option && (false !== strpos($option->getValue(), 'sheets'))) {
                $sheetsCount += $item->getQuantity();
            }
        }

        // Повышение лимита при соблюдении условий:
        // если есть листы И (их количество >= лимита ИЛИ этаж >= лимита)
        if ($sheetsCount) {
            if (($sheetsCount >= $minimalSheets) || ($floor + (int)$extraFloor >= $minimalFloor)) {
                return $unloadingLimit * self::UNLOADING_SHEETS_LOADER_FACTOR + $this->getZoneUnloadingExtra($zone);
            }
        }

        return $unloadingLimit + $this->getZoneUnloadingExtra($zone);
    }

    /**
     * Стоимость доставки
     * @param CartInterface $cart Корзина
     * @param string $purchase Тип доставки
     * @param string $zone Зона
     * @return float
     */
    private function calculateDelivery(CartInterface $cart, $purchase, $zone)
    {
        if (self::ORDER_PURCHASE_DELIVERY === $purchase) {
            return $this->getZonePrice($cart, $zone);
        }

        if (self::ORDER_PURCHASE_EXPRESS === $purchase) {
            $deliveryFactor = 1 + $this->options->getValue(self::EXPRESS_DELIVERY) / 100;

            return $this->getZonePrice($cart, $zone) * $deliveryFactor;
        }

        return 0;
    }

    /**
     * Стоимость разгрузки
     * @param CartInterface $cart Корзина
     * @param string $zone Зона
     * @param string $unloading Тип выгрузки
     * @param int $floor Этаж
     * @param bool $elevator Лифт
     * @param bool $extraFloor Дополнительный этаж
     * @return float
     * @throws \Exception
     */
    private function calculateUnloading(CartInterface $cart, $zone, $unloading, $floor, $elevator, $extraFloor)
    {
        if (self::ORDER_UNLOADING_ROOM === $unloading) {
            $result = 0;

            foreach ($cart->getItems() as $item) {
                $result += $this->calculateItemUnloading($item, $floor, $elevator, $extraFloor);
            }

            if (!$result) {
                return 0;
            }

            $result += $this->getZoneUnloadingExtra($zone);
            $unloadingLimit = $this->calculateUnloadingLimit($cart, $zone, $floor, $extraFloor);

            return ($result > $unloadingLimit) ? $result : $unloadingLimit;
        }

        if (self::ORDER_UNLOADING_CAR === $unloading) {
            return 0;
        }

        return 0;
    }

    /**
     * Стоимость разгрузки одной позиции
     * @param CartItemInterface $item Позиция
     * @param int $floor Этаж
     * @param bool $elevator Лифт
     * @param bool $extraFloor Дополнитльный этаж
     * @return float
     * @throws \Exception
     */
    private function calculateItemUnloading(CartItemInterface $item, $floor, $elevator, $extraFloor)
    {
        $unloadingType = $item->getOptions()->get('unloading_type');
        $unloadingType = $unloadingType ? $unloadingType->getValue() : null;

        if (!$unloadingType || (!$floor && !$extraFloor)) {
            return 0;
        }

        // Текущий тип товара (тары)
        $unloadingType = explode(' ', $unloadingType);
        $unloadingType = $unloadingType[0];

        // Константа типа товара (тары)
        if (!defined('self::UNLOADING_' . strtoupper($unloadingType))) {
            return 0;
        }

        $unloadingTypeConstant = constant('self::UNLOADING_' . strtoupper($unloadingType));

        // Список типов товаров, не входящих в лифт
        $typesNoElevating = $this->options->getValue(self::PRODUCT_TYPES_NO_ELEVATING);
        $typesNoElevating = str_replace(' ', '', $typesNoElevating);
        $typesNoElevating = explode(',', $typesNoElevating);

        // Подъем на лифте?
        $elevating = $elevator && !in_array($unloadingType, $typesNoElevating, true);

        // Стоимость разгрузки
        $unloadingPrice = $this->options->getValue($unloadingTypeConstant);
        $unloadingPrice = $this->castCost($unloadingPrice);

        // Стоимость разгрузки с лифтом: за сколько этажей брать
        $elevatorFactor = (int)$this->options->getValue(self::UNLOADING_ELEVATOR) + (int)$extraFloor;

        // Стоимость разгрузки еденицы за этаж
        $pricePerFloor = $unloadingPrice * $item->getQuantity();

        // Стоимость разгрузки на второй этаж и выше
        if ($floor > 1) {
            return $elevating ? ($elevatorFactor * $pricePerFloor) : (($floor + (int)$extraFloor) * $pricePerFloor);
        }

        return ((int)$extraFloor + 1) * $pricePerFloor;
    }

    /**
     * Стоимость приезда грузчиков в зону
     * @param $zone
     * @return float
     */
    private function getZoneUnloadingExtra($zone)
    {
        $zonePrices = $this->options->getValue($zone);
        $zonePrices = explode('+', $zonePrices);

        return $this->castCost($zonePrices[1] ?? '');
    }

    /**
     * Приведение стоимости
     * @param string $cost
     * @return float
     */
    private function castCost($cost)
    {
        $cost = str_replace([' ', ','], ['', '.'], $cost);

        return (float)$cost;
    }
}

<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AppBundle\Provider\CartProvider;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Postroyka\AppBundle\Service\OrderCalculator;
use Submarine\CartBundle\Entity\Cart;
use Submarine\FrontBundle\Controller\Front\AbstractFrontController;
use Submarine\MailerBundle\Provider\MailerProvider;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Provider\PagesProvider;
use Submarine\PropertiesBundle\Provider\PropertyValuesProvider;

class AbstractController extends AbstractFrontController
{
    const CATALOG_CACHE = 'catalog';

    /**
     * @return PagesProvider
     */
    protected function pages()
    {
        return $this->get('submarine.pages.pages_provider');
    }

    /**
     * @return CatalogProvider
     */
    protected function catalog()
    {
        return $this->get('postroyka_app.catalog_provider');
    }

    /**
     * @return CartProvider
     */
    protected function cartProvider()
    {
        return $this->get('postroyka_app.cart_provider');
    }

    /**
     * @return ExtendedUser
     */
    protected function getExtendedUser()
    {
        return $this->get('postroyka_account.extended_user_provider')->getByUser($this->getUser());
    }

    /**
     * @return PropertyValuesProvider
     */
    protected function properties()
    {
        return $this->get('submarine_properties.property_values_provider');
    }

    /**
     * @return MailerProvider
     */
    protected function mailer()
    {
        return $this->get('submarine.mailer.provider');
    }

    /**
     * @return array
     */
    protected function extendedData()
    {
        // Стоимость доставки в зоны для карты
        $zoneConst = 'Postroyka\AppBundle\Service\OrderCalculator::DELIVERY_ZONE_';
        $zones = [];
        $cart = new Cart();
        $zonesNumber = OrderCalculator::DELIVERY_ZONES_NUMBER;

        for ($i = 1; $i <= $zonesNumber; $i++) {
            $zone = $this->get('postroyka_app.order_calculator')->getZonePrice($cart, constant($zoneConst . $i));
            $zones[] = number_format($zone, 0, ',', ' ') . ' руб.';
        }

        return [
            'left_menu' => $this->getCatalogGroups(),
            'cart' => $this->cartProvider()->getCart($this->getUser()),
            'extended_user' => $this->getExtendedUser(),
            'map_zones' => implode('|', $zones),
        ];
    }

    /**
     * Получение групп и подгрупп каталога
     * @return Page[]
     */
    protected function getCatalogGroups()
    {
        if (false === ($groups = $this->cache()->fetch(self::CATALOG_CACHE))) {
            $groups = $this->catalog()->getGroupsTreeArray();

            // Кэшируем на час
            $this->cache()->save(self::CATALOG_CACHE, $groups, 60 * 60);
        }

        return $groups;
    }
}

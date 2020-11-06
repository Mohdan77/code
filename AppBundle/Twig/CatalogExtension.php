<?php

namespace Postroyka\AppBundle\Twig;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\AppBundle\Service\PriceService;
use Submarine\ControlsBundle\Twig\StringFormatExtension;
use Submarine\OrdersBundle\Entity\Order;
use Submarine\PagesBundle\Entity\Page;
use Submarine\UsersBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Contracts\Translation\TranslatorInterface;

class CatalogExtension extends \Twig_Extension
{
    /**
     * @var PriceService
     */
    private $priceService;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var ExtendedUserProvider
     */
    private $extendedUserProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * CatalogExtension constructor.
     * @param PriceService $priceService
     * @param TokenStorage $tokenStorage
     * @param ExtendedUserProvider $extendedUserProvider
     */
    public function __construct(PriceService $priceService, TokenStorage $tokenStorage, ExtendedUserProvider $extendedUserProvider, TranslatorInterface $translator)
    {
        $this->priceService = $priceService;
        $this->tokenStorage = $tokenStorage;
        $this->extendedUserProvider = $extendedUserProvider;
        $this->translator = $translator;
    }

    /**
     * @return ExtendedUser
     */
    private function getExtendedUser()
    {
        $token = $this->tokenStorage->getToken();
        $user = $token ? $token->getUser() : null;
        $user = $user instanceof User ? $user : null;

        return $this->extendedUserProvider->getByUser($user);
    }

    /**
     * Форматирование розничной цены
     * @param float $price
     * @param Page $product
     * @return string
     */
    private function formatRetailPrice($price, Page $product)
    {
        $price = number_format($price, 2, ',', ' ');
        $unit = $product->getValue('unit', 'шт.');

        return $price . ' <span>р/' . $unit . '</span>';
    }

    /**
     * Розничная цена со скидочной картой
     * @param Page $product
     * @return string
     */
    public function retailPrice(Page $product)
    {
        $price = $this->priceService->getRetailPrice($product, $this->getExtendedUser());

        return $this->formatRetailPrice($price, $product);
    }

    /**
     * Старая розничная цена со скидочной картой
     * @param Page $product
     * @return string
     */
    public function oldRetailPrice(Page $product)
    {
        $price = $this->priceService->getOldRetailPrice($product, $this->getExtendedUser());

        return $this->formatRetailPrice($price, $product);
    }

    /**
     * Есть ли старая розничная цена?
     * @param Page $product
     * @return bool
     */
    public function hasOldRetailPrice(Page $product)
    {
        $retailPrice = $this->priceService->getRetailPrice($product, $this->getExtendedUser());
        $oldRetailPrice = $this->priceService->getOldRetailPrice($product, $this->getExtendedUser());

        return $oldRetailPrice > $retailPrice;
    }

    /**
     * Оптовая цена со скидочной картой
     * @param Page $product
     * @return string
     */
    public function wholesalePrice(Page $product)
    {
        $price = $this->priceService->getWholesalePrice($product, $this->getExtendedUser());
        $price = number_format($price, 2, ',', ' ');

        return $price . ' р.';
    }

    /**
     * Есть ли оптовая цена?
     * @param Page $product
     * @return bool
     */
    public function hasWholesalePrice(Page $product)
    {
        return $this->priceService->hasWholesalePrice($product, $this->getExtendedUser());
    }

    /**
     * Оптовое количество
     * @param Page $product
     * @return string
     */
    public function wholesaleQuantity(Page $product)
    {
        return $product->getValue('wholesale_quantity') . ' ' . $product->getValue('unit', 'шт.');
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('unloading', array($this, 'unloadingFilter')),
        );
    }

    public function unloadingFilter($order)
    {
        if ( !$order instanceof Order) {
            throw new \Exception('The value must be instance of Order');
        }

        $options = $order->getOptions();

        $yes = $this->translator->trans('boolean.yes');
        $no = $this->translator->trans('boolean.no');

        $unloadingPrice = $options->get('unloading_price');
        if ($unloadingPrice && $unloadingPrice->value != '') {
            $unloadingPrice = StringFormatExtension::numberFilter($unloadingPrice->value, true) .' руб.';
        } else {
            $unloadingPrice = '';
        }

        $floor = $options->get('floor');
        if ($floor && $floor->value != '') {
            $floor = ' | '. $floor->title .': '. $floor->value;
        } else {
            $floor = '';
        }

        $elevator = $options->get('elevator');
        if ($elevator && $elevator->value != '') {
            $elevator = ' | ' . $elevator->title .': '. $yes;
        } else {
            $elevator = ' | ' . $elevator->title .': '. $no;
        }

        $extraFloor = $options->get('extraFloor');
        if ($extraFloor && $extraFloor->value != '') {
            $extraFloor = ' | '. $extraFloor->title .': ' . $yes;
        } else {
            $extraFloor = ' | '. $extraFloor->title .': ' . $no;
        }

        $unloading = $unloadingPrice . $floor;
        if ($unloading == '') {
            return $options->get('unloading')->value;
        } else {
            $unloading .= $elevator . $extraFloor;
        }

        return $unloading;
    }

    public function getFunctions()
    {
        return [
            new \Twig_Function('retail_price', [$this, 'retailPrice'], ['is_safe' => ['html']]),
            new \Twig_Function('old_retail_price', [$this, 'oldRetailPrice'], ['is_safe' => ['html']]),
            new \Twig_Function('has_old_retail_price', [$this, 'hasOldRetailPrice']),
            new \Twig_Function('wholesale_price', [$this, 'wholesalePrice']),
            new \Twig_Function('has_wholesale_price', [$this, 'hasWholesalePrice']),
            new \Twig_Function('wholesale_quantity', [$this, 'wholesaleQuantity']),
        ];
    }
}

<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AppBundle\Provider\CatalogProvider;
use Postroyka\AppBundle\Service\PriceService;
use Postroyka\AppBundle\ServicePages;
use Submarine\CartBundle\Cart\CartItemInterface;
use Symfony\Component\HttpFoundation\Request;

class CartController extends AbstractController
{
    /**
     * @return PriceService
     */
    private function priceService()
    {
        return $this->get('postroyka_app.price_service');
    }

    public function __construct()
    {
        parent::__construct();

        $this->getMetaTags()->setTitle('Корзина');
    }

    /**
     * Корзина
     */
    public function cartAction(Request $request)
    {
        $cart = $this->cartProvider()->getCart($this->getUser());


        if ('POST' === $request->getMethod()) {
            $items = $request->get('items', []);

            foreach ($items as $key => $quantity) {
                if ($cart->getItems()->containsKey($key)) {
                    if ($quantity) {
                        /** @var CartItemInterface $item */
                        $item = $cart->getItems()->get($key);
                        $item->setQuantity($quantity);
                        $product = $this->pages()->getByUrl($item->getUrl());

                        if ($product->getId()) {
                            $discount = $this->priceService()->getUnitDiscount($product, $this->getExtendedUser(), $quantity);
                            $item->setUnitDiscount($discount);
                        } else {
                            $cart->removeItem($key);
                        }
                    } else {
                        $cart->removeItem($key);
                    }
                }
            }

            $this->cartProvider()->saveCart($this->getUser(), $cart);

            if ($request->get('submit_order')){
                return $this->redirectToRoute('order');
            }

            return $this->redirectToRoute('cart');
        }

        $data = [
            'left_menu' => $this->getCatalogGroups(),
            'cart' => $cart,
            'total_weight' => $this->cartProvider()->getTotalWeight($cart),
        ];

        foreach ($cart->getItems() as $item){
            $product = $this->catalog()->get($item->getEntityId());
            $data['products'][$item->getEntityId()] = $product;
        }

        if ($cart->isEmpty()) {
            $data['page'] = $this->pages()->getByTag(ServicePages::CART_EMPTY);

            $stock = $this->catalog()->getStockAndMarkdownQuery()->getResult();
            shuffle($stock);
            $data['stock'] = $stock;
        }

        $data['mayneed_products'] = $this->catalog()->getMayneedQuery()->getResult();

        return $this->renderPage('@PostroykaApp/Cart/cart.html.twig', $data);
    }

    /**
     * Добавление товара
     */
    public function addAction(Request $request, $id)
    {
        $product = $this->pages()->get($id);

        if (!$product->getId() || (CatalogProvider::PRODUCT_TYPE !== $product->getType()->getId())) {
            throw $this->createNotFoundException();
        }

        $quantity = $request->get('quantity', 1);

        $this->cartProvider()->addToCart($this->getUser(), $product, $quantity);

        $refUrl = $request->server->get('HTTP_REFERER');

        if ($refUrl) {
            return $this->redirect($refUrl);
        }

        return $this->redirectToRoute('cart');
    }

    /**
     * Удаление товара
     */
    public function removeAction($key)
    {
        $cart = $this->cartProvider()->getCart($this->getUser());
        $cart->removeItem($key);
        $this->cartProvider()->saveCart($this->getUser(), $cart);

        return $this->redirectToRoute('cart');
    }

    /**
     * Очистка корзины
     */
    public function clearAction()
    {
        $this->cartProvider()->clearCart($this->getUser());

        return $this->redirectToRoute('cart');
    }
}

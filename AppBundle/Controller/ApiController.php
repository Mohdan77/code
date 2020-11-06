<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AppBundle\Form\NotFoundForm;
use Postroyka\AppBundle\Provider\CartProvider;
use Postroyka\AppBundle\Provider\CatalogProvider;
use Postroyka\AppBundle\Service\OrderCalculator;
use Postroyka\AppBundle\Service\PriceService;
use Submarine\CartBundle\Cart\CartItemInterface;
use Submarine\ControlsBundle\Twig\StringFormatExtension;
use Submarine\FrontBundle\Controller\Front\AbstractFrontController;
use Submarine\OrdersBundle\Entity\Order;
use Submarine\PagesBundle\Entity\Page;
use Submarine\PagesBundle\Filter\PagesFilter;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiController extends AbstractFrontController
{
    const MAX_PRODUCTS_SEARCH = 99999;
    const MAX_CATEGORIES_SEARCH = 99999;
    const SESSION_KEY_ORDER_FORM_DATA = 'order_form_data';

    /**
     * @return CatalogProvider
     */
    private function catalog()
    {
        return $this->get('postroyka_app.catalog_provider');
    }

    /**
     * @return OrderCalculator
     */
    private function calculator()
    {
        return $this->get('postroyka_app.order_calculator');
    }

    /**
     * @return CartProvider
     */
    private function cartProvider()
    {
        return $this->get('postroyka_app.cart_provider');
    }

    /**
     * @return PriceService
     */
    private function priceService()
    {
        return $this->get('postroyka_app.price_service');
    }

    /**
     * @return Session
     */
    private function sessions()
    {
        return $this->get('session');
    }

    /**
     * @return ExtendedUser
     */
    private function getExtendedUser()
    {
        return $this->get('postroyka_account.extended_user_provider')->getByUser($this->getUser());
    }

    /**
     * Точка входа
     */
    public function apiAction(Request $request, $action = '')
    {
        if ($action === 'search-catalog') {
            return $this->searchCatalog($request);
        } elseif ($action === 'calculate-unloading') {
            return $this->calculateUnloading($request);
        } elseif ($action === 'cart-add') {
            return $this->cartAdd($request);
        } elseif ($action === 'cart-update') {
            return $this->cartUpdate($request);
        } elseif ($action === 'keep-form-data') {
            return $this->keepFormData($request);
        }

        return new JsonResponse('Invalid request!');
    }

    /**
     * Поиск страницы
     */
    public function searchCatalog(Request $request)
    {
        $search = trim($request->query->get('term', ''));
        $result = [];

        if (mb_strlen($search) < 2) {
            return new JsonResponse($result);
        }


        // Товары
        $filter = new PagesFilter(CatalogProvider::PRODUCT_TYPE);
        $fields = ['title', 'productId', 'metaKeywords'];


        /**
         * @var Page[] $items
         */
        $items = $this->catalog()->searchQuery($search, $fields, $filter)->setMaxResults(self::MAX_PRODUCTS_SEARCH)->getResult();

        foreach ($items as $item) {
            $result[] = [
                'label' => $item->getTitle(),
                'value' => $this->convertUrl($item->getUrl()),
                'type' => 'product',
            ];
        }

        $result[] = [
            'type' => 'total_products',
            'total' => $this->catalog()->searchCount($search, $fields, $filter),
            'max' => self::MAX_PRODUCTS_SEARCH,
        ];

        return new JsonResponse($result);
    }

    /**
     * Рассчет стоимости разгрузки
     */
    public function calculateUnloading(Request $request)
    {
        $cart = $this->cartProvider()->getCart($this->getUser());

        return new JsonResponse([
            'price' => $this->calculator()->getUnloadingPrice($cart, $request->request->all()),
            'limit' => $this->calculator()->getUnloadingLimit($cart, $request->request->all()),
        ]);
    }

    /**
     * Добавление в корзину
     */
    public function cartAdd(Request $request)
    {
        $product = $this->catalog()->get($request->get('id'));

        if (!$product->getId() || (CatalogProvider::PRODUCT_TYPE !== $product->getType()->getId())) {
            return new JsonResponse();
        }

        $quantity = $request->get('quantity', 1);
        $this->cartProvider()->addToCart($this->getUser(), $product, $quantity);
        $cart = $this->cartProvider()->getCart($this->getUser());

        $result = [
            'title' => $product->getTitle(),
            'quantity' => $quantity,
            'cart_quantity' => $cart->getCountQuantity(),
            'cart_total' => $cart->getTotal(),
        ];

        return new JsonResponse($result);
    }

    /**
     * Обновление корзины
     */
    public function cartUpdate(Request $request)
    {
        $cart = $this->cartProvider()->getCart($this->getUser());

        $items = $request->get('items');

        foreach ($items as $key => $quantity) {
            if ($cart->getItems()->containsKey($key)) {
                if ($quantity) {
                    /** @var CartItemInterface $item */
                    $item = $cart->getItems()->get($key);
                    $item->setQuantity($quantity);
                    $product = $this->catalog()->getByUrl($item->getUrl());

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

        $weight = $this->cartProvider()->getTotalWeight($cart);

        $result = [
            'quantity' => $quantity ?? 0,
            'cart_quantity' => $cart->getCountQuantity(),
            'cart_total' => StringFormatExtension::numberFilter($cart->getTotal(), true),
            'cart_total_discount' => StringFormatExtension::numberFilter($cart->getTotalDiscount(), true),
            'total_weight' => number_format($weight, (int)(floor($weight) != $weight), ',', ' '),
        ];

        return new JsonResponse($result);
    }

    /**
     * Сохранение введенных в форму заказа данных
     * @param Request $request
     * @return JsonResponse
     */
    public function keepFormData(Request $request)
    {
        // Фильтрация необходимых параметров формы
        $allowed = ['purchase', 'date', 'address', 'delivery', 'unloading', 'floor', 'elevator', 'extraFloor', 'phone', 'email', 'comment'];
        $data = array_intersect_key($request->request->all(), array_flip($allowed));

        // Преобразовать checkbox в boolean
        $booleans = ['elevator', 'extraFloor'];
        array_walk($data, function (&$item, $key) use ($booleans) {
            // Если ключ есть в массиве, значит checkbox is 'on'
            if (in_array($key, $booleans)) {
                $item = true;
            }
        });

        $this->sessions()->set(self::SESSION_KEY_ORDER_FORM_DATA, $data);

        return new JsonResponse('Success');
    }

    /**
     * Не нашли что искали?
     */
    public function notFoundAction(Request $request)
    {

        $formData = json_decode($request->getContent(), true);

        if ($formData === null) {
            throw new BadRequestHttpException('Invalid JSON');
        }

        $form = $this->createForm(NotFoundForm::class, null, ['csrf_protection' => false]);
        $form->submit($formData);

        if (!$form->isValid()) {

            $errors = [];
            foreach ($form->all() as $childForm) {
                if ($childForm instanceof FormInterface) {

                    foreach ($childForm->getErrors() as $childError) {
                        $errors[$childForm->getName()] = $childError->getMessage();
                    }
                }
            }

            $data['errors'] = $errors;

            return new JsonResponse($data, 400);
        }

        try {
            $mailer = $this->get('submarine.mailer.provider');

            $mailer->sendForm($form, 'mail.subject.not_found', $request);
            $data['success'] = true;
        } catch (\Exception $e) {
            $data['error'] = true;
        }

        return new JsonResponse($data, 200);
    }

    public function printOrderAction($orderId, $orderUuid)
    {

        /** @var Order $order */
        $order = $this->getDoctrine()->getRepository(Order::class)->find($orderId);

        if ($order->getUuid() != $orderUuid) {
            throw $this->createNotFoundException();
        }

        if ($order == null) {
            return new JsonResponse(['errors' => 'order not found'], 404);
        }

        return $this->renderPage('@PostroykaApp/Api/Print/order.html.twig', ['order' => $order]);

    }



}

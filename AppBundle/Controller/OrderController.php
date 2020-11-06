<?php

namespace Postroyka\AppBundle\Controller;

use Postroyka\AppBundle\Form\OrderForm;
use Postroyka\AppBundle\Provider\OrdersProvider;
use Postroyka\AppBundle\Service\OrderCalculator;
use Postroyka\AppBundle\ServicePages;
use Submarine\CartBundle\Cart\CartInterface;
use Submarine\CartBundle\Entity\Option;
use Submarine\CoreBundle\Options\OptionsCacheProvider;
use Submarine\MailerBundle\Entity\Message;
use Submarine\OrdersBundle\Entity\Order;
use Submarine\OrdersBundle\Event\OrderEvent;
use Submarine\OrdersBundle\Provider\StatusProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\DataCollectorTranslator;

class OrderController extends AbstractController
{
    const SESSION_KEY_SUCCESS_MD5 = 'order_success_md5';

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    /**
     * @return OrdersProvider
     */
    private function orders()
    {
        return $this->get('postroyka_app.orders_provider');
    }

    /**
     * @return Session
     */
    private function sessions()
    {
        return $this->get('session');
    }

    /**
     * @return StatusProvider
     */
    private function statuses()
    {
        return $this->get('submarine_orders.status_provider');
    }

    /**
     * @return OptionsCacheProvider
     */
    private function options()
    {
        return $this->get('core.options_cache');
    }

    /**
     * @return OrderCalculator
     */
    private function calculator()
    {
        return $this->get('postroyka_app.order_calculator');
    }

    /**
     * @return DataCollectorTranslator
     */
    private function translator()
    {
        return $this->get('translator');
    }

    public function __construct(EventDispatcherInterface $dispatcher)
    {
        parent::__construct();

        $this->getMetaTags()->setTitle('Оформление заказа');

        $this->dispatcher = $dispatcher;
    }

    /*
     * Создание заказа
     */
    public function orderAction(Request $request)
    {
        $cart = $this->cartProvider()->getCart($this->getUser());

        if ($cart->isEmpty()) {
            return $this->redirectToRoute('cart');
        }

        $form = $this->createForm(OrderForm::class, $this->getDefaultData(), $this->getDefaultOptions($cart));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $order = new Order();
                $order->setUser($this->getUser());
                $order->setStatus($this->statuses()->getDefaultStatus());
                $order->handleCart($cart);

                // Стоимость доставки
                $delivery = $this->calculator()->getDeliveryPrice($cart, $form->getNormData());

                if ($form->getNormData()['purchase'] !== OrderCalculator::ORDER_PURCHASE_PICKUP) {
                    $title = $this->translator()->trans('order.delivery.price');
                    $order->addOption(new Option('delivery_price', $delivery, $title));
                }

                // Стоимость разгрузки
                $unloading = $this->calculator()->getUnloadingPrice($cart, $form->getNormData());

                if ($unloading) {
                    $title = $this->translator()->trans('order.unloading.price');
                    $order->addOption(new Option('unloading_price', $unloading, $title));
                }

                $order->setDeliveryMethod($this->translator()->trans($form->getNormData()['purchase']));
                $order->setDeliveryMethodPrice($unloading + $delivery);

                if ($this->getExtendedUser()->getId()) {
                    $title = $this->translator()->trans('user.discount_card');
                    $order->addOption(new Option('discount_card', $this->getExtendedUser()->getDiscount(), $title));
                }


                // Масса заказа
                $totalWeight = $this->cartProvider()->getTotalWeight($cart);

                if ($totalWeight) {
                    $title = $this->translator()->trans('order.total_weight');
                    $order->addOption(new Option('total_weight', $totalWeight, $title));
                }


                $this->orders()->handleFormOrder($order, $form);
                $this->orders()->createOrder($order);


                // Оплата картой
                if ($form->get('card_payment')->getData()) {
                    $cardPaymentHelper = $this->get('postroyka_app.card_payment_service');
                    $cardPaymentHelper->handleOrder($order, $form);
                }


                $this->cartProvider()->clearCart($this->getUser());

                $this->sessions()->set(self::SESSION_KEY_SUCCESS_MD5, md5($order->getId()));
                $this->sessions()->remove(ApiController::SESSION_KEY_ORDER_FORM_DATA);

                $this->sendMessages($order, $request);

                // Сгенерировать событие
                $event = new OrderEvent($order);
                $this->dispatcher->dispatch($event, $event->getEventName());
            } catch (\Exception $e) {
                $this->get('core.log')->critical("Ошибка оформления заказа: {message} \n{trace}", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);

                $this->notifier()->error($e->getMessage());

                return $this->redirectToRoute('order');
            }

            return $this->redirectToRoute('order_success', [
                'order_id' => $order->getId()
            ]);
        }

        $data = [
            'form' => $form->createView(),
            'cart' => $cart,
            'unloading_limit' => $this->calculator()->getUnloadingLimit($cart, $form->getNormData()),
            'total_weight' => $this->cartProvider()->getTotalWeight($cart),
            'body_class' => 'checkout',
        ];

        return $this->renderPage('@PostroykaApp/Order/order.html.twig', $data);
    }

    /*
     * Заказ успешно оформлен
     */
    public function successAction(Request $request)
    {
        $orderId = $request->get('order_id', 0);
        $md5 = $this->sessions()->get(self::SESSION_KEY_SUCCESS_MD5);
        $this->sessions()->remove(self::SESSION_KEY_SUCCESS_MD5);

        $order = $this->orders()->get($orderId);

        if (!$order->getId() || $md5 !== md5($order->getId())) {
            return $this->redirectToRoute('home');
        }

        $data['order'] = $order;
        $data['order_id'] = $orderId;
        $data['page'] = $this->pages()->getByTag(ServicePages::ORDER_SUCCESS);

        return $this->renderPage('@PostroykaApp/Order/success.html.twig', $data);
    }

    // Оплата картой прошла успешно
    public function cardPaymentSuccessAction(Request $request)
    {
        $orderId = $request->get('orderId', 0);

        $order = $this->orders()->get($orderId);
        $cardPayment = $order->getCardPayment();

        $order->setPaid(true);
        $cardPayment->setUid($request->get('uid'));

        $this->entityManager()->persist($order);
        $this->entityManager()->persist($cardPayment);

        $this->entityManager()->flush();


        return $this->renderPage('@PostroykaApp/Order/card_payment_success.html.twig', [
            'order' => $order
        ]);
    }

    /*
     * Печать заказа
     */
    public function printAction()
    {
        $cart = $this->cartProvider()->getCart($this->getUser());

        $data = [
            'cart' => $cart,
            'total_weight' => $this->cartProvider()->getTotalWeight($cart),
        ];

        return $this->renderPage('@PostroykaApp/Order/print.html.twig', $data);
    }

    /**
     * Сохраненные данные для формы
     * @return array
     */
    private function getDefaultData()
    {
        $data = $this->sessions()->get(ApiController::SESSION_KEY_ORDER_FORM_DATA);

        if (!$data) {
            $data['floor'] = null;
            $data['unloading'] = OrderCalculator::ORDER_UNLOADING_NONE;
        }

        $user = $this->getExtendedUser();

        if ($user->getPhone()) {
            $data['phone'] = $user->getPhone();
        }

        if ($user->getId()) {
            $data['email'] = $user->getEmail();
        }

        return $data;
    }

    /**
     * Начальные опции для формы
     * @param CartInterface $cart
     * @return array
     */
    private function getDefaultOptions(CartInterface $cart)
    {
        $options = [
            'delivery_zone_1' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_1),
            'delivery_zone_2' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_2),
            'delivery_zone_3' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_3),
            'delivery_zone_4' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_4),
            'delivery_zone_5' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_5),
            'delivery_zone_6' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_6),
            'delivery_zone_7' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_7),
            'delivery_zone_8' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_8),
            'delivery_zone_9' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_9),
            'delivery_zone_10' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_10),
            'delivery_zone_11' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_11),
            'delivery_zone_12' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_12),
            'delivery_zone_13' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_13),
            'delivery_zone_14' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_14),
            'delivery_zone_15' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_15),
            'delivery_zone_16' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_16),
            'delivery_zone_17' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_17),
            'delivery_zone_18' => $this->calculator()->getZonePrice($cart, OrderCalculator::DELIVERY_ZONE_18),
            'express_delivery_factor' => 1 + $this->options()->getValue(OrderCalculator::EXPRESS_DELIVERY) / 100,
            'translator' => $this->translator(),
            'weekend_dates' => $this->options()->getValue(OrderCalculator::WEEKEND_DATES),
        ];

        return $options;
    }

    /**
     * Отправка e-mail
     * @param Order $order
     * @param Request $request
     */
    private function sendMessages(Order $order, Request $request)
    {
        try {
            // Отправка e-mail администратору
            $message = new Message($request);
            $message
                ->setSubject('Заказ ' . $order->getId() . '.')
                ->setTemplate('@PostroykaApp/Mail/order_manager.html.twig', ['order' => $order])
                ->setTag('order_created');
            $this->mailer()->send($message);


            // Отправка e-mail клиенту
            $optionEmail = $order->getOptions()->get('email');
            if ($optionEmail && $optionEmail->getValue()) {
                $message = new Message($request);
//                dd($order);
                $message
                    ->setSubject('[Postroyka.by] Заказ ' . $order->getId() . '.')
                    ->setHeaderTo($optionEmail->getValue())
                    ->setTemplate('@PostroykaApp/Mail/order.html.twig', ['order' => $order])
                    ->setTag('client');
                $this->mailer()->send($message);
            }
        } catch (\Exception $e) {
        }
    }
}

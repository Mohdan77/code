<?php

namespace Postroyka\AppBundle\Service;

use BeGateway\GetPaymentToken;
use BeGateway\Logger;
use BeGateway\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Submarine\OrdersBundle\Entity\CardPayment;
use Submarine\OrdersBundle\Entity\Order;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CardPaymentService
{
    private $em;
    private $request;
    private $router;
    private $translator;

    public function __construct(EntityManagerInterface $em, RequestStack $requestStack, RouterInterface $router, TranslatorInterface $translator, array $config)
    {
        $this->em = $em;
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
        $this->translator = $translator;

        Settings::$shopId = $config['shop_id'];
        Settings::$shopKey = $config['shop_secret_key'];
        Settings::$gatewayBase = $config['gateway_base'];
        Settings::$checkoutBase = $config['checkout_base'];

        Logger::getInstance()->setLogLevel(Logger::INFO);
    }

    public function handleOrder(Order $order, FormInterface $form)
    {
        $transaction = new GetPaymentToken;

        $successUrl = $this->request->getScheme() . '://' . $this->request->getHttpHost() .
            $this->router->generate('order_card_payment_success', [
                'orderId' => $order->getId()
            ]);

        $transaction->money->setAmount($order->getPayPrice());
        $transaction->money->setCurrency('BYN');
        $transaction->setDescription('заказ №' . $order->getId());
        $transaction->setTrackingId($order->getId());
        $transaction->setLanguage('ru');
        $transaction->customer->setEmail($form->get('email')->getData());
        $transaction->setSuccessUrl($successUrl);

        $response = $transaction->submit();

        $cardPayment = new CardPayment();
        $order->setCardPayment($cardPayment);

        $order->setPaymentMethod($this->translator->trans(OrderCalculator::ORDER_PAYMENT_CARD));

        if ($response->isSuccess()) {
            $cardPayment->setRedirectUrl($response->getRedirectUrl());
        } else {
            $cardPayment->setHasError($response->isError());
            throw new \Exception($response->getMessage());
        }

        $this->em->persist($cardPayment);
        $this->em->persist($order);
        $this->em->flush();
    }
}

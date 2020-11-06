<?php

namespace Postroyka\ApiBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\ApiBundle\Libs\Bitrix24;
use Submarine\OrdersBundle\Event\OrderEvent;
use Submarine\PagesBundle\Entity\Page;

/**
 * Взаимодействие с API Битрикс
 */
class OrderListener
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ExtendedUserProvider
     */
    private $extendedUserProvider;

    public function __construct(EntityManagerInterface $entityManager, ExtendedUserProvider $extendedUserProvider)
    {
        $this->entityManager = $entityManager;
        $this->extendedUserProvider = $extendedUserProvider;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderCreate(OrderEvent $event)
    {
        $order = $event->getOrder();
        $extendedUser = $this->extendedUserProvider->getByUser($order->getUser());
        $options = $order->getOptions();

        $b24api = new Bitrix24([
            'domain' => 'https://crm.postroyka.by',
            'auth' => 'nadclykiua0jjkx6',
        ]);

        $contact_data['fields'] = [
            'NAME' => $extendedUser->getFirstName() ?: 'Не указано',
            'SECOND_NAME' => $extendedUser->getSecondName() ?: '',
            'LAST_NAME' => '',
            'EMAIL' => [[
                'VALUE' => $options->get('email')->getValue(),
                'VALUE_TYPE' => 'WORK'
            ]],
            'PHONE' => [[
                'VALUE' => preg_replace("/[^\d]/", '', $options->get('phone')->getValue()),
                'VALUE_TYPE' => 'WORK'
            ]],
            'ADDRESS' => $options->get('address')->getValue(),
        ];

        $contact_id = $b24api->getOrCreateContact($contact_data);

        $order_to_bitrix = [
            'TITLE' => 'Заказ №' . $order->getId() . ' на Postroyka.by',
            'CATEGORY_ID' => 0,
            'CONTACT_ID' => $contact_id,
            'ORIGIN_ID' => $order->getId(),
            'COMMENTS' => $options->get('comment')->getValue(),
        ];

        $deal_id = $b24api->addDeal($order_to_bitrix);

        if ($deal_id) {
            $products = [];

            foreach ($order->getItems() as $orderItem) {
                $temp_product = $b24api->getProduct(['XML_ID' => $orderItem->getEntityId()]);

                /** @var Page $page */
                $page = $this->entityManager->getRepository($orderItem->getEntityName())->find($orderItem->getEntityId());
                $measure_name = $page->getField('unit')->getValue();

                if ($temp_product) {
                    $products[] = [
                        'PRODUCT_ID' => $temp_product[0]['ID'],
                        'PRICE' => $orderItem->getUnitPrice(),
                        'QUANTITY' => $orderItem->getQuantity(),
                        'MEASURE_NAME' => $measure_name,
                    ];
                } else {
                    $products[] = [
                        'PRODUCT_NAME' => $orderItem->getTitle(),
                        'PRICE' => $orderItem->getUnitPrice(),
                        'QUANTITY' => $orderItem->getQuantity(),
                        'MEASURE_NAME' => $measure_name,
                    ];
                }
            }

            $b24api->addDealProductsRow($deal_id, $products);
        }
    }
}
<?php

namespace Postroyka\AppBundle\Provider;

use Submarine\OrdersBundle\Entity\Order;
use Submarine\OrdersBundle\Provider\OrderProvider;
use Submarine\UsersBundle\Entity\User;

class OrdersProvider extends OrderProvider
{
    /**
     * Новые и обновленные заказы с указанной даты
     * @param \DateTime $since
     * @return \Doctrine\ORM\Query
     */
    public function getUpdatedOrdersQuery(\DateTime $since)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('o', 'status', 'items')
            ->from(Order::class, 'o')
            ->leftJoin('o.status', 'status')
            ->leftJoin('o.items', 'items')
            ->where('o.updatedAt > :since')
            ->setParameter('since', $since)
            ->getQuery();
    }

    /**
     * Заказы пользователя
     * @param User $user
     * @return \Doctrine\ORM\Query
     */
    public function getUserOrdersQuery(User $user)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.id', 'DESC')
            ->getQuery();
    }
}
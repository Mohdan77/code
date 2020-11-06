<?php

namespace Postroyka\AccountBundle\Provider;

use Doctrine\ORM\Query;
use Postroyka\AccountBundle\Entity\ExtendedUser;
use Submarine\CoreBundle\Provider\AbstractProvider;
use Submarine\UsersBundle\Entity\User;

class ExtendedUserProvider extends AbstractProvider
{
    /**
     * @param $id
     * @return ExtendedUser
     */
    public function get($id)
    {
        $userExtended = $this
            ->entityManager()
            ->getRepository(ExtendedUser::class)
            ->find($id);

        return $userExtended ?: new ExtendedUser(new User());
    }

    /**
     * @param User|null $user
     * @return ExtendedUser
     */
    public function getByUser(User $user = null)
    {
        if (!$user || !$user->getId()) {
            return new ExtendedUser(new User());
        }

        return $this->get($user->getId());
    }

    /**
     * Все расширенные пользователи
     * @return Query
     */
    public function getAllQuery()
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('e, u')
            ->from(ExtendedUser::class, 'e')
            ->leftJoin('e.user', 'u')
            ->orderBy('u.username')
            ->getQuery();
    }

    /**
     * Все расширенные пользователи
     * @param \DateTime $since
     * @return Query
     */
    public function getUpdatedQuery(\DateTime $since)
    {
        return $this
            ->entityManager()
            ->createQueryBuilder()
            ->select('e, u')
            ->from(ExtendedUser::class, 'e')
            ->leftJoin('e.user', 'u')
            ->where('e.updatedAt > :sine')
            ->setParameter('sine', $since)
            ->getQuery();
    }


    public function getStatiscticsUsers($param)
    {
        if (!$param['users_statistics_select']) return [];

        $users = $this->getAllQuery()->getResult();

        foreach ($users as $key => $user) {
            if (is_null($user->getCart())) unset($users[$key]);
        }

        if ($param['users_statistics_select'] == 1){
            usort($users, function ($a, $b){
                $c = count($a->getCart()->getItems()->toArray());
                $d = count(($b->getCart()->getItems()->toArray()));

                return $c < $d;
            });

        }

        if ($param['users_statistics_select'] == 2){

            usort($users, function ($a, $b){
                $c = $a->getCart()->getTotal();
                $d = $b->getCart()->getTotal();

                return $c < $d;
            });
        }

        if ($param['users_statistics_select'] == 3){
            foreach ($users as $key => $user){
                $carts = $user->getCart()->getItems()->toArray();
                $check = 0;
                foreach ($carts as $hash => $cart){
                    $check += $cart->getUnitPrice() * $cart->getQuantity();
                }
                $users[$key]->average = $check / count($carts);
            }
            usort($users, function ($a, $b){
                return $a->average < $b->average;
            });
        }

        if ($param['users_statistics_select'] == 4){
            usort($users, function ($a, $b){
                $c = $a->getCart()->getCountQuantity();
                $d = $b->getCart()->getCountQuantity();

                return $c < $d;
            });
        }

        if ($param['users_statistics_select'] == 5){
            $users = $this->getNextMonthUsers();

            foreach ($users as $key => $item){
                $data[$key]['username'] = $item->getUsername();
                $data[$key]['email'] = $item->getEmail();
                $data[$key]['result'] = $item->getDateTimeCreated()->format('d-m-Y');
                $data[$key]['countUsers'] = count($users);
            }

            return $data;
        }

        if($param['users_statistics_select'] == 6){

            foreach ($users as $key => $user) {
                $carts = $user->getCart()->getItems()->toArray();
                foreach ($carts as $hash => $cart){
                    if (!isset($data['product'][$cart->getTitle()])) $data['product'][$cart->getTitle()] = 0;
                    $data['product'][$cart->getTitle()] += (int)$cart->getQuantity();
                }
            }

            array_multisort($data['product'], SORT_DESC);

            return $data;
        }

        $data = [];
        $users = array_slice($users, 0, 10);

        foreach ($users as $key => $value){
            $data[$key]['username'] = $value->getUser()->getUsername();
            $data[$key]['email'] = $value->getUser()->getEmail();
            if ($param['users_statistics_select'] == 1){
                $data[$key]['result'] = count($value->getCart()->getItems()->toArray());

            }
            if ($param['users_statistics_select'] == 2){
                $data[$key]['result'] = $value->getCart()->getTotal();
            }
            if ($param['users_statistics_select'] == 3){
                $data[$key]['result'] = round($value->average, 2);
            }
            if ($param['users_statistics_select'] == 4){
                $data[$key]['result'] = $value->getCart()->getCountQuantity();
            }
        }

dd($data);
        return $data;
    }

    public function getMaxOrders($users)
    {

    }

    public function getNextMonthUsers()
    {
        $date = new \DateTime();
        $month = $date->modify('-1 month')->format('Y-m-d');

        $dql = '
        SELECT u
        FROM '.User::class.' u
        WHERE u.dateTimeCreated > :val';

        $query = $this->entityManager()
            ->createQuery($dql)
            ->setParameter('val', $month);

        return $query->getResult();
    }
}
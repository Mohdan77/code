<?php

namespace Postroyka\AccountBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Submarine\ConfirmationBundle\Event\ConfirmEvent;
use Submarine\UsersBundle\Service\UsersService;

class AccountChangeEmailListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UsersService
     */
    private $usersService;

    /**
     * @var ExtendedUserProvider
     */
    private $extendedUserProvider;

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param UsersService $usersService
     */
    public function setUsersService($usersService)
    {
        $this->usersService = $usersService;
    }

    /**
     * @param ExtendedUserProvider $extendedUserProvider
     */
    public function setExtendedUserProvider($extendedUserProvider)
    {
        $this->extendedUserProvider = $extendedUserProvider;
    }

    /**
     * @param ConfirmEvent $event
     */
    public function onAccountChangeEmail(ConfirmEvent $event)
    {
        if (!$event->getUserId()) {
            return;
        }

        $user = $this->usersService->getUser($event->getUserId());

        if (!$user->getId()) {
            return;
        }

        $data = $event->getConfirmCode()->getData();
        $checkUser = $this->usersService->getUserByEmail($data['email']);

        if ($checkUser->getId()) {
            return;
        }

        $user->setEmail($data['email']);
        $extendedUser = $this->extendedUserProvider->getByUser($user);
        $extendedUser->setUpdatedAt();

        $this->entityManager->flush();
    }
} 
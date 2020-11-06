<?php

namespace Postroyka\AccountBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Submarine\ConfirmationBundle\Event\ConfirmEvent;
use Submarine\UsersBundle\Events\Users\UserDefaultEvent;
use Submarine\UsersBundle\Events\UsersEvents;
use Submarine\UsersBundle\Service\UsersService;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class AccountRegistrationConfirmListener
{
    /**
     * @var UsersService
     */
    private $usersService;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var EventDispatcher
     */
    private $dispatcher;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param EventDispatcher $dispatcher
     */
    public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param UsersService $usersService
     */
    public function setUsersService($usersService)
    {
        $this->usersService = $usersService;
    }

    /**
     * @param TokenStorage $tokenStorage
     */
    public function setTokenStorage($tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @param Session $session
     */
    public function setSession($session)
    {
        $this->session = $session;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param ConfirmEvent $event
     */
    public function onAccountRegistrationConfirm(ConfirmEvent $event)
    {
        if (!$event->getUserId()) {
            return;
        }

        $user = $this->usersService->getUser($event->getUserId());

        if (!$user->getId()) {
            return;
        }

        $user->setConfirmed(true);
        $this->entityManager->flush($user);

        $this->dispatcher->dispatch(UsersEvents::onUserChange, new UserDefaultEvent($user));

        //Auto login
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $this->session->set('_security_main', serialize($token));
    }
}
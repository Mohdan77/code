<?php

namespace Postroyka\AccountBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Submarine\ConfirmationBundle\Event\ConfirmEvent;
use Submarine\UsersBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Encoder\EncoderFactory;
use Symfony\Component\HttpFoundation\Session\Session;

class RestorePasswordListener
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EncoderFactory
     */
    private $encoderFactory;

    /**
     * @var TokenStorage
     */
    private $tokenStorage;

    /**
     * @var Session
     */
    private $session;

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
     * @param EncoderFactory $encoderFactory
     */
    public function setEncoderFactory($encoderFactory)
    {
        $this->encoderFactory = $encoderFactory;
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
    public function onAccountRestorePassword(ConfirmEvent $event)
    {
        $data = $event->getConfirmCode()->getData();
        $user = $this->entityManager->getRepository(User::class)->findOneByEmail($data['email']);

        if (!$user) {
            return;
        }

        $encoder = $this->encoderFactory->getEncoder($user);
        $passHash = $encoder->encodePassword($data['password'], $user->getSalt());
        $user->setPassword($passHash);

        $this->entityManager->flush($user);

        //Auto login
        $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
        $this->session->set('_security_main', serialize($token));
    }
}
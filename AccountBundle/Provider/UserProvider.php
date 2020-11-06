<?php

namespace Postroyka\AccountBundle\Provider;

use Doctrine\ORM\NoResultException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Submarine\UsersBundle\Entity\User;
use Doctrine\ORM\EntityManager;

class UserProvider implements UserProviderInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Loads the user for the given username.
     * This method must throw UsernameNotFoundException if the user is not
     * found.
     * @param string $username The username
     * @return UserInterface
     * @see UsernameNotFoundException
     * @throws UsernameNotFoundException if the user is not found
     */
    public function loadUserByUsername($username)
    {
        return $this->loadUserByEmail($username);
    }

    /**
     * Загрузка пользователя по e-mail
     * @param $email
     * @return User
     */
    private function loadUserByEmail($email)
    {
        $dql = '
        SELECT u
        FROM ' . User::entityName() . ' u
        WHERE u.email = :email';

        $q = $this->entityManager
            ->createQuery($dql)
            ->setParameter('email', $email);
        try {
            $user = $q->getSingleResult();
        } catch (NoResultException $e) {
            return new User();
        }

        return $user;
    }

    /**
     * Refreshes the user for the account interface.
     * It is up to the implementation to decide if the user data should be
     * totally reloaded (e.g. from the database), or if the UserInterface
     * object can just be merged into some internal array of users / identity
     * map.
     * @param UserInterface|User $user
     * @return UserInterface
     * @throws UnsupportedUserException if the account is not supported
     */
    public function refreshUser(UserInterface $user)
    {
        if ($user->getId()) {
            $userRegistered = $this->loadUserByEmail($user->getEmail());

            if ($userRegistered->getId()) {
                return $userRegistered;
            }
        }

        return $user;
    }

    /**
     * Whether this provider supports the given user class
     *
     * @param string $class
     *
     * @return Boolean
     */
    public function supportsClass($class)
    {
        return $class === User::entityName() || is_subclass_of($class, User::entityName());
    }
}
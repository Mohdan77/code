<?php

namespace Postroyka\AccountBundle\Service;

use Doctrine\ORM\EntityManager;
use Postroyka\AccountBundle\Entity\ExtendedUser;
use Postroyka\AccountBundle\Entity\RegistrationUser;
use Submarine\CoreBundle\AccessControl\Role;
use Submarine\CoreBundle\Notifier\NotifierProvider;
use Submarine\UsersBundle\Entity\User;
use Submarine\UsersBundle\Service\UsersService;

class RegistrationService
{
    /**
     * @var UsersService
     */
    private $userService;

    /**
     * @var NotifierProvider
     */
    private $notifier;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @param UsersService $userService
     */
    public function setUserService($userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param NotifierProvider $notifier
     */
    public function setNotifier($notifier)
    {
        $this->notifier = $notifier;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param RegistrationUser $registrationUser
     * @param bool $confirmed
     * @return ExtendedUser|null
     */
    public function registerUser(RegistrationUser $registrationUser, $confirmed = false)
    {
        $user = $this->createUser($registrationUser, $confirmed);
        if (!$user) {
            return null;
        }

        $userExtended = $this->createUserExtended($registrationUser, $user);
        if (!$userExtended) {
            try {
                $this->entityManager->remove($user);
                $this->entityManager->flush();
            } catch (\Exception $exc) {
                return null;
            }

            return null;
        }

        return $userExtended;
    }

    /**
     * @param RegistrationUser $registrationUser
     * @param bool $confirmed
     * @return null|User
     */
    private function createUser(RegistrationUser $registrationUser, $confirmed = false)
    {
        $user = new User();
        $user->setUsername($registrationUser->getUsername());
        $user->setEmail($registrationUser->getEmail());
        $user->setEnabled(true);
        $user->setConfirmed($confirmed);

        $passwordHash = $this->userService->encodeUserPassword($user, $registrationUser->getPassword());
        $user->setPassword($passwordHash);

        $checkEmail = $this->userService->getUserByEmail($registrationUser->getEmail())->getId();

        if ($checkEmail) {
            $this->notifier->error('message.error.registration_email_not_unique');

            return null;
        }

        if ($registrationUser->getAuthAccount()->count()) {
            foreach ($registrationUser->getAuthAccount() as $account) {
                $account->setUser($user);
                $user->getAuthAccounts()->add($account);
            }
        }

        $group = $this->userService->getGroup(Role::ROLE_USER);
        $user->getGroups()->add($group);

        try {
            $this->entityManager->persist($user);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            return null;
        }

        return $user;
    }

    /**
     * @param RegistrationUser $registrationUser
     * @param User $user
     * @return ExtendedUser|null
     */
    private function createUserExtended(RegistrationUser $registrationUser, User $user)
    {
        $userExtended = new ExtendedUser($user);
        $userExtended->setFirstName($registrationUser->getFirstName());
        $userExtended->setSecondName($registrationUser->getSecondName());

        try {
            $this->entityManager->persist($userExtended);
            $this->entityManager->flush();
        } catch (\Exception $exc) {
            return null;
        }

        return $userExtended;
    }
}
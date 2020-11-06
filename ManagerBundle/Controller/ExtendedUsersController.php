<?php

namespace Postroyka\ManagerBundle\Controller;

use Postroyka\AccountBundle\Entity\RegistrationUser;
use Postroyka\AccountBundle\Form\RegistrationForm;
use Postroyka\AccountBundle\Manager\ExtendedUserManager;
use Postroyka\AccountBundle\Provider\ExtendedUserProvider;
use Postroyka\AccountBundle\Service\RegistrationService;
use Postroyka\ManagerBundle\Form\ExtendedUserForm;
use Submarine\CoreBundle\Controller\AbstractManagerController;
use Symfony\Component\HttpFoundation\Request;

class ExtendedUsersController extends AbstractManagerController
{
    const USERS_PER_PAGE = 20;

    /**
     * @return ExtendedUserProvider
     */
    private function extendedUsersProvider()
    {

        return $this->get('postroyka_account.extended_user_provider');
    }

    /**
     * @return RegistrationService
     */
    private function registrationService()
    {
        return $this->get('postroyka_account.registration_service');
    }

    /**
     * @return ExtendedUserManager
     */
    private function extendedUserManager()
    {
        return $this->get('postroyka_account.extended_user_manager');
    }

    public function listAction(Request $request)
    {
        $users = $this->extendedUsersProvider()->getAllQuery();

        $data = [
            'users' => $this->paginator()->paginate($users, $request->get('page', 1), self::USERS_PER_PAGE),
        ];

        return $this->renderPage('PostroykaManagerBundle:ExtendedUsers:list.html.twig', $data);
    }

    /**
     * Редактировние пользователя
     */
    public function editAction(Request $request, $id)
    {
        $user = $this->extendedUsersProvider()->get($id);

        if (!$user->getId()) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(ExtendedUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->extendedUserManager()->save($user);
                $this->notifier()->success();

                return $this->redirectToRoute('postroyka_manager_users_edit', ['id' => $user->getId()]);
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data = [
            'user' => $user,
            'form' => $form->createView(),
        ];

        return $this->renderPage('PostroykaManagerBundle:ExtendedUsers:edit.html.twig', $data);
    }

    /**
     * Создание пользователя
     */
    public function createAction(Request $request)
    {
        $userRegistration = new RegistrationUser();
        $form = $this->createForm(RegistrationForm::class, $userRegistration);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->registrationService()->registerUser($userRegistration, true);

            if ($user) {
                $this->notifier()->success();

                return $this->redirectToRoute('postroyka_manager_users_edit', ['id' => $user->getId()]);
            } else {
                $this->notifier()->error();
            }
        }

        $data = [
            'form' => $form->createView(),
        ];

        return $this->renderPage('PostroykaManagerBundle:ExtendedUsers:create.html.twig', $data);
    }

    public function statisticsAction(Request $request)
    {
        $data = [];

        if ($post = $request->request->all()){
            $data['users'] = $this->extendedUsersProvider()->getStatiscticsUsers($post);
        }
        if (isset($data['users']['product'])){
            $data['product'] = $data['users']['product'];
            unset($data['users']['product']);
        }

        $data['users_statistics_select'] = isset($post['users_statistics_select']) ? $post['users_statistics_select'] : '';

        return $this->renderPage('PostroykaManagerBundle:StatisticsUsers:statistics_users.html.twig', $data);
    }
}
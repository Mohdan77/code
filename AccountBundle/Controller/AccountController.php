<?php

namespace Postroyka\AccountBundle\Controller;

use Postroyka\AccountBundle\Events\AccountEvents;
use Postroyka\AccountBundle\Form\EmailChangeForm;
use Postroyka\AccountBundle\Form\PasswordChangeForm;
use Postroyka\AccountBundle\Form\ProfileForm;
use Postroyka\AccountBundle\Mailer\AccountMailer;
use Postroyka\AccountBundle\Manager\ExtendedUserManager;
use Postroyka\AppBundle\Controller\AbstractController;
use Postroyka\AppBundle\Provider\OrdersProvider;
use Submarine\UsersBundle\Service\UsersService;
use Symfony\Component\HttpFoundation\Request;

class AccountController extends AbstractController
{
    const ORDERS_PER_PAGE = 20;

    /**
     * @return UsersService
     */
    private function users()
    {
        return $this->get('submarine.users');
    }

    /**
     * @return ExtendedUserManager
     */
    private function extendedUserManager()
    {
        return $this->get('postroyka_account.extended_user_manager');
    }

    /**
     * @return AccountMailer
     */
    private function accountMailer()
    {
        return $this->get('postroyka_account.account_mailer');
    }

    /**
     * @return OrdersProvider
     */
    private function orders()
    {
        return $this->get('postroyka_app.orders_provider');
    }

    public function __construct()
    {
        parent::__construct();

        $this->getMetaTags()->setTitle('Личный кабинет');
    }

    /**
     * История заказов
     */
    public function ordersAction(Request $request)
    {
        $orders = $this->orders()->getUserOrdersQuery($this->getUser());

        $data = [
            'orders' => $this->paginator()->paginate($orders, $request->get('page', 1), self::ORDERS_PER_PAGE),
            'right_menu' => 'orders',
        ];

        return $this->renderPage('@PostroykaAccount/Account/orders.html.twig', $data);
    }

    /**
     * Просмотр заказа
     */
    public function orderAction($id)
    {
        $order = $this->orders()->get($id);

        if (!$order->getId() || $order->getUser()->getId() !== $this->getUser()->getId()) {
            throw  $this->createNotFoundException();
        }

        $data = [
            'order' => $order,
            'right_menu' => 'orders',
        ];

        return $this->renderPage('@PostroykaAccount/Account/order.html.twig', $data);
    }

    /**
     * Профиль
     */
    public function profileAction(Request $request)
    {
        $extendedUser = $this->getExtendedUser();
        $form = $this->createForm(ProfileForm::class, $extendedUser);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->extendedUserManager()->save($extendedUser);
                $this->notifier()->success();

                return $this->redirectToRoute('account_profile');
            } catch (\Exception $e) {
                $this->notifier()->error();
            }
        }

        $data = [
            'form' => $form->createView(),
            'right_menu' => 'profile',
        ];

        return $this->renderPage('@PostroykaAccount/Account/profile.html.twig', $data);
    }

    /**
     * Изменение пароля
     */
    public function passwordAction(Request $request)
    {
        $form = $this->createForm(PasswordChangeForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $extendedUser = $this->getExtendedUser();
            $data = $form->getData();
            $currentPassword = $this->users()->encodeUserPassword($extendedUser->getUser(), $data['password']);

            if ($extendedUser->getUser()->getPassword() === $currentPassword) {
                $newPassword = $this->users()->encodeUserPassword($extendedUser->getUser(), $data['password_new']);
                $extendedUser->getUser()->setPassword($newPassword);

                try {
                    $this->extendedUserManager()->save($extendedUser);
                    $this->notifier()->success('message.success.password_changed');

                    return $this->redirect($this->generateUrl('account_password'));
                } catch (\Exception $e) {
                    $this->notifier()->error();
                }
            } else {
                $this->notifier()->error('message.error.current_password_not_valid');
            }
        }

        $data = [
            'form' => $form->createView(),
            'right_menu' => 'password',
        ];

        return $this->renderPage('@PostroykaAccount/Account/password.html.twig', $data);
    }

    /**
     * Изменение E-mail
     */
    public function emailAction(Request $request)
    {
        $form = $this->createForm(EmailChangeForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            if ($this->users()->getUserByEmail($data['email'])->getId()) {
                $this->notifier()->error('message.error.registration_email_not_unique');

                return $this->redirect($this->generateUrl('account_email'));
            }

            $userExtended = $this->getExtendedUser();
            $currentPassword = $this->users()->encodeUserPassword($userExtended->getUser(), $data['password']);

            if ($userExtended->getUser()->getPassword() === $currentPassword) {
                $code = $this->get('submarine.confirmation')->createCodeConfirm(
                    AccountEvents::CHANGE_EMAIL,
                    ['email' => $data['email']],
                    $userExtended->getUser()->getId()
                );

                if ($this->accountMailer()->sendEmailChangeConfirm($userExtended->getUser(), $code)) {
                    $this->notifier()->success('message.success.confirm_change_email');
                } else {
                    $this->notifier()->error('message.error.email_not_send');
                }

                return $this->redirect($this->generateUrl('account_email'));
            } else {
                $this->notifier()->error('message.error.current_password_not_valid');
            }
        }

        $data = [
            'form' => $form->createView(),
            'right_menu' => 'email',
        ];

        return $this->renderPage('@PostroykaAccount/Account/email.html.twig', $data);
    }
}
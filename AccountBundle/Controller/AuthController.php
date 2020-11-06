<?php

namespace Postroyka\AccountBundle\Controller;

use Postroyka\AccountBundle\Events\AccountEvents;
use Postroyka\AccountBundle\Form\LoginForm;
use Postroyka\AccountBundle\Form\RestorePasswordForm;
use Postroyka\AccountBundle\Mailer\AccountMailer;
use Postroyka\AppBundle\Controller\AbstractController;
use Submarine\ConfirmationBundle\Service\ConfirmationService;
use Submarine\CoreBundle\AccessControl\Role;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;

class AuthController extends AbstractController
{
    /**
     * @return AccountMailer
     */
    private function accountMailer()
    {
        return $this->get('postroyka_account.account_mailer');
    }

    /**
     * @return ConfirmationService
     */
    private function confirmation()
    {
        return $this->get('submarine.confirmation');
    }

    /**
     * Логин
     */
    public function loginAction(Request $request)
    {
        if ($this->isGranted(Role::ROLE_USER)) {
            return $this->redirect($this->generateUrl('account'));
        }

        $this->getMetaTags()->setDescription('Личный кабинет. Войти в аккаунту postroyka.by. Вход в личный кабинет аккаунта строительного интернет-магазина postroyka.by.');

        $data = [];
        $data['showBlockConfirm'] = false;

        $lastEmail = $request->getSession()->get('email');
        $codeError = Security::AUTHENTICATION_ERROR;

        if ($request->attributes->has($codeError) || $request->getSession()->has($codeError)) {
            $request->getSession()->remove($codeError);
            $lastEmail = $request->getSession()->get(Security::LAST_USERNAME);
            $user = $this->get('submarine.users')->getUserByEmail($lastEmail);

            if ($user->getId() && !$user->isConfirmed()) {
                $data['showBlockConfirm'] = true;
            } elseif ($user->getId() && !$user->isEnabled()) {
                $this->notifier()->warning('message.error.user_disabled');
            } else {
                $this->notifier()->error('message.error.login_failed');
            }
        }

        $data['_email'] = $lastEmail;
        $data['_remember_me'] = true;

        $form = $this->createForm(LoginForm::class, $data);

        $data['form'] = $form->createView();
        $data['lastEmail'] = $lastEmail;

        return $this->renderPage('@PostroykaAccount/Auth/login.html.twig', $data);
    }

    /**
     * Восстановление пароля
     */
    public function restorePasswordAction(Request $request)
    {
        $this->getMetaTags()->setDescription('Не получается войти? Забыли пароль к аккаунту postroyka.by? С помощью восстановления пароля можете восстановить пароль.');

        $data = [];
        $form = $this->createForm(RestorePasswordForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $user = $this->get('submarine.users')->getUserByEmail($formData['email']);

            if (!$user->getId()) {
                $this->notifier()->error('Пользователь с таким e-mail не найден.');

                return $this->redirect($this->generateUrl('restore_password'));
            }

            $code = $this->confirmation()->createCodeConfirm(AccountEvents::RESTORE_PASSWORD, $formData, $user->getId());

            if (!$this->accountMailer()->sendRestorePasswordConfirm($user, $code, $formData['password'])) {
                $this->notifier()->error('message.error.email_not_send');
            } else {
                $this->notifier()->success('message.success.password_restore_must_confirmed');

                return $this->redirectToRoute('login');
            }
        }

        $data['form'] = $form->createView();

        return $this->renderPage('@PostroykaAccount/Auth/restore_password.html.twig', $data);
    }
}
<?php

namespace Postroyka\AccountBundle\Controller;

use Postroyka\AccountBundle\Entity\RegistrationUser;
use Postroyka\AccountBundle\Events\AccountEvents;
use Postroyka\AccountBundle\Form\RegistrationForm;
use Postroyka\AccountBundle\Mailer\AccountMailer;
use Postroyka\AccountBundle\Service\RegistrationService;
use Postroyka\AppBundle\Controller\AbstractController;
use Submarine\ConfirmationBundle\Service\ConfirmationService;
use Submarine\UsersBundle\Service\UsersService;
use Symfony\Component\HttpFoundation\Request;

class RegistrationController extends AbstractController
{
    /**
     * @return RegistrationService
     */
    private function registrationService()
    {
        return $this->get('postroyka_account.registration_service');
    }

    /**
     * @return ConfirmationService
     */
    private function confirmation()
    {
        return $this->get('submarine.confirmation');
    }

    /**
     * @return AccountMailer
     */
    private function accountMailer()
    {
        return $this->get('postroyka_account.account_mailer');
    }

    /**
     * @return UsersService
     */
    private function users()
    {
        return $this->get('submarine.users');
    }

    /**
     * Регистрация
     */
    public function registrationAction(Request $request)
    {
        $data = [];
        $userRegistration = new RegistrationUser();
        $form = $this->createForm(RegistrationForm::class, $userRegistration);

        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $request->getSession()->set('email', $userRegistration->getEmail());
                $userExtended = $this->registrationService()->registerUser($userRegistration);

                if ($userExtended) {
                    $code = $this->confirmation()->createCodeConfirm(
                        AccountEvents::REGISTRATION_CONFIRM,
                        ['email' => $userExtended->getEmail()],
                        $userExtended->getUser()->getId()
                    );

                    if (!$this->accountMailer()->sendRegistrationConfirm($userExtended->getUser(), $code)) {
                        $this->notifier()->error('message.error.email_not_send');
                    }

                    return $this->redirect($this->generateUrl('registration_success'));
                } else {
                    $this->notifier()->error();
                }
            } else {
                $this->notifier()->error('message.error.form_invalid');
            }
        }

        $data['form'] = $form->createView();

        return $this->renderPage('@PostroykaAccount/Registration/registration.html.twig', $data);
    }

    /**
     * Успешная регистрация
     */
    public function successAction(Request $request)
    {
        if (!$request->getSession()->get('email')) {
            throw $this->createNotFoundException();
        }

        return $this->renderPage('@PostroykaAccount/Registration/registration_success.html.twig', []);
    }

    /**
     * Отправка повторного письма активации
     */
    public function sendNewActivationCodeAction(Request $request)
    {
        $email = $request->get('email');
        $user = $this->users()->getUserByEmail($email);

        if (!$user->getId() || $user->isConfirmed()) {
            $this->notifier()->error('message.error.user_not_found');

            return $this->redirect($this->generateUrl('login'));
        }

        $code = $this->confirmation()->createCodeConfirm(
            AccountEvents::REGISTRATION_CONFIRM,
            ['email' => $user->getEmail()],
            $user->getId()
        );

        if (!$this->accountMailer()->sendRegistrationConfirm($user, $code)) {
            $this->notifier()->error('message.error.email_not_send');
        } else {
            $this->notifier()->success('message.success.new_code_confirm_send');
        }

        return $this->redirect($this->generateUrl('login'));
    }
}
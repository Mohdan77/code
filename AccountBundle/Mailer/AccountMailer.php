<?php

namespace Postroyka\AccountBundle\Mailer;

use Submarine\ConfirmationBundle\Entity\CodeConfirm;
use Submarine\CoreBundle\Options\OptionsProvider;
use Submarine\MailerBundle\Entity\Message;
use Submarine\MailerBundle\Provider\MailerProvider;
use Submarine\UsersBundle\Entity\User;

class AccountMailer
{
    /**
     * @var MailerProvider
     */
    private $mailer;

    /**
     * @var OptionsProvider
     */
    private $options;

    /**
     * @param MailerProvider $mailer
     */
    public function setMailer($mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @param OptionsProvider $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    /**
     * Регистрация, подтверждение
     * @param User $user
     * @param CodeConfirm $code
     * @return bool
     */
    public function sendRegistrationConfirm(User $user, CodeConfirm $code)
    {
        return $this->send(
            $user->getEmail(),
            'mail.subject.registration',
            'PostroykaAccountBundle:Mail:registration.html.twig',
            [
                'user' => $user,
                'code' => $code->getCode(),
                'code_expired' => $code->getDateExpired()
            ]
        );
    }

    /**
     * Изменение e-mail
     * @param User $user
     * @param CodeConfirm $code
     * @return bool
     */
    public function sendEmailChangeConfirm(User $user, CodeConfirm $code)
    {
        return $this->send(
            $user->getEmail(),
            'mail.subject.change_email',
            'PostroykaAccountBundle:Mail:change_email.html.twig',
            [
                'user' => $user,
                'code' => $code->getCode(),
                'code_expired' => $code->getDateExpired()
            ]
        );
    }

    /**
     * Восстановление пароля
     * @param User $user
     * @param CodeConfirm $code
     * @param $password
     * @return bool
     */
    public function sendRestorePasswordConfirm(User $user, CodeConfirm $code, $password)
    {
        return $this->send(
            $user->getEmail(),
            'mail.subject.password_restore',
            'PostroykaAccountBundle:Mail:restore_password.html.twig',
            [
                'user' => $user,
                'password' => $password,
                'code' => $code->getCode(),
                'code_expired' => $code->getDateExpired()
            ]
        );
    }

    /**
     * Отправка e-mail
     * @param $to
     * @param $subject
     * @param $template
     * @param array $data
     * @return bool
     */
    protected function send($to, $subject, $template, array $data = [])
    {
        try {
            $message = new Message();

            $message
                ->setHeaderTo($to)
                ->setSubject($subject)
                ->setTemplate($template, $data);

            $this->mailer->send($message);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }
}
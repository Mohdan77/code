<?php

namespace Postroyka\AccountBundle\Events;

class AccountEvents
{
    /**
     * Пользователь активировал учетную запись
     */
    const REGISTRATION_CONFIRM = 'account.registration_confirmed';

    /**
     * Изменение e-mail
     */
    const CHANGE_EMAIL = 'account.changed_email';

    /**
     * Восстановление пароля
     */
    const RESTORE_PASSWORD = 'account.restore_password';
}
<?php

namespace Postroyka\AccountBundle\Manager;

use Postroyka\AccountBundle\Entity\ExtendedUser;
use Submarine\CoreBundle\Manager\AbstractManager;

class ExtendedUserManager extends AbstractManager
{
    protected function getClassName()
    {
        return ExtendedUser::class;
    }
}
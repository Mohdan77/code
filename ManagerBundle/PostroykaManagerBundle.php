<?php

namespace Postroyka\ManagerBundle;

use Submarine\CoreBundle\Bundles\AbstractSubmarineBundle;

class PostroykaManagerBundle extends AbstractSubmarineBundle
{
    public function getAliasName()
    {
        return 'postroyka_manager';
    }

    public function getVersion()
    {
        return '1.0';
    }

    public function getRoute()
    {
        return 'postroyka_manager_home';
    }
}

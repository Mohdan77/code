<?php

namespace Postroyka\ManagerBundle\DependencyInjection;

use Submarine\CoreBundle\AccessControl\Role;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('postroyka_manager')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('main_menu')->defaultFalse()->end()
            ->scalarNode('role_access')->defaultValue(Role::ROLE_ADMIN_BASE)->end()
            ->end();


        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.

        return $treeBuilder;
    }
}

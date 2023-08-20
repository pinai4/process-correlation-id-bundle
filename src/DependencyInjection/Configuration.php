<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('pinai4_process_correlation_id');
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
                ->booleanNode('enabled')
                    ->defaultTrue()
                    ->info('You can enable/disable all bundle features')
                ->end()
                ->scalarNode('log_field_name')
                    ->defaultValue('process_correlation_id')
                    ->cannotBeEmpty()
                    ->info('You can customize monolog field name which will show ProcessCorrelationId value')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

}
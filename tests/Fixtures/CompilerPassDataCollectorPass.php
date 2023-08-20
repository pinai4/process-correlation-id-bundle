<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPassDataCollectorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $data = [];
        foreach ($container->findTaggedServiceIds('messenger.bus') as $busId => $tags) {
            /*var_dump($container->getDefinition($busId)->getArgument(0)::class);
            var_dump($container->getDefinition($busId)->getArgument(0)->getValues());
            die();*/
            $busMiddlewares = array_map('strval', $container->getDefinition($busId)->getArgument(0)->getValues());
            $data['buses'][$busId] = $busMiddlewares;
        }

        $container
            ->register(CompilerPassDataCollector::class, CompilerPassDataCollector::class)
            ->setArgument(0, $data)
            ->setPublic(true);
    }

}
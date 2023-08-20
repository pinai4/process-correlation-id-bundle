<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class MessengerBusesAutoconfigForDefaultMiddlewaresStackPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('pinai4_process_correlation_id.id')) {
            return;
        }

        $addProcessCorrelationIdStampMiddleware = 'pinai4_process_correlation_id.messenger.add_stamp_middleware';
        $logProcessCorrelationIdMiddleware = 'pinai4_process_correlation_id.messenger.log_middleware';

        foreach ($container->findTaggedServiceIds('messenger.bus') as $busId => $tags) {
            $middlewareReferences = [];
            /** @var Reference[] $busMiddlewares */
            $busMiddlewares = $container->getDefinition($busId)->getArgument(0)->getValues();
            $needUpdateBus = false;
            foreach ($busMiddlewares as $key => $refMiddlewareId) {
                $middlewareReferences[(string) $refMiddlewareId] = $refMiddlewareId;
                if (isset($busMiddlewares[$key + 1])
                    && (string) $busMiddlewares[$key + 1] === 'messenger.middleware.dispatch_after_current_bus'
                    && $container->has($addProcessCorrelationIdStampMiddleware)) {
                    $middlewareReferences[$addProcessCorrelationIdStampMiddleware] = new Reference($addProcessCorrelationIdStampMiddleware);
                    $needUpdateBus = true;
                }

                if ((string) $refMiddlewareId === 'messenger.middleware.dispatch_after_current_bus'
                    && $container->has($logProcessCorrelationIdMiddleware)) {
                    $middlewareReferences[$logProcessCorrelationIdMiddleware] = new Reference(
                        $logProcessCorrelationIdMiddleware
                    );
                }
            }

            if ($needUpdateBus) {
                $container->getDefinition($busId)->replaceArgument(0, new IteratorArgument(array_values($middlewareReferences)));
            }
        }
    }
}

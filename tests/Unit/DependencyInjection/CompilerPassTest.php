<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Compiler\MessengerBusesAutoconfigForDefaultMiddlewaresStackPass;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Messenger\MessageBus;

class CompilerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MessengerBusesAutoconfigForDefaultMiddlewaresStackPass());
    }

    public function testWithDispatchAfterCurrentBusMiddleware(): void
    {
        $this->setDefinition('pinai4_process_correlation_id.id', new Definition());
        $this->setDefinition('pinai4_process_correlation_id.messenger.add_stamp_middleware', new Definition());
        $this->setDefinition('pinai4_process_correlation_id.messenger.log_middleware', new Definition());

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('messenger.middleware.dispatch_after_current_bus'),
            new Reference('middleware2'),
        ]);

        $this->registerService('bus', MessageBus::class)
            ->setArgument(0, $expectedArgument)
            ->addTag('messenger.bus');

        $this->compile();

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('pinai4_process_correlation_id.messenger.add_stamp_middleware'),
            new Reference('messenger.middleware.dispatch_after_current_bus'),
            new Reference('pinai4_process_correlation_id.messenger.log_middleware'),
            new Reference('middleware2'),
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('bus', 0, $expectedArgument);
    }

    public function testWithoutDispatchAfterCurrentBusMiddleware(): void
    {
        $this->setDefinition('pinai4_process_correlation_id.id', new Definition());
        $this->setDefinition('pinai4_process_correlation_id.messenger.add_stamp_middleware', new Definition());
        $this->setDefinition('pinai4_process_correlation_id.messenger.log_middleware', new Definition());

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('middleware2'),
        ]);

        $this->registerService('bus', MessageBus::class)
            ->setArgument(0, $expectedArgument)
            ->addTag('messenger.bus');

        $this->compile();

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('middleware2'),
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('bus', 0, $expectedArgument);
    }

    public function testNotDefinedMainBundleService(): void
    {
        $this->setDefinition('pinai4_process_correlation_id.messenger.add_stamp_middleware', new Definition());
        $this->setDefinition('pinai4_process_correlation_id.messenger.log_middleware', new Definition());

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('messenger.middleware.dispatch_after_current_bus'),
            new Reference('middleware2'),
        ]);

        $this->registerService('bus', MessageBus::class)
            ->setArgument(0, $expectedArgument)
            ->addTag('messenger.bus');

        $this->compile();

        $expectedArgument = new IteratorArgument([
            new Reference('middleware1'),
            new Reference('messenger.middleware.dispatch_after_current_bus'),
            new Reference('middleware2'),
        ]);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('bus', 0, $expectedArgument);
    }
}
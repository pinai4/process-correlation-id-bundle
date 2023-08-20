<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Pinai4ProcessCorrelationIdExtension;
use Pinai4\ProcessCorrelationIdBundle\EventListener\InitProcessCorrelationIdSubscriber;
use Pinai4\ProcessCorrelationIdBundle\Messenger\AddProcessCorrelationIdStampMiddleware;
use Pinai4\ProcessCorrelationIdBundle\Messenger\LogProcessCorrelationIdMiddleware;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Symfony\Component\DependencyInjection\Reference;

class ExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [
            new Pinai4ProcessCorrelationIdExtension(),
        ];
    }

    public function testExtensionServices()
    {
        $this->load();

        $this->assertContainerBuilderHasService(
            'pinai4_process_correlation_id.id',
            ProcessCorrelationId::class
        );

        $this->assertContainerBuilderHasService(
            'pinai4_process_correlation_id.event_listener.init_subscriber',
            InitProcessCorrelationIdSubscriber::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.event_listener.init_subscriber',
            0,
            new Reference('pinai4_process_correlation_id.id')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.event_listener.init_subscriber',
            1,
            new Reference('pinai4_process_correlation_id.monolog.processor')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'pinai4_process_correlation_id.event_listener.init_subscriber',
            'kernel.event_subscriber'
        );

        $this->assertContainerBuilderHasService(
            'pinai4_process_correlation_id.messenger.add_stamp_middleware',
            AddProcessCorrelationIdStampMiddleware::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.messenger.add_stamp_middleware',
            0,
            new Reference('pinai4_process_correlation_id.id')
        );

        $this->assertContainerBuilderHasService(
            'pinai4_process_correlation_id.messenger.log_middleware',
            LogProcessCorrelationIdMiddleware::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.messenger.log_middleware',
            0,
            new Reference('pinai4_process_correlation_id.monolog.processor')
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.messenger.log_middleware',
            1,
            new Reference('logger')
        );

        $this->assertContainerBuilderHasService(
            'pinai4_process_correlation_id.monolog.processor',
            ProcessCorrelationIdProcessor::class
        );
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(
            'pinai4_process_correlation_id.monolog.processor',
            0,
            'process_correlation_id'
        );
        $this->assertContainerBuilderHasServiceDefinitionWithTag(
            'pinai4_process_correlation_id.monolog.processor',
            'monolog.processor'
        );
    }

}
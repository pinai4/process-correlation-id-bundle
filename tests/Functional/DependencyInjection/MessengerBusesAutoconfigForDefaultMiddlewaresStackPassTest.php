<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Functional\DependencyInjection;

use Pinai4\ProcessCorrelationIdBundle\Pinai4ProcessCorrelationIdBundle;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\CompilerPassDataCollector;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\CompilerPassDataCollectorPass;
use Pinai4\ProcessCorrelationIdBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;

class MessengerBusesAutoconfigForDefaultMiddlewaresStackPassTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, string> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->addTestBundle(Pinai4ProcessCorrelationIdBundle::class);
        $kernel->addTestCompilerPass(new CompilerPassDataCollectorPass());
        $kernel->handleOptions($options);

        return $kernel;
    }

    public function testDefaultBus()
    {
        $kernel = self::bootKernel();

        $container =  $kernel->getContainer();

        $this->assertSame($container->get(CompilerPassDataCollector::class)->getData(), [
            'buses' => [
                'messenger.bus.default' => [
                    0 => 'messenger.bus.default.middleware.add_bus_name_stamp_middleware',
                    1 => 'messenger.middleware.reject_redelivered_message_middleware',
                    2 => 'pinai4_process_correlation_id.messenger.add_stamp_middleware',
                    3 => 'messenger.middleware.dispatch_after_current_bus',
                    4 => 'pinai4_process_correlation_id.messenger.log_middleware',
                    5 => 'messenger.middleware.failed_message_processing_middleware',
                    6 => 'messenger.middleware.send_message',
                    7 => 'messenger.bus.default.middleware.handle_message',
                ],
            ],
        ]);
    }

    public function testMultipleBuses()
    {
        $options = [
            'config' => static function (TestKernel $kernel): void {
                $kernel->addTestConfig(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'messenger' => [
                            'default_bus' => 'command.bus',
                            'buses' => [
                                'command.bus' => [],
                                'query.bus' => [
                                    'middleware' => ['router_context']
                                ],
                                'event.bus' => [
                                    'default_middleware' => false,
                                ],
                            ],
                        ],
                    ]);
                });
            },
        ];
        $kernel = self::bootKernel($options);

        $container =  $kernel->getContainer();

        $this->assertSame($container->get(CompilerPassDataCollector::class)->getData(), [
            'buses' => [
                'command.bus' => [
                    0 => 'command.bus.middleware.add_bus_name_stamp_middleware',
                    1 => 'messenger.middleware.reject_redelivered_message_middleware',
                    2 => 'pinai4_process_correlation_id.messenger.add_stamp_middleware',
                    3 => 'messenger.middleware.dispatch_after_current_bus',
                    4 => 'pinai4_process_correlation_id.messenger.log_middleware',
                    5 => 'messenger.middleware.failed_message_processing_middleware',
                    6 => 'messenger.middleware.send_message',
                    7 => 'command.bus.middleware.handle_message',
                ],
                'query.bus' => [
                    0 => 'query.bus.middleware.add_bus_name_stamp_middleware',
                    1 => 'messenger.middleware.reject_redelivered_message_middleware',
                    2 => 'pinai4_process_correlation_id.messenger.add_stamp_middleware',
                    3 => 'messenger.middleware.dispatch_after_current_bus',
                    4 => 'pinai4_process_correlation_id.messenger.log_middleware',
                    5 => 'messenger.middleware.failed_message_processing_middleware',
                    6 => 'messenger.middleware.router_context',
                    7 => 'messenger.middleware.send_message',
                    8 => 'query.bus.middleware.handle_message',
                ],
                'event.bus' => [],
            ],
        ]);
    }


}
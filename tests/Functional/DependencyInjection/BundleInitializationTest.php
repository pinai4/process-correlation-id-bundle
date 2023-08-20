<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Functional\DependencyInjection;

use DeepCopy\Exception\PropertyException;
use DeepCopy\Reflection\ReflectionHelper;
use Pinai4\ProcessCorrelationIdBundle\EventListener\InitProcessCorrelationIdSubscriber;
use Pinai4\ProcessCorrelationIdBundle\Messenger\AddProcessCorrelationIdStampMiddleware;
use Pinai4\ProcessCorrelationIdBundle\Messenger\LogProcessCorrelationIdMiddleware;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Pinai4\ProcessCorrelationIdBundle\Pinai4ProcessCorrelationIdBundle;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Pinai4\ProcessCorrelationIdBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBus;

class BundleInitializationTest extends KernelTestCase
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
        $kernel->handleOptions($options);

        return $kernel;
    }

    /**
     * @throws \Exception
     */
    public function testInitEnabledBundle(): void
    {
        $options = [
            'config' => static function (TestKernel $kernel): void {
                $kernel->addTestConfig(static function (ContainerBuilder $container) {
                    $aliases = [
                        'test.pinai4_process_correlation_id.id' => new Alias(
                            'pinai4_process_correlation_id.id', true
                        ),
                        'test.pinai4_process_correlation_id.event_listener.init_subscriber' => new Alias(
                            'pinai4_process_correlation_id.event_listener.init_subscriber', true
                        ),
                        'test.pinai4_process_correlation_id.messenger.add_stamp_middleware' => new Alias(
                            'pinai4_process_correlation_id.messenger.add_stamp_middleware', true
                        ),
                        'test.pinai4_process_correlation_id.messenger.log_middleware' => new Alias(
                            'pinai4_process_correlation_id.messenger.log_middleware', true
                        ),
                        'test.pinai4_process_correlation_id.monolog.processor' => new Alias(
                            'pinai4_process_correlation_id.monolog.processor', true
                        ),
                        'test.messenger.bus.default' => new Alias(
                            'messenger.bus.default', true
                        ),
                    ];

                    $container->setAliases($aliases);
                });
            },
        ];

        $kernel = self::bootKernel($options);

        /** @var Container $container */
        $container = $kernel->getContainer();

        $services = [
            'test.pinai4_process_correlation_id.id' => ProcessCorrelationId::class,
            'test.pinai4_process_correlation_id.event_listener.init_subscriber' => InitProcessCorrelationIdSubscriber::class,
            'test.pinai4_process_correlation_id.messenger.add_stamp_middleware' => AddProcessCorrelationIdStampMiddleware::class,
            'test.pinai4_process_correlation_id.messenger.log_middleware' => LogProcessCorrelationIdMiddleware::class,
            'test.pinai4_process_correlation_id.monolog.processor' => ProcessCorrelationIdProcessor::class,
            'test.messenger.bus.default' => MessageBus::class
        ];

        foreach ($services as $id => $class) {
            $this->assertTrue($container->has($id), "Service '{$id}' does not exist");
            $s = $container->get($id);
            $this->assertInstanceOf($class, $s);
        }

        /** @var \IteratorAggregate $busMiddlewareAggregate */
        $busMiddlewareAggregate = $this->getObjectNotPublicPropertyValue(
            $container->get('test.messenger.bus.default'),
            'middlewareAggregate'
        );
        $this->assertInstanceOf('IteratorAggregate', $busMiddlewareAggregate);

        $middlewareClasses = array_map('get_class', iterator_to_array($busMiddlewareAggregate->getIterator()));
        $this->assertContains(AddProcessCorrelationIdStampMiddleware::class, $middlewareClasses);
        $this->assertContains(LogProcessCorrelationIdMiddleware::class, $middlewareClasses);
    }

    /**
     * @throws \ReflectionException
     * @throws PropertyException
     */
    public function testInitDisabledBundle(): void
    {
        $options = [
            'config' => static function (TestKernel $kernel): void {
                $kernel->addTestConfig(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('pinai4_process_correlation_id', [
                        'enabled' => false,
                    ]);

                    $aliases = [
                        'test.messenger.bus.default' => new Alias(
                            'messenger.bus.default', true
                        ),
                    ];

                    $container->setAliases($aliases);
                });
            },
        ];
        $kernel = self::bootKernel($options);

        /** @var Container $container */
        $container = $kernel->getContainer();

        $this->assertFalse($container->has('pinai4_process_correlation_id.id'));
        $this->assertArrayNotHasKey('pinai4_process_correlation_id.id', $container->getRemovedIds());

        /** @var \IteratorAggregate $busMiddlewareAggregate */
        $busMiddlewareAggregate = $this->getObjectNotPublicPropertyValue(
            $container->get('test.messenger.bus.default'),
            'middlewareAggregate'
        );
        $this->assertInstanceOf('IteratorAggregate', $busMiddlewareAggregate);

        $middlewareClasses = array_map('get_class', iterator_to_array($busMiddlewareAggregate->getIterator()));
        $this->assertNotContains(AddProcessCorrelationIdStampMiddleware::class, $middlewareClasses);
        $this->assertNotContains(LogProcessCorrelationIdMiddleware::class, $middlewareClasses);
    }

    /**
     * @throws \ReflectionException
     * @throws PropertyException
     */
    private function getObjectNotPublicPropertyValue($obj, $propertyName): mixed
    {
        $property = ReflectionHelper::getProperty($obj, $propertyName);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @throws \ReflectionException
     * @throws PropertyException
     */
    public function testInitBundleWithMultipleMessengerBuses(): void
    {
        $options = [
            'config' => static function (TestKernel $kernel): void {
                $kernel->addTestConfig(static function (ContainerBuilder $container) {
                    $container->loadFromExtension('framework', [
                        'messenger' => [
                            'default_bus' => 'command.bus',
                            'buses' => [
                                'command.bus' => [],
                                'query.bus' => [],
                                'event.bus' => [
                                    'default_middleware' => false,
                                ],
                            ],
                        ],
                    ]);

                    $aliases = [
                        'test.command.bus' => new Alias(
                            'command.bus', true
                        ),
                        'test.query.bus' => new Alias(
                            'query.bus', true
                        ),
                        'test.event.bus' => new Alias(
                            'event.bus', true
                        ),
                    ];

                    $container->setAliases($aliases);
                });
            },
        ];
        $kernel = self::bootKernel($options);

        /** @var Container $container */
        $container = $kernel->getContainer();

        // Command bus middlewares check
        /** @var \IteratorAggregate $busMiddlewareAggregate */
        $busMiddlewareAggregate = $this->getObjectNotPublicPropertyValue(
            $container->get('test.command.bus'),
            'middlewareAggregate'
        );
        $this->assertInstanceOf('IteratorAggregate', $busMiddlewareAggregate);

        $middlewareClasses = array_map('get_class', iterator_to_array($busMiddlewareAggregate->getIterator()));
        $this->assertContains(AddProcessCorrelationIdStampMiddleware::class, $middlewareClasses);
        $this->assertContains(LogProcessCorrelationIdMiddleware::class, $middlewareClasses);

        // Query bus middlewares check
        /** @var \IteratorAggregate $busMiddlewareAggregate */
        $busMiddlewareAggregate = $this->getObjectNotPublicPropertyValue(
            $container->get('test.query.bus'),
            'middlewareAggregate'
        );
        $this->assertInstanceOf('IteratorAggregate', $busMiddlewareAggregate);

        $middlewareClasses = array_map('get_class', iterator_to_array($busMiddlewareAggregate->getIterator()));
        $this->assertContains(AddProcessCorrelationIdStampMiddleware::class, $middlewareClasses);
        $this->assertContains(LogProcessCorrelationIdMiddleware::class, $middlewareClasses);

        // Event bus middlewares check
        /** @var \IteratorAggregate $busMiddlewareAggregate */
        $busMiddlewareAggregate = $this->getObjectNotPublicPropertyValue(
            $container->get('test.event.bus'),
            'middlewareAggregate'
        );
        $this->assertInstanceOf('IteratorAggregate', $busMiddlewareAggregate);

        $middlewareClasses = array_map('get_class', iterator_to_array($busMiddlewareAggregate->getIterator()));
        $this->assertNotContains(AddProcessCorrelationIdStampMiddleware::class, $middlewareClasses);
        $this->assertNotContains(LogProcessCorrelationIdMiddleware::class, $middlewareClasses);
    }
}


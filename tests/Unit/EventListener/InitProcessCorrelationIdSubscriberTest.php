<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\EventListener;

use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\EventListener\InitProcessCorrelationIdSubscriber;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Command\ConsumeMessagesCommand;

class InitProcessCorrelationIdSubscriberTest extends TestCase
{
    public function testOnKernelRequest(): void
    {
        $processCorrelationId = new ProcessCorrelationId();

        $fieldName = 'some_field_name';
        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $subscriber = new InitProcessCorrelationIdSubscriber($processCorrelationId, $processor);

        $eventEventDispatcher = new EventDispatcher();
        $eventEventDispatcher->addSubscriber($subscriber);

        $requestEvent = $this->createStub(RequestEvent::class);
        $requestEvent->method('isMainRequest')
            ->willReturn(true);

        $eventEventDispatcher->dispatch($requestEvent, KernelEvents::REQUEST);

        $this->assertTrue($processCorrelationId->isGenerated());
        $this->assertStringStartsWith('R-', $processCorrelationId->get());

        $this->assertSame([
            'extra' => [$fieldName => $processCorrelationId->get()],
        ], $processor([]));
    }

    public function testOnKernelRequestNotMainRequest(): void
    {
        $processCorrelationId = new ProcessCorrelationId();

        $fieldName = 'some_field_name';
        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $subscriber = new InitProcessCorrelationIdSubscriber($processCorrelationId, $processor);

        $eventEventDispatcher = new EventDispatcher();
        $eventEventDispatcher->addSubscriber($subscriber);

        $requestEvent = $this->createStub(RequestEvent::class);
        $requestEvent->method('isMainRequest')
            ->willReturn(false);

        $eventEventDispatcher->dispatch($requestEvent, KernelEvents::REQUEST);

        $this->assertFalse($processCorrelationId->isGenerated());

        $this->assertSame([], $processor([]));
    }

    public function testOnConsoleCommand(): void
    {
        $processCorrelationId = new ProcessCorrelationId();

        $fieldName = 'some_field_name';
        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $subscriber = new InitProcessCorrelationIdSubscriber($processCorrelationId, $processor);

        $eventEventDispatcher = new EventDispatcher();
        $eventEventDispatcher->addSubscriber($subscriber);

        $commandStub = $this->createStub(Command::class);
        $consoleCommandEvent = new ConsoleCommandEvent(
            $commandStub,
            $this->createStub(InputInterface::class),
            $this->createStub(OutputInterface::class)
        );

        $eventEventDispatcher->dispatch($consoleCommandEvent, ConsoleEvents::COMMAND);

        $this->assertTrue($processCorrelationId->isGenerated());
        $this->assertStringStartsWith('C-', $processCorrelationId->get());

        $this->assertSame([
            'extra' => [$fieldName => $processCorrelationId->get()],
        ], $processor([]));
    }

    public function testOnConsoleCommandWithConsumeMessagesCommand(): void
    {
        $processCorrelationId = new ProcessCorrelationId();

        $fieldName = 'some_field_name';
        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $subscriber = new InitProcessCorrelationIdSubscriber($processCorrelationId, $processor);

        $eventEventDispatcher = new EventDispatcher();
        $eventEventDispatcher->addSubscriber($subscriber);

        $commandStub = $this->createStub(Command::class);
        $commandStub->method('getName')
            ->willReturn(ConsumeMessagesCommand::getDefaultName());
        
        $consoleCommandEvent = new ConsoleCommandEvent(
            $commandStub,
            $this->createStub(InputInterface::class),
            $this->createStub(OutputInterface::class)
        );

        $eventEventDispatcher->dispatch($consoleCommandEvent, ConsoleEvents::COMMAND);

        $this->assertFalse($processCorrelationId->isGenerated());

        $this->assertSame([], $processor([]));
    }
}
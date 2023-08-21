<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\Messenger;

use Pinai4\ProcessCorrelationIdBundle\Messenger\LogProcessCorrelationIdMiddleware;
use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\Messenger\ProcessCorrelationIdStamp;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\Messenger\DummyMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

class LogProcessCorrelationIdMiddlewareTest extends TestCase
{
    public function testWithProcessCorrelationIdStamp(): void
    {
        $testMessage = new DummyMessage('Some Message');

        $loggerProcessorMock = $this->createMock(ProcessCorrelationIdProcessor::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessorMock, $loggerMock),
        ]);

        $testProcessCorrelationId = 'pr_cor_id';

        $loggerProcessorMock
            ->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($testProcessCorrelationId));

        $loggerProcessorMock
            ->expects($this->never())
            ->method('resetToInitialProcessCorrelationId');

        $loggerMock
            ->expects($this->never())
            ->method('error');

        $messageBus->dispatch(
            new Envelope($testMessage, [new ProcessCorrelationIdStamp($testProcessCorrelationId)])
        );
    }

    public function testWithoutProcessCorrelationIdStamp(): void
    {
        $testMessage = new DummyMessage('Some Message');

        $loggerProcessorMock = $this->createMock(ProcessCorrelationIdProcessor::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessorMock, $loggerMock),
        ]);

        $loggerProcessorMock
            ->expects($this->never())
            ->method('activateProcessCorrelationId');

        $loggerMock
            ->expects($this->never())
            ->method('error');

        $messageBus->dispatch($testMessage);
    }

    public function testWithProcessCorrelationIdAndConsumedByWorkerStampStamp(): void
    {
        $testMessage = new DummyMessage('Some Message');

        $loggerProcessorMock = $this->createMock(ProcessCorrelationIdProcessor::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $setConsumedByWorkerStampMiddlewareMock = $this->createMock(MiddlewareInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessorMock, $loggerMock),
            $setConsumedByWorkerStampMiddlewareMock,
        ]);

        $setConsumedByWorkerStampMiddlewareMock
            ->expects($this->exactly(1))
            ->method('handle')
            ->willReturnCallback(static function (Envelope $envelope, StackInterface $stack): Envelope {
                if ($envelope->last(ConsumedByWorkerStamp::class) === null) {
                    $envelope = $envelope->with(new ConsumedByWorkerStamp());
                }

                return $stack->next()->handle($envelope, $stack);
            });

        $testProcessCorrelationId = 'pr_cor_id';

        $loggerProcessorMock
            ->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($testProcessCorrelationId));

        $loggerProcessorMock
            ->expects($this->exactly(1))
            ->method('resetToInitialProcessCorrelationId');

        $loggerMock
            ->expects($this->never())
            ->method('error');

        $messageBus->dispatch(
            new Envelope($testMessage, [new ProcessCorrelationIdStamp($testProcessCorrelationId)])
        );
    }

    public function testThrowException(): void
    {
        $testMessage = new DummyMessage('Some Message');

        $testProcessCorrelationId = 'pr_cor_id';

        $loggerProcessorMock = $this->createMock(ProcessCorrelationIdProcessor::class);
        $loggerProcessorMock
            ->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($testProcessCorrelationId));
        $loggerProcessorMock
            ->expects($this->exactly(1))
            ->method('resetToInitialProcessCorrelationId');

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->exactly(1))
            ->method('error');

        $throwExceptionMiddlewareMock = $this->createMock(MiddlewareInterface::class);
        $throwExceptionMiddlewareMock
            ->expects($this->exactly(1))
            ->method('handle')
            ->willThrowException(new \Exception('Test Exception'));

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessorMock, $loggerMock),
            $throwExceptionMiddlewareMock,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test Exception');

        $messageBus->dispatch(
            new Envelope($testMessage, [new ProcessCorrelationIdStamp($testProcessCorrelationId)])
        );
    }
}

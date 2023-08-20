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
        $message = new DummyMessage('Some Message');

        $loggerProcessor = $this->createMock(ProcessCorrelationIdProcessor::class);
        $logger = $this->createMock(LoggerInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessor, $logger),
        ]);

        $processCorrelationId = 'pr_cor_id';

        $loggerProcessor->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($processCorrelationId));

        $loggerProcessor->expects($this->never())
            ->method('resetToInitialProcessCorrelationId');

        $logger->expects($this->never())
            ->method('error');

        $messageBus->dispatch(
            new Envelope($message, [new ProcessCorrelationIdStamp($processCorrelationId)])
        );
    }

    public function testWithoutProcessCorrelationIdStamp(): void
    {
        $message = new DummyMessage('Some Message');

        $loggerProcessor = $this->createMock(ProcessCorrelationIdProcessor::class);
        $logger = $this->createMock(LoggerInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessor, $logger),
        ]);

        $loggerProcessor->expects($this->never())
            ->method('activateProcessCorrelationId');

        $logger->expects($this->never())
            ->method('error');

        $messageBus->dispatch($message);
    }

    public function testWithProcessCorrelationIdAndConsumedByWorkerStampStamp(): void
    {
        $message = new DummyMessage('Some Message');

        $loggerProcessor = $this->createMock(ProcessCorrelationIdProcessor::class);
        $logger = $this->createMock(LoggerInterface::class);

        $setConsumedByWorkerStampMiddleware = $this->createMock(MiddlewareInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessor, $logger),
            $setConsumedByWorkerStampMiddleware,
        ]);

        $setConsumedByWorkerStampMiddleware->expects($this->exactly(1))
        ->method('handle')
        ->willReturnCallback(static function (Envelope $envelope, StackInterface $stack): Envelope {
            if ($envelope->last(ConsumedByWorkerStamp::class) === null) {
                $envelope = $envelope->with(new ConsumedByWorkerStamp());
            }
            return $stack->next()->handle($envelope, $stack);
        });

        $processCorrelationId = 'pr_cor_id';

        $loggerProcessor->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($processCorrelationId));

        $loggerProcessor->expects($this->exactly(1))
            ->method('resetToInitialProcessCorrelationId');

        $logger->expects($this->never())
            ->method('error');

        $messageBus->dispatch(
            new Envelope($message, [new ProcessCorrelationIdStamp($processCorrelationId)])
        );
    }

    public function testThrowException(): void
    {
        $message = new DummyMessage('Some Message');

        $loggerProcessor = $this->createMock(ProcessCorrelationIdProcessor::class);
        $logger = $this->createMock(LoggerInterface::class);

        $throwExceptionMiddleware = $this->createMock(MiddlewareInterface::class);

        $messageBus = new MessageBus([
            new LogProcessCorrelationIdMiddleware($loggerProcessor, $logger),
            $throwExceptionMiddleware,
        ]);

        $throwExceptionMiddleware->expects($this->exactly(1))
            ->method('handle')
            ->willThrowException(new \Exception('Test Exception'));

        $processCorrelationId = 'pr_cor_id';

        $loggerProcessor->expects($this->exactly(1))
            ->method('activateProcessCorrelationId')
            ->with($this->identicalTo($processCorrelationId));

        $loggerProcessor->expects($this->exactly(1))
            ->method('resetToInitialProcessCorrelationId');

        $logger->expects($this->exactly(1))
            ->method('error');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test Exception');

        $messageBus->dispatch(
            new Envelope($message, [new ProcessCorrelationIdStamp($processCorrelationId)])
        );
    }
}

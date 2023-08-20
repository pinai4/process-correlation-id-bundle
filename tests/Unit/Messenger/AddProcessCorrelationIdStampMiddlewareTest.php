<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\Messenger;

use PHPUnit\Framework\MockObject\Stub\ReturnCallback;
use Pinai4\ProcessCorrelationIdBundle\Messenger\AddProcessCorrelationIdStampMiddleware;
use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\Messenger\ProcessCorrelationIdStamp;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\Messenger\DispatchingMiddleware;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\Messenger\DummyEvent;
use Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\Messenger\DummyMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\DelayedMessageHandlingException;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\DispatchAfterCurrentBusMiddleware;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\DispatchAfterCurrentBusStamp;

class AddProcessCorrelationIdStampMiddlewareTest extends TestCase
{
    public function testOnlyRootDispatchWithGeneratedId(): void
    {
        $message = new DummyMessage('Some Message');

        $processCorrelationId = new ProcessCorrelationId();
        $processCorrelationId->generate('main_test_');

        $messageBus = new MessageBus([
            new AddProcessCorrelationIdStampMiddleware($processCorrelationId),
        ]);

        $envelop = $messageBus->dispatch(new Envelope($message));
        $envelopWithStamp = $messageBus->dispatch(
            new Envelope($message, [new ProcessCorrelationIdStamp('message_test_hash')])
        );

        /** @var ?ProcessCorrelationIdStamp $stamp */
        $stamp = $envelop->last(ProcessCorrelationIdStamp::class);

        $this->assertNotNull($stamp);
        $this->assertStringStartsWith('main_test_', $stamp->getProcessCorrelationId());

        /** @var ?ProcessCorrelationIdStamp $stamp */
        $stamp = $envelopWithStamp->last(ProcessCorrelationIdStamp::class);

        $this->assertNotNull($stamp);
        $this->assertSame('message_test_hash', $stamp->getProcessCorrelationId());
    }

    public function testOnlyRootDispatchWithNotGeneratedId(): void
    {
        $message = new DummyMessage('Some Message');

        $messageBus = new MessageBus([
            new AddProcessCorrelationIdStampMiddleware(new ProcessCorrelationId()),
        ]);

        $envelop = $messageBus->dispatch(new Envelope($message));
        $envelopWithStamp = $messageBus->dispatch(
            new Envelope($message, [new ProcessCorrelationIdStamp('message_test_hash')])
        );

        /** @var ?ProcessCorrelationIdStamp $stamp */
        $stamp = $envelop->last(ProcessCorrelationIdStamp::class);
        $this->assertNotNull($stamp);
        $this->assertSame('not specified', $stamp->getProcessCorrelationId());

        /** @var ?ProcessCorrelationIdStamp $stamp */
        $stamp = $envelopWithStamp->last(ProcessCorrelationIdStamp::class);
        $this->assertNotNull($stamp);
        $this->assertSame('message_test_hash', $stamp->getProcessCorrelationId());
    }

    public function testWithNestedDispatches(): void
    {
        $message = new DummyMessage('Some Message');
        $firstEvent = new DummyEvent('First event');
        $secondEvent = new DummyEvent('Second event');
        $thirdEvent = new DummyEvent('Third event');

        $processCorrelationId = new ProcessCorrelationId();

        $dispatchAfterCurrentBus = new DispatchAfterCurrentBusMiddleware();
        $addProcessCorrelationIdStampMiddleware = new AddProcessCorrelationIdStampMiddleware($processCorrelationId);

        $handlingMiddleware = $this->createMock(MiddlewareInterface::class);

        $eventBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBus,
            $handlingMiddleware,
        ]);

        $messageBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBus,
            new DispatchingMiddleware($eventBus, [
                $firstEvent,
                $secondEvent,
                $thirdEvent,
            ]),
            $handlingMiddleware,
        ]);

        $series = [];
        $handlingMiddleware->expects($this->exactly(12))
            ->method('handle')
            ->with(
                $this->callback(function (Envelope $envelope) use (&$series) {
                    $expectedVals = array_shift($series);

                    /** @var ?ProcessCorrelationIdStamp $stamp */
                    $stamp = $envelope->last(ProcessCorrelationIdStamp::class);

                    return $envelope->getMessage() === $expectedVals[0]
                        && $stamp?->getProcessCorrelationId() === $expectedVals[1];
                })
            )
            ->willReturnOnConsecutiveCalls(
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage()
            );


        $id = 'not specified';
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
            [$thirdEvent, $id],
            [$message, $id],
        ];
        $messageBus->dispatch($message);

        $processCorrelationId->generate('main_test_');
        $id = $processCorrelationId->get();
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
            [$thirdEvent, $id],
            [$message, $id],
        ];
        $messageBus->dispatch($message);

        $id = 'custom_id';
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
            [$thirdEvent, $id],
            [$message, $id],
        ];
        $messageBus->dispatch(new Envelope($message, [new ProcessCorrelationIdStamp($id)]));
    }

    public function testWithNestedDispatchesWithDispatchAfterCurrentBusStamp(): void
    {
        $message = new DummyMessage('Some Message');
        $firstEvent = new DummyEvent('First event');
        $secondEvent = new DummyEvent('Second event');
        $thirdEvent = new DummyEvent('Third event');

        $processCorrelationId = new ProcessCorrelationId();

        $dispatchAfterCurrentBus = new DispatchAfterCurrentBusMiddleware();
        $addProcessCorrelationIdStampMiddleware = new AddProcessCorrelationIdStampMiddleware($processCorrelationId);

        $handlingMiddleware = $this->createMock(MiddlewareInterface::class);

        $eventBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBus,
            $handlingMiddleware,
        ]);

        $messageBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBus,
            new DispatchingMiddleware($eventBus, [
                new Envelope($firstEvent, [new DispatchAfterCurrentBusStamp()]),
                new Envelope($secondEvent, [new DispatchAfterCurrentBusStamp()]),
                $thirdEvent, // Not in a new transaction
            ]),
            $handlingMiddleware,
        ]);

        $series = [];
        $handlingMiddleware->expects($this->exactly(12))
            ->method('handle')
            ->with(
                $this->callback(function (Envelope $envelope) use (&$series) {
                    $expectedVals = array_shift($series);

                    /** @var ?ProcessCorrelationIdStamp $stamp */
                    $stamp = $envelope->last(ProcessCorrelationIdStamp::class);

                    return $envelope->getMessage() === $expectedVals[0]
                        && $stamp?->getProcessCorrelationId() === $expectedVals[1];
                })
            )
            ->willReturnOnConsecutiveCalls(
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage()
            );


        $id = 'not specified';
        $series = [
            // Third event is dispatch within main dispatch, but before its handling:
            [$thirdEvent, $id],
            // Then expect main dispatched message to be handled first:
            [$message, $id],
            // Then, expect events in new transaction to be handled next, in dispatched order:
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];
        $messageBus->dispatch($message);

        $processCorrelationId->generate('main_test_');
        $id = $processCorrelationId->get();
        $series = [
            [$thirdEvent, $id],
            [$message, $id],
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];
        $messageBus->dispatch($message);

        $id = 'custom_id';
        $series = [
            [$thirdEvent, $id],
            [$message, $id],
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];
        $messageBus->dispatch(new Envelope($message, [new ProcessCorrelationIdStamp($id)]));
    }

    public function testWithNestedDispatchesThrowException(): void
    {
        $message = new DummyMessage('Some Message');
        $firstEvent = new DummyEvent('First event');
        $secondEvent = new DummyEvent('Second event');
        $thirdEvent = new DummyEvent('Third event');

        $processCorrelationId = new ProcessCorrelationId();

        $dispatchAfterCurrentBusMiddleware = new DispatchAfterCurrentBusMiddleware();
        $addProcessCorrelationIdStampMiddleware = new AddProcessCorrelationIdStampMiddleware($processCorrelationId);

        $handlingMiddleware = $this->createMock(MiddlewareInterface::class);

        $eventBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBusMiddleware,
            $handlingMiddleware,
        ]);

        $messageBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBusMiddleware,
            new DispatchingMiddleware($eventBus, [
                $firstEvent,
                $secondEvent,
                $thirdEvent,
            ]),
            $handlingMiddleware,
        ]);

        $series = [];
        $handlingMiddleware->expects($this->exactly(9))
            ->method('handle')
            ->with(
                $this->callback(function (Envelope $envelope) use (&$series) {
                    $expectedVals = array_shift($series);

                    /** @var ?ProcessCorrelationIdStamp $stamp */
                    $stamp = $envelope->last(ProcessCorrelationIdStamp::class);

                    return $envelope->getMessage() === $expectedVals[0]
                        && $stamp?->getProcessCorrelationId() === $expectedVals[1];
                })
            )
            ->willReturnOnConsecutiveCalls(
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
            );

        $id = 'not specified';
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];

        try {
            $messageBus->dispatch($message);
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }

        $processCorrelationId->generate('main_test_');
        $id = $processCorrelationId->get();
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
            [$thirdEvent, $id],
        ];

        try {
            $messageBus->dispatch($message);
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }

        $id = 'custom_id';
        $series = [
            [$firstEvent, $id],
            [$secondEvent, $id],
            [$thirdEvent, $id],
            [$message, $id],
        ];

        try {
            $messageBus->dispatch(new Envelope($message, [new ProcessCorrelationIdStamp($id)]));
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }
    }

    public function testWithNestedDispatchesWithDispatchAfterCurrentBusStampThrowException(): void
    {
        $message = new DummyMessage('Some Message');
        $firstEvent = new DummyEvent('First event');
        $secondEvent = new DummyEvent('Second event');
        $thirdEvent = new DummyEvent('Third event');

        $processCorrelationId = new ProcessCorrelationId();

        $dispatchAfterCurrentBusMiddleware = new DispatchAfterCurrentBusMiddleware();
        $addProcessCorrelationIdStampMiddleware = new AddProcessCorrelationIdStampMiddleware($processCorrelationId);

        $handlingMiddleware = $this->createMock(MiddlewareInterface::class);

        $eventBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBusMiddleware,
            $handlingMiddleware,
        ]);

        $messageBus = new MessageBus([
            $addProcessCorrelationIdStampMiddleware,
            $dispatchAfterCurrentBusMiddleware,
            new DispatchingMiddleware($eventBus, [
                new Envelope($firstEvent, [new DispatchAfterCurrentBusStamp()]),
                new Envelope($secondEvent, [new DispatchAfterCurrentBusStamp()]),
                $thirdEvent,
            ]),
            $handlingMiddleware,
        ]);

        $series = [];
        $handlingMiddleware->expects($this->exactly(10))
            ->method('handle')
            ->with(
                $this->callback(function (Envelope $envelope) use (&$series) {
                    $expectedVals = array_shift($series);

                    /** @var ?ProcessCorrelationIdStamp $stamp */
                    $stamp = $envelope->last(ProcessCorrelationIdStamp::class);

                    return $envelope->getMessage() === $expectedVals[0]
                        && $stamp?->getProcessCorrelationId() === $expectedVals[1];
                })
            )
            ->willReturnOnConsecutiveCalls(
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->willHandleMessage(),
                $this->throwException(new \Exception('Test Exception')),
                $this->throwException(new \Exception('Test Exception')),
            );

        $id = 'not specified';
        $series = [
            [$thirdEvent, $id],
            [$message, $id],
        ];

        try {
            $messageBus->dispatch($message);
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }

        $processCorrelationId->generate('main_test_');
        $id = $processCorrelationId->get();
        $series = [
            [$thirdEvent, $id],
            [$message, $id],
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];

        try {
            $messageBus->dispatch($message);
        } catch (DelayedMessageHandlingException $e) {
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }

        $id = 'custom_id';
        $series = [
            [$thirdEvent, $id],
            [$message, $id],
            [$firstEvent, $id],
            [$secondEvent, $id],
        ];

        try {
            $messageBus->dispatch(new Envelope($message, [new ProcessCorrelationIdStamp($id)]));
        } catch (DelayedMessageHandlingException $e) {
        } catch (\Exception $e) {
            if ($e->getMessage() !== 'Test Exception') {
                throw $e;
            }
        }
    }

    private function willHandleMessage(): ReturnCallback
    {
        return $this->returnCallback(fn($envelope, StackInterface $stack) => $stack->next()->handle($envelope, $stack));
    }

}
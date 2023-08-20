<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Fixtures\Messenger;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class DispatchingMiddleware implements MiddlewareInterface
{
    public function __construct(private MessageBusInterface $bus, private array $messages)
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        foreach ($this->messages as $event) {
            $this->bus->dispatch($event);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
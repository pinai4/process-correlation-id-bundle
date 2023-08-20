<?php

namespace Pinai4\ProcessCorrelationIdBundle\Messenger;

use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

class AddProcessCorrelationIdStampMiddleware implements MiddlewareInterface
{
    private ?string $rootDispatchProcessCorrelationId = null;

    public function __construct(private ProcessCorrelationId $processCorrelationId)
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        if ($this->rootDispatchProcessCorrelationId !== null) {
            /*
             * A call to MessageBusInterface::dispatch() was made from inside the main bus handling,
             * but the message does not have the stamp. So, process it like normal.
             */
            if ($envelope->last(ProcessCorrelationIdStamp::class) === null) {
                $envelope = $envelope->with(new ProcessCorrelationIdStamp($this->rootDispatchProcessCorrelationId));
            }

            try {
                $returnedEnvelope = $stack->next()->handle($envelope, $stack);
                if ($returnedEnvelope->last(ConsumedByWorkerStamp::class) !== null) {
                    $this->rootDispatchProcessCorrelationId = null;
                }
            } catch (\Throwable $e) {
                $this->rootDispatchProcessCorrelationId = null;
                throw $e;
            }

            return $returnedEnvelope;
        }

        /** @var ?ProcessCorrelationIdStamp $targetStamp */
        $targetStamp = $envelope->last(ProcessCorrelationIdStamp::class);
        if ($targetStamp === null) {
            $processCorrelationId = 'not specified';
            if ($this->processCorrelationId->isGenerated()) {
                $processCorrelationId = $this->processCorrelationId->get();
            }
            $envelope = $envelope->with(new ProcessCorrelationIdStamp($processCorrelationId));
        } else {
            $processCorrelationId = $targetStamp->getProcessCorrelationId();
        }

        // First time we get here, mark as inside a "root dispatch" call:
        $this->rootDispatchProcessCorrelationId = $processCorrelationId;


        try {
            $returnedEnvelope = $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $e) {
            $this->rootDispatchProcessCorrelationId = null;
            throw $e;
        }

        $this->rootDispatchProcessCorrelationId = null;

        return $returnedEnvelope;
    }
}

<?php

namespace Pinai4\ProcessCorrelationIdBundle\Messenger;

use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ConsumedByWorkerStamp;

class LogProcessCorrelationIdMiddleware implements MiddlewareInterface
{
    public function __construct(private ProcessCorrelationIdProcessor $loggerProcessor, private LoggerInterface $logger)
    {
    }

    /**
     * @throws \Throwable
     */
    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        /** @var ?ProcessCorrelationIdStamp $stamp */
        $stamp = $envelope->last(ProcessCorrelationIdStamp::class);

        if ($stamp !== null) {
            $this->loggerProcessor->activateProcessCorrelationId($stamp->getProcessCorrelationId());
        }

        try {
            $returnedEnvelope = $stack->next()->handle($envelope, $stack);
            if ($returnedEnvelope->last(ConsumedByWorkerStamp::class) !== null) {
                $this->loggerProcessor->resetToInitialProcessCorrelationId();
            }
            return $returnedEnvelope;
        } catch (\Throwable $e) {
            $this->logger->error(
                'Error thrown while handling message {class}. Error: "{error}"',
                ['class' => $envelope->getMessage()::class, 'error' => $e->getMessage(), 'exception' => $e]
            );
            $this->loggerProcessor->resetToInitialProcessCorrelationId();
            throw $e;
        }
    }
}

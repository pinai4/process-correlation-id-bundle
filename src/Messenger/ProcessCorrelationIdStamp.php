<?php

namespace Pinai4\ProcessCorrelationIdBundle\Messenger;

use Symfony\Component\Messenger\Stamp\StampInterface;

class ProcessCorrelationIdStamp implements StampInterface
{
    public function __construct(private string $processCorrelationId)
    {
    }

    public function getProcessCorrelationId(): string
    {
        return $this->processCorrelationId;
    }
}

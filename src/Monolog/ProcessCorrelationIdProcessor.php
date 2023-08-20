<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Monolog;

use Monolog\Processor\ProcessorInterface;

class ProcessCorrelationIdProcessor implements ProcessorInterface
{
    private ?string $initialProcessCorrelationId = null;

    private ?string $processCorrelationId = null;

    public function __construct(private string $fieldName)
    {
    }

    public function setInitialProcessCorrelationId(string $processCorrelationId): void
    {
        $this->initialProcessCorrelationId = $processCorrelationId;
        $this->processCorrelationId = $processCorrelationId;
    }

    public function activateProcessCorrelationId(string $processCorrelationId): void
    {
        $this->processCorrelationId = $processCorrelationId;
    }

    public function resetToInitialProcessCorrelationId(): void
    {
        $this->processCorrelationId = $this->initialProcessCorrelationId;
    }

    public function __invoke(array $record): array
    {
        if ($this->processCorrelationId !== null) {
            $record['extra'][$this->fieldName] = $this->processCorrelationId;
            // $record['context'][$this->fieldName] = $this->processCorrelationId;
        }

        return $record;
    }
}

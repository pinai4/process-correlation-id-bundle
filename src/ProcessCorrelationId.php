<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle;

class ProcessCorrelationId
{
    private ?string $value = null;

    public function generate(string $prefix): void
    {
        $this->value = uniqid($prefix);
    }

    public function isGenerated(): bool
    {
        return $this->value !== null;
    }

    public function get(): string
    {
        if (!$this->isGenerated()) {
            throw new \LogicException('Process Correlation Id was not generated yet');
        }

        return $this->value;
    }
}

<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\ProcessCorrelationId;

class ProcessCorrelationIdTest extends TestCase
{
    public function testGenerate(): void
    {
        $processCorrelationId = new ProcessCorrelationId();

        $this->assertFalse($processCorrelationId->isGenerated());

        $prefix = 'Test_';
        $processCorrelationId->generate($prefix);
        $this->assertTrue($processCorrelationId->isGenerated());
        $this->assertStringStartsWith($prefix, $processCorrelationId->get());
    }

    public function testGetException(): void
    {
        $processCorrelationId = new ProcessCorrelationId();
        $this->assertFalse($processCorrelationId->isGenerated());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Process Correlation Id was not generated yet');

        $processCorrelationId->get();
    }
}

<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\Messenger;

use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;

class ProcessCorrelationIdProcessorTest extends TestCase
{
    public function testRegularCases(): void
    {
        $testFieldName = 'some_field_name';

        $testInitProcessCorrelationId = 'init_proc_cor_id';
        $testActiveProcessCorrelationId1 = 'active_proc_cor_id_1';
        $testActiveProcessCorrelationId2 = 'active_proc_cor_id_2';

        $processor = new ProcessCorrelationIdProcessor($testFieldName);

        $record = $processor(['key' => 'val']);
        $this->assertSame(['key' => 'val'], $record);

        $processor->setInitialProcessCorrelationId($testInitProcessCorrelationId);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$testFieldName => $testInitProcessCorrelationId]
            ],
            $record
        );

        $processor->activateProcessCorrelationId($testActiveProcessCorrelationId1);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$testFieldName => $testActiveProcessCorrelationId1]
            ],
            $record
        );

        $processor->resetToInitialProcessCorrelationId();
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$testFieldName => $testInitProcessCorrelationId]
            ],
            $record
        );

        $processor->activateProcessCorrelationId($testActiveProcessCorrelationId2);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$testFieldName => $testActiveProcessCorrelationId2]
            ],
            $record
        );
    }

    public function testWithoutInitialCall(): void
    {
        $testFieldName = 'some_field_name';

        $testActiveProcessCorrelationId1 = 'active_proc_cor_id_1';

        $processor = new ProcessCorrelationIdProcessor($testFieldName);

        $processor->activateProcessCorrelationId($testActiveProcessCorrelationId1);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$testFieldName => $testActiveProcessCorrelationId1]
            ],
            $record
        );

        $processor->resetToInitialProcessCorrelationId();
        $record = $processor(['key' => 'val']);
        $this->assertSame(['key' => 'val'], $record);
    }
}
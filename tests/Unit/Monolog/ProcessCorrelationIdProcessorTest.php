<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\Messenger;

use PHPUnit\Framework\TestCase;
use Pinai4\ProcessCorrelationIdBundle\Monolog\ProcessCorrelationIdProcessor;

class ProcessCorrelationIdProcessorTest extends TestCase
{
    public function testRegularCases(): void
    {
        $fieldName = 'some_field_name';

        $initProcessCorrelationId = 'init_proc_cor_id';
        $activeProcessCorrelationId1 = 'active_proc_cor_id_1';
        $activeProcessCorrelationId2 = 'active_proc_cor_id_2';

        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $record = $processor(['key' => 'val']);
        $this->assertSame(['key' => 'val'], $record);

        $processor->setInitialProcessCorrelationId($initProcessCorrelationId);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$fieldName => $initProcessCorrelationId]
            ],
            $record
        );

        $processor->activateProcessCorrelationId($activeProcessCorrelationId1);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$fieldName => $activeProcessCorrelationId1]
            ],
            $record
        );

        $processor->resetToInitialProcessCorrelationId();
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$fieldName => $initProcessCorrelationId]
            ],
            $record
        );

        $processor->activateProcessCorrelationId($activeProcessCorrelationId2);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$fieldName => $activeProcessCorrelationId2]
            ],
            $record
        );
    }

    public function testWithoutInitialCall(): void
    {
        $fieldName = 'some_field_name';

        $activeProcessCorrelationId1 = 'active_proc_cor_id_1';

        $processor = new ProcessCorrelationIdProcessor($fieldName);

        $processor->activateProcessCorrelationId($activeProcessCorrelationId1);
        $record = $processor(['key' => 'val']);
        $this->assertSame(
            [
                'key' => 'val',
                'extra' => [$fieldName => $activeProcessCorrelationId1]
            ],
            $record
        );

        $processor->resetToInitialProcessCorrelationId();
        $record = $processor(['key' => 'val']);
        $this->assertSame(['key' => 'val'], $record);
    }
}
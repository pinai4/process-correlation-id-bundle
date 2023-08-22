<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Functional;

use Pinai4\ProcessCorrelationIdBundle\Tests\TestKernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class InternalKernelTestCase extends KernelTestCase
{
    private static ?string $kernelCacheDir;
    private static ?string $kernelLogDir;

    protected function setUp(): void
    {
        self::$kernelCacheDir = null;
        self::$kernelLogDir = null;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearKernelTempFiles(self::$kernelCacheDir, self::$kernelLogDir);
    }

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    /**
     * @param array<string, string> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        /** @var TestKernel $kernel */
        $kernel = parent::createKernel($options);
        $kernel->handleOptions($options);

        self::$kernelCacheDir = $kernel->getCacheDir();
        self::$kernelLogDir = $kernel->getLogDir();

        return $kernel;
    }

    private function clearKernelTempFiles(?string $cacheDir, ?string $logDir): void
    {
        $filesystem = new Filesystem();

        if ($cacheDir !== null && $filesystem->exists($cacheDir)) {
            $filesystem->remove($cacheDir);
        }

        if ($logDir !== null && $filesystem->exists($logDir)) {
            $filesystem->remove($logDir);
        }
    }
}
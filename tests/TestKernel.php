<?php

namespace Pinai4\ProcessCorrelationIdBundle\Tests;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\ConfigBuilderCacheWarmer;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;

class TestKernel extends Kernel
{
    use MicroKernelTrait;

    /**
     * @var string[]
     */
    private $testBundle = [];

    /**
     * @var string[]|callable[]
     */
    private $testConfigs = [];

    /**
     * @var string
     */
    private $testCachePrefix;

    /**
     * @var string|null;
     */
    private $testProjectDir;

    /**
     * @var CompilerPassInterface[]
     */
    private $testCompilerPasses = [];

    /**
     * @var array<int, string>
     */
    private $testRoutingFiles = [];

    /**
     * Internal config.
     */
    private bool $clearCacheOnShutdown = true;

    public function __construct(string $environment, bool $debug)
    {
        parent::__construct($environment, $debug);

        $this->testCachePrefix = uniqid('cache', true);

        $this->addTestBundle(FrameworkBundle::class);
        $this->addTestConfig(__DIR__.'/Resources/config/TestKernel/framework.yml');
        if (class_exists(ConfigBuilderCacheWarmer::class)) {
            $this->addTestConfig(__DIR__.'/Resources/config/TestKernel/framework-53.yml');
        } else {
            $this->addTestConfig(__DIR__.'/Resources/config/TestKernel/framework-52.yml');
        }
    }

    /**
     * @psalm-param class-string<BundleInterface> $bundleClassName
     *
     * @param string $bundleClassName
     */
    public function addTestBundle($bundleClassName): void
    {
        $this->testBundle[] = $bundleClassName;
    }

    /**
     * @param string|callable $configFile path to a config file or a callable which get the {@see ContainerBuilder} as its first argument
     */
    public function addTestConfig($configFile): void
    {
        $this->testConfigs[] = $configFile;
    }

    public function getCacheDir(): string
    {
        return __DIR__.'/var/cache/'.$this->testCachePrefix;
    }

    public function getLogDir(): string
    {
        return __DIR__.'/var/log';
    }

    public function getProjectDir(): string
    {
        if (null === $this->testProjectDir) {
            return realpath(__DIR__.'/../');
        }

        return $this->testProjectDir;
    }

    /**
     * @param string|null $projectDir
     */
    public function setTestProjectDir($projectDir): void
    {
        $this->testProjectDir = $projectDir;
    }

    public function registerBundles(): iterable
    {
        $this->testBundle = array_unique($this->testBundle);

        foreach ($this->testBundle as $bundle) {
            yield new $bundle();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        foreach ($this->testCompilerPasses as $pass) {
            $container->addCompilerPass($pass);
        }

        return $container;
    }

    public function addTestCompilerPass(CompilerPassInterface $compilerPasses): void
    {
        $this->testCompilerPasses[] = $compilerPasses;
    }

    /**
     * @param string $routingFile
     */
    public function addTestRoutingFile($routingFile): void
    {
        $this->testRoutingFiles[] = $routingFile;
    }

    public function handleOptions(array $options): void
    {
        if (array_key_exists('config', $options) && is_callable($configCallable = $options['config'])) {
            $configCallable($this);
        }
    }

    /**
     * @throws \Exception
     */
    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        foreach ($this->testConfigs as $config) {
            $loader->load($config);
        }
    }

    /**
     * @param RoutingConfigurator|RouteCollectionBuilder $routes
     */
    protected function configureRoutes($routes): void
    {
        foreach ($this->testRoutingFiles as $routingFile) {
            $routes->import($routingFile);
        }
    }

    public function shutdown(): void
    {
        parent::shutdown();

        if (!$this->clearCacheOnShutdown) {
            return;
        }

        $this->clearCache();

    }

    public function clearCache(): void
    {
        $cacheDirectory = $this->getCacheDir();
        $logDirectory = $this->getLogDir();

        $filesystem = new Filesystem();

        if ($filesystem->exists($cacheDirectory)) {
            $filesystem->remove($cacheDirectory);
        }

        if ($filesystem->exists($logDirectory)) {
            $filesystem->remove($logDirectory);
        }
    }

    public function setClearCacheOnShutdown(bool $clearCacheOnShutdown): void
    {
        $this->clearCacheOnShutdown = $clearCacheOnShutdown;
    }
}

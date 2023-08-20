<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class Pinai4ProcessCorrelationIdExtension extends Extension
{
    public function getNamespace(): string
    {
        return 'http://pinai4_project.com/schema/dic/process-correlation-id';
    }
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        if (!$this->isConfigEnabled($container, $config)) {
            return;
        }

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $definition = $container->getDefinition('pinai4_process_correlation_id.monolog.processor');
        $definition->setArgument(0, $config['log_field_name']);
    }
}
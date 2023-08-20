<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle\Tests\Unit\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionConfigurationTestCase;
use Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Configuration;
use Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Pinai4ProcessCorrelationIdExtension;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ConfigurationTest extends AbstractExtensionConfigurationTestCase
{
    protected function getContainerExtension(): ExtensionInterface
    {
        return new Pinai4ProcessCorrelationIdExtension();
    }

    protected function getConfiguration(): ConfigurationInterface
    {
        return new Configuration();
    }

    public function testYamlConfig()
    {
        $sources = [
            __DIR__.'/../../Resources/config/Configuration/settings.yaml',
        ];

        $expectedConfiguration = ['enabled' => false, 'log_field_name' => 'process_correlation_id'];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }

    public function testXmlConfig()
    {
        $sources = [
            __DIR__.'/../../Resources/config/Configuration/settings.xml',
        ];

        $expectedConfiguration = ['enabled' => true, 'log_field_name' => 'field_name_from_xml_settings'];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }

    public function testPhpConfig()
    {
        $sources = [
            __DIR__.'/../../Resources/config/Configuration/settings.php',
        ];

        $expectedConfiguration = ['enabled' => true, 'log_field_name' => 'field_name_from_php_settings'];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }

    public function testMixedConfig()
    {
        $sources = [
            __DIR__.'/../../Resources/config/Configuration/settings.yaml',
            function (ContainerBuilder $container): void {
                $container->loadFromExtension(
                    'pinai4_process_correlation_id',
                    [
                        'log_field_name' => 'field_name_from_test_case'
                    ]
                );
            },
        ];

        $expectedConfiguration = ['enabled' => false, 'log_field_name' => 'field_name_from_test_case'];

        $this->assertProcessedConfigurationEquals($expectedConfiguration, $sources);
    }
}
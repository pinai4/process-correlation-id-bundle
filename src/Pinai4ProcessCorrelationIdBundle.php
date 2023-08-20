<?php

declare(strict_types=1);

namespace Pinai4\ProcessCorrelationIdBundle;

use Pinai4\ProcessCorrelationIdBundle\DependencyInjection\Compiler\MessengerBusesAutoconfigForDefaultMiddlewaresStackPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Pinai4ProcessCorrelationIdBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MessengerBusesAutoconfigForDefaultMiddlewaresStackPass());
    }
}
<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\ContainerBuilder;

/* @var $container ContainerBuilder */
$container->loadFromExtension(
    'pinai4_process_correlation_id',
    [
        'log_field_name' => 'field_name_from_php_settings',
    ]
);

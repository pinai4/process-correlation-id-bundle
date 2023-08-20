# Process Correlation Id Bundle

Bundle provides the ability to track Process Correlation Id and store it in logs

Installation
============

Make sure Composer is installed globally, as explained in the
[installation chapter](https://getcomposer.org/doc/00-intro.md)
of the Composer documentation.

Applications that use Symfony Flex
----------------------------------

Open a command console, enter your project directory and execute:

```console
$ composer require pinai4/process-correlation-id-bundle
```

Applications that don't use Symfony Flex
----------------------------------------

### Step 1: Download the Bundle

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require pinai4/process-correlation-id-bundle
```

### Step 2: Enable the Bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    Pinai4\ProcessCorrelationIdBundle\Pinai4ProcessCorrelationIdBundle::class => ['all' => true],
];
```

## Configuration

Monolog field name for ProcessCorrelationId value can be configured directly by
creating a new `config/packages/pinai4_process_correlation_id.yaml` file. 
You can also any moment enable/disable all bundle features in this file. The
default values are:

```yaml
# config/packages/pinai4_process_correlation_id.yaml
pinai4_process_correlation_id:

    # You can enable/disable all bundle features
    enabled:              true

    # You can customize monolog field name which will show ProcessCorrelationId value
    log_field_name:       process_correlation_id

```

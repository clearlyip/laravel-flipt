<img width="100%" src="https://raw.githubusercontent.com/flipt-io/flipt/refs/heads/v2/logo.svg"/>

Laravel-flipt was created by, and is maintained by **[Andrew Nagy](https://github.com/tm1000)**, the package is designed to allow Laravel to work with [Flipt](https://flipt.io/)

<p align="center">
<a href="https://packagist.org/packages/clearlyip/laravel-flipt"><img src="https://img.shields.io/packagist/dt/clearlyip/laravel-flipt" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/clearlyip/laravel-flipt"><img src="https://img.shields.io/packagist/v/clearlyip/laravel-flipt" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/clearlyip/laravel-flipt"><img src="https://img.shields.io/packagist/l/clearlyip/laravel-flipt" alt="License"></a>
</p>

## Features

- Registers as a driver for [Laravel Pennant](https://github.com/laravel/pennant)
- Registers a Flipt client class to access the API directly
- Utilizes Laravel's cache system to store flags in cache for quick access with configurable TTL

## Installation & Usage

> **Requires [PHP 8.4+](https://php.net/releases/)**

Require Laravel-flipt using [Composer](https://getcomposer.org):

```bash
composer require clearlyip/laravel-flipt
```

## Laravel Version Compatibility

| Laravel | Laravel Flipt |
| :------ | :------------ |
| 12.x    | 1.x           |

## Usage

### Configuration Files

- Publish the Laravel Flipt configuration file using the `vendor:publish` Artisan command. The `flipt` configuration file will be placed in your `config` directory (Use `--force` to overwrite your existing `clearly` config file):
    - `php artisan vendor:publish --tag="flipt" [--force]`

All options are fully documented in the configuration file

### Pennant

Register the driver for [Laravel Pennant](https://github.com/laravel/pennant) in the `pennant.php` configuration file

```php
'default' => env('PENNANT_STORE', 'flipt'),
'stores' => [
    'array' => [
        'driver' => 'array',
    ],

    'flipt' => [
        'driver' => 'flipt',
    ],
],
```

### User

Use the [hasFeatures](https://laravel.com/docs/12.x/pennant#the-has-features-trait) trait on your User model

Configure what parameters to use for entityId and context in the flipt configuration file

```php
'identity' => [
    'identifier' => 'id',
    'context' => [
        'email' => 'email',
        'my-value' => 'dot.notation.is.supported',
    ],
],
```

### Accessing

The Flipt Class can be accessed through Laravel's Container. The returned class is [https://github.com/clearlyip/laravel-flipt](https://github.com/clearlyip/laravel-flipt)

```php
$flipt = App::make(Flipt::class);
```

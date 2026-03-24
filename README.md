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
- Supports boolean and variant flag evaluation
- Supports batch evaluation via the `clientevaluation` API
- Provides access to Flipt's Environments, Flags, Evaluate, OpenFeature, and Internal APIs
- Ships with Artisan commands to clear flag caches globally or per user

## Installation & Usage

> **Requires [PHP 8.4+](https://php.net/releases/)**

Require Laravel-flipt using [Composer](https://getcomposer.org):

```bash
composer require clearlyip/laravel-flipt
```

## Laravel Version Compatibility

| Laravel    | Laravel Flipt |
| :--------- | :------------ |
| 12.x       | 1.x           |
| 12.x, 13.x | 2.x           |

## Usage

### Configuration Files

Publish the Laravel Flipt configuration file using the `vendor:publish` Artisan command. The `flipt` configuration file will be placed in your `config` directory (use `--force` to overwrite an existing config file):

```bash
php artisan vendor:publish --tag="flipt" [--force]
```

All options are fully documented in the published configuration file.

Key environment variables:

| Variable            | Default | Description            |
| :------------------ | :------ | :--------------------- |
| `FLIPT_HOST`        | `null`  | The Flipt API host URL |
| `FLIPT_NAMESPACE`   | `null`  | The Flipt namespace    |
| `FLIPT_ENVIRONMENT` | `local` | The Flipt environment  |

### Pennant

Register the driver for [Laravel Pennant](https://github.com/laravel/pennant) in the `pennant.php` configuration file:

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

### User Identity

Use the [hasFeatures](https://laravel.com/docs/12.x/pennant#the-has-features-trait) trait on your User model.

Configure what parameters to use for `entityId` and context in the flipt configuration file. Dot notation is supported for nested model attributes:

```php
'identity' => [
    'identifier' => 'id',
    'context' => [
        'email' => 'email',
        'my-value' => 'dot.notation.is.supported',
    ],
],
```

### Accessing the Flipt Client

The `Flipt` class can be resolved through Laravel's service container:

```php
use Clearlyip\LaravelFlipt\Flipt;

$flipt = App::make(Flipt::class);
```

The client exposes the following API modules as magic properties:

| Property                   | Class                    | Description                         |
| :------------------------- | :----------------------- | :---------------------------------- |
| `$flipt->evaluate`         | `Flipt\Evaluate`         | Boolean and variant flag evaluation |
| `$flipt->clientevaluation` | `Flipt\ClientEvaluation` | Batch/client-side evaluation        |
| `$flipt->openfeature`      | `Flipt\OpenFeature`      | OpenFeature-compatible evaluation   |
| `$flipt->environments`     | `Flipt\Environments`     | Manage Flipt environments           |
| `$flipt->flags`            | `Flipt\Flags`            | List and retrieve flag definitions  |
| `$flipt->internal`         | `Flipt\Internal`         | Internal Flipt API access           |

#### Disabling the Cache

To skip the cache for a single call chain, use `withSkipCache()`:

```php
$flipt->withSkipCache()->evaluate->boolean($request);
```

### Artisan Commands

| Command                                       | Description                            |
| :-------------------------------------------- | :------------------------------------- |
| `php artisan flipt:cache:clear`               | Clear all cached Flipt flag responses  |
| `php artisan flipt:cache:user:clear {userId}` | Clear cached flags for a specific user |

### Caching

The package uses Laravel's cache system to cache Flipt API responses. Configure the cache behavior in `config/flipt.php`:

```php
'cache' => [
    'store'  => env('FLIPT_CACHE_STORE', 'default'),
    'prefix' => env('FLIPT_CACHE_PREFIX', 'flipt'),
    'ttl'    => env('FLIPT_CACHE_TTL', 60),
    'tags'   => ['flipt'],
],
```

The cache store must support tagging (e.g., Redis, Memcached) for the cache-clearing commands to work correctly.

## Contributing

Contributions are welcome. Please open an issue or pull request on [GitHub](https://github.com/clearlyip/laravel-flipt).

## License

Laravel-flipt is open-sourced software licensed under the [BSD-3-Clause license](LICENSE).

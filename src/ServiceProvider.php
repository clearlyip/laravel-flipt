<?php

namespace Clearlyip\LaravelFlipt;

use Clearlyip\LaravelFlipt\Commands\CacheClear;
use Clearlyip\LaravelFlipt\Commands\UserCacheClear;
use Clearlyip\LaravelFlipt\Pennant\FliptFeatureDriver;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Pennant\Feature;

class ServiceProvider extends LaravelServiceProvider
{
    public const FLIPT_CONFIG_PATH = __DIR__ . '/../config/flipt.php';

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    #[\Override]
    public function register()
    {
        $this->mergeConfigFrom(self::FLIPT_CONFIG_PATH, 'flipt');
        $this->app->bind(Flipt::class, static function (Application $app) {
            /** @var string|null $store */
            $store = config('flipt.cache.store', null);
            $storeKey = $store === null || $store === 'default' ? null : $store;

            $cacheFactory = $app->make(\Illuminate\Contracts\Cache\Factory::class);

            /** @var \Illuminate\Cache\Repository $cacheProvider */
            $cacheProvider = $cacheFactory->store($storeKey);

            /** @var string|null $envConfig */
            $envConfig = config('flipt.environment');

            return new Flipt(
                host: (string) config('flipt.host', ''),
                namespace: (string) config('flipt.namespace', 'default'),
                environment: is_string($envConfig) ? $envConfig : null,
                cache: $cacheProvider,
            );
        });

        $this->commands([CacheClear::class, UserCacheClear::class]);
    }

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            self::FLIPT_CONFIG_PATH => config_path('flipt.php'),
        ], ['flipt']);
        $this->loadRoutesFrom(dirname(__DIR__) . '/routes/flipt.php');

        Feature::extend(
            'flipt',
            fn(Application $app) => new FliptFeatureDriver(
                $app->make(Flipt::class),
                $app->make(\Illuminate\Contracts\Events\Dispatcher::class),
            ),
        );
    }
}

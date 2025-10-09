<?php

namespace Clearlyip\LaravelFlipt;

use Clearlyip\LaravelFlipt\Commands\CacheClear;
use Clearlyip\LaravelFlipt\Commands\UserCacheClear;
use Clearlyip\LaravelFlipt\Pennant\FliptFeatureDriver;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Pennant\Feature;
use Illuminate\Contracts\Foundation\Application;

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
        $this->app->bind(Flipt::class, function ($app) {
            $store = config('flipt.cache.store', null);

            $cacheFactory = $app->make(
                \Illuminate\Contracts\Cache\Factory::class,
            );

            $cacheProvider = $cacheFactory->store(
                $store === 'default' ? null : $store,
            );

            return new Flipt(
                host: config('flipt.host', ''),
                namespace: config('flipt.namespace'),
                environment: config('flipt.environment'),
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
        $this->publishes(
            [
                self::FLIPT_CONFIG_PATH => config_path('flipt.php'),
            ],
            ['flipt'],
        );
        $this->loadRoutesFrom(dirname(__DIR__) . '/routes/flipt.php');

        Feature::extend(
            'flipt',
            fn(Application $app) => new FliptFeatureDriver(
                $app->make(Flipt::class),
                $app->make('events'),
            ),
        );
    }
}

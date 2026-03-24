<?php

namespace CIP\Tests;

use Clearlyip\LaravelFlipt\ServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [ServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('flipt.host', 'http://localhost:8080');
        $app['config']->set('flipt.namespace', 'default');
        $app['config']->set('flipt.environment', 'test');
        $app['config']->set('flipt.identity.identifier', 'id');
        $app['config']->set('flipt.identity.context', ['email' => 'email']);
        $app['config']->set('flipt.cache.store', null);
        $app['config']->set('flipt.cache.prefix', 'flipt');
        $app['config']->set('flipt.cache.tags', ['flipt']);
        $app['config']->set('flipt.cache.ttl', 60);
    }
}

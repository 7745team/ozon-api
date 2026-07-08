<?php

namespace Tdkomplekt\OzonApi\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Tdkomplekt\OzonApi\Facades\OzonApi;
use Tdkomplekt\OzonApi\OzonApiServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            OzonApiServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'OzonApi' => OzonApi::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]);

        $app['config']->set('ozon-api.client_id', 'test-client-id');
        $app['config']->set('ozon-api.api_key', 'test-api-key');
    }
}

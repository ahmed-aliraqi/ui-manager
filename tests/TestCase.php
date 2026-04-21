<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests;

use AhmedAliraqi\UiManager\UiManagerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // Package tests don't test CSRF protection itself; disable it so
        // PUT/POST/DELETE requests work without a token in the test session.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            UiManagerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('ui-manager.cache.enabled', false);

        // Disable CSRF for API tests
        $app['config']->set('session.driver', 'array');
    }

    protected function resolveApplicationExceptionHandler($app): void
    {
        // Surface all exceptions in tests for easier debugging
        parent::resolveApplicationExceptionHandler($app);
    }
}

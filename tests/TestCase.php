<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests;

use AhmedAliraqi\UiManager\UiManagerServiceProvider;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase as Orchestra;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    protected function getPackageProviders($app): array
    {
        return [
            MediaLibraryServiceProvider::class,
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
        $app['config']->set('session.driver', 'array');

        // Spatie Media Library settings for tests
        $app['config']->set('media-library.disk_name', 'public');
        $app['config']->set('media-library.media_model', \Spatie\MediaLibrary\MediaCollections\Models\Media::class);
        $app['config']->set('filesystems.disks.public', [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => '/storage',
            'visibility' => 'public',
        ]);
    }
}

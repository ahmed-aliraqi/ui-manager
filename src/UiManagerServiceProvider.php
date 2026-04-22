<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager;

use AhmedAliraqi\UiManager\Console\InstallCommand;
use AhmedAliraqi\UiManager\Console\MakeUiPageCommand;
use AhmedAliraqi\UiManager\Console\MakeUiSectionCommand;
use AhmedAliraqi\UiManager\Services\MediaUploadService;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Services\UiManager;
use AhmedAliraqi\UiManager\Services\VariableParser;
use AhmedAliraqi\UiManager\Variables\VariableRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class UiManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/ui-manager.php', 'ui-manager');

        $this->app->singleton(PageRegistry::class);
        $this->app->singleton(SectionRegistry::class);
        $this->app->singleton(VariableRegistry::class);
        $this->app->singleton(VariableParser::class);
        $this->app->singleton(MediaUploadService::class);

        $this->app->singleton(UiManager::class, function ($app) {
            return new UiManager(
                $app->make(SectionRegistry::class),
                $app->make(PageRegistry::class),
            );
        });

        // Register a convenient VariableParser that gets the registry injected
        $this->app->singleton(VariableParser::class, function ($app) {
            return new VariableParser($app->make(VariableRegistry::class));
        });
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'ui-manager');
        $this->registerRoutes();
        $this->registerBladeDirectives();
        $this->registerPublishables();
        $this->registerBuiltInVariables();

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                MakeUiPageCommand::class,
                MakeUiSectionCommand::class,
            ]);
        }
    }

    private function registerRoutes(): void
    {
        $routeConfig = config('ui-manager.routes');

        // JSON API routes must be registered BEFORE the SPA catch-all
        // so that /ui-manager/api/* requests don't fall through to the view.
        $this->app['router']
            ->middleware($routeConfig['api_middleware'])
            ->prefix($routeConfig['api_prefix'])
            ->group(__DIR__ . '/../routes/api.php');

        // SPA catch-all web route (must come last)
        $this->app['router']
            ->middleware($routeConfig['middleware'])
            ->prefix($routeConfig['prefix'])
            ->group(__DIR__ . '/../routes/web.php');
    }

    private function registerBladeDirectives(): void
    {
        // @uiField('section', 'field')
        Blade::directive('uiField', function (string $expression): string {
            [$section, $field] = array_map('trim', explode(',', $expression, 2));

            return "<?php echo e(ui({$section})->field({$field})); ?>";
        });

        // @uiSection('section') ... @enduiSection
        Blade::directive('uiSection', function (string $expression): string {
            return "<?php \$__uiSection = ui({$expression}); ?>";
        });
    }

    private function registerPublishables(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ui-manager.php' => config_path('ui-manager.php'),
        ], 'ui-manager-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'ui-manager-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/ui-manager'),
        ], 'ui-manager-views');

        // Publish the pre-built dist/ directory to public/vendor/ui-manager/.
        // dist/ is the direct Vite output, so manifest.json ends up at
        // public/vendor/ui-manager/manifest.json — exactly where the controller reads it.
        $this->publishes([
            __DIR__ . '/../dist' => public_path('vendor/ui-manager'),
        ], 'ui-manager-assets');
    }

    private function registerBuiltInVariables(): void
    {
        /** @var VariableRegistry $registry */
        $registry = $this->app->make(VariableRegistry::class);

        $registry->register('app.name', fn () => config('app.name'));
        $registry->register('app.url',  fn () => config('app.url'));
        $registry->register('app.env',  fn () => config('app.env'));
    }
}

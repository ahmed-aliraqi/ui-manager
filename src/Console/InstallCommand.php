<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Console;

use Illuminate\Console\Command;

final class InstallCommand extends Command
{
    protected $signature = 'ui-manager:install
        {--force : Overwrite existing published files}';

    protected $description = 'Install UI Manager: publish config & assets, then run migrations';

    public function handle(): int
    {
        $this->info('Installing UI Manager...');
        $this->newLine();

        $this->publishConfig();
        $this->publishAssets();
        $this->runMigrations();

        $this->newLine();
        $this->info('UI Manager installed successfully.');
        $this->line('  Dashboard: <comment>' . url(config('ui-manager.routes.prefix', 'ui-manager')) . '</comment>');

        return self::SUCCESS;
    }

    private function publishConfig(): void
    {
        $this->components->task('Publishing config', function () {
            $this->callSilently('vendor:publish', [
                '--tag'   => 'ui-manager-config',
                '--force' => $this->option('force'),
            ]);
        });
    }

    private function publishAssets(): void
    {
        $this->components->task('Publishing assets', function () {
            $this->callSilently('vendor:publish', [
                '--tag'   => 'ui-manager-assets',
                '--force' => $this->option('force'),
            ]);
        });
    }

    private function runMigrations(): void
    {
        $this->components->task('Running migrations', function () {
            $this->callSilently('migrate');
        });
    }
}

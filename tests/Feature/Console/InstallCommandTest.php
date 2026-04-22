<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Console;

use AhmedAliraqi\UiManager\Tests\TestCase;

final class InstallCommandTest extends TestCase
{
    public function test_install_command_runs_successfully(): void
    {
        $this->artisan('ui-manager:install')
            ->assertSuccessful();
    }

    public function test_install_command_outputs_dashboard_url(): void
    {
        $this->artisan('ui-manager:install')
            ->assertSuccessful()
            ->expectsOutputToContain('ui-manager');
    }

    public function test_install_command_publishes_config(): void
    {
        $this->artisan('ui-manager:install');

        $this->assertFileExists(config_path('ui-manager.php'));
    }

    public function test_install_command_with_force_flag(): void
    {
        $this->artisan('ui-manager:install', ['--force' => true])
            ->assertSuccessful();
    }
}

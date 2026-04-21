<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class MakeUiPageCommand extends Command
{
    protected $signature = 'make:ui-page
        {name? : The page class name (e.g. About or Marketing/About)}
        {--name= : The slug name used in the system}
        {--force : Overwrite existing file}';

    protected $description = 'Create a new UI Page class';

    public function handle(): int
    {
        $className = $this->argument('name') ?? $this->ask('Page class name (e.g. About)');
        $className = (string) $className;

        $slug = $this->option('name')
            ?? Str::slug(str_replace(['/', '\\'], '-', $className));

        $relativePath = str_replace('/', DIRECTORY_SEPARATOR, $className);
        $namespace    = 'App\\Ui\\Pages';
        $classBaseName = class_basename(str_replace('/', '\\', $className));

        // Handle sub-namespaces like Marketing/About
        if (str_contains($className, '/')) {
            $subNs    = str_replace('/', '\\', dirname($className));
            $namespace .= '\\' . $subNs;
        }

        $targetDir  = app_path('Ui/Pages/' . dirname(str_replace('/', DIRECTORY_SEPARATOR, $className)));
        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $classBaseName . '.php';

        if (str_ends_with($targetDir, DIRECTORY_SEPARATOR . '.')) {
            $targetDir  = app_path('Ui/Pages');
            $targetFile = $targetDir . DIRECTORY_SEPARATOR . $classBaseName . '.php';
        }

        if (file_exists($targetFile) && ! $this->option('force')) {
            $this->error("File already exists: {$targetFile}");

            return self::FAILURE;
        }

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $stub = $this->buildStub($namespace, $classBaseName, $slug);

        file_put_contents($targetFile, $stub);

        $this->info("Page created: {$targetFile}");
        $this->line("  Name: <comment>{$slug}</comment>");

        return self::SUCCESS;
    }

    private function buildStub(string $namespace, string $class, string $name): string
    {
        $displayName = ucwords(str_replace('-', ' ', $name));

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use AhmedAliraqi\\UiManager\\Core\\Page;

        class {$class} extends Page
        {
            protected string \$name = '{$name}';

            public function getDisplayName(): string
            {
                return __('{$displayName}');
            }
        }
        PHP;
    }
}

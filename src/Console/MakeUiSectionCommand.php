<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Console;

use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class MakeUiSectionCommand extends Command
{
    protected $signature = 'make:ui-section
        {name? : The section class name (e.g. Banner or Home/Banner)}
        {--page= : Fully-qualified Page class (e.g. App\\Ui\\Pages\\Home)}
        {--repeatable : Make this section repeatable}
        {--layout= : Layout name (default: default)}
        {--force : Overwrite existing file}';

    protected $description = 'Create a new UI Section class';

    public function handle(): int
    {
        $className = $this->argument('name') ?? $this->ask('Section class name (e.g. Banner)');
        $className = (string) $className;

        $pageClass = $this->option('page') ?? $this->selectPage();

        $layout     = $this->option('layout') ?? 'default';
        $repeatable = (bool) $this->option('repeatable');
        $slug       = Str::slug(str_replace(['/', '\\'], '-', class_basename($className)));

        $namespace    = 'App\\Ui\\Sections';
        $classBaseName = class_basename(str_replace('/', '\\', $className));

        if (str_contains($className, '/')) {
            $subNs    = str_replace('/', '\\', dirname($className));
            $namespace .= '\\' . $subNs;
        }

        $targetDir  = app_path('Ui/Sections');

        if (str_contains($className, '/')) {
            $targetDir .= '/' . dirname($className);
        }

        $targetFile = $targetDir . DIRECTORY_SEPARATOR . $classBaseName . '.php';

        if (file_exists($targetFile) && ! $this->option('force')) {
            $this->error("File already exists: {$targetFile}");

            return self::FAILURE;
        }

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $stub = $this->buildStub($namespace, $classBaseName, $slug, $pageClass, $layout, $repeatable);

        file_put_contents($targetFile, $stub);

        $this->info("Section created: {$targetFile}");
        $this->line("  Name:       <comment>{$slug}</comment>");
        $this->line("  Page:       <comment>{$pageClass}</comment>");
        $this->line("  Layout:     <comment>{$layout}</comment>");
        $this->line("  Repeatable: <comment>" . ($repeatable ? 'yes' : 'no') . "</comment>");

        return self::SUCCESS;
    }

    /**
     * Auto-discover registered pages and show an interactive choice list.
     * Falls back to a free-text ask when no pages are registered yet.
     */
    private function selectPage(): string
    {
        /** @var array<string, Page> $pages */
        $pages = app(PageRegistry::class)->all();

        if (empty($pages)) {
            return (string) $this->ask(
                'Page class (e.g. App\\Ui\\Pages\\Home)',
                'App\\Ui\\Pages\\Home',
            );
        }

        $classes = array_values(array_map('get_class', $pages));
        $display = array_map(
            fn (Page $p) => get_class($p) . '  [' . $p->getName() . ']',
            array_values($pages),
        );
        $customLabel = 'Enter class manually...';
        $display[]   = $customLabel;

        $selected = (string) $this->choice(
            'Select the page this section belongs to',
            $display,
            0,
        );

        if ($selected === $customLabel) {
            return (string) $this->ask(
                'Page class (e.g. App\\Ui\\Pages\\Home)',
                'App\\Ui\\Pages\\Home',
            );
        }

        $idx = array_search($selected, $display, true);

        return $classes[$idx] ?? 'App\\Ui\\Pages\\Home';
    }

    private function buildStub(
        string $namespace,
        string $class,
        string $name,
        string $pageClass,
        string $layout,
        bool $repeatable,
    ): string {
        $repeatableImpl  = $repeatable ? ' implements Repeatable' : '';
        $repeatableUse   = $repeatable
            ? "\nuse AhmedAliraqi\\UiManager\\Contracts\\Repeatable;" : '';
        $displayName     = ucwords(str_replace('-', ' ', $name));

        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace {$namespace};

        use AhmedAliraqi\\UiManager\\Core\\Section;
        use AhmedAliraqi\\UiManager\\Fields\\Field;{$repeatableUse}
        use {$pageClass};

        class {$class} extends Section{$repeatableImpl}
        {
            protected string \$name = '{$name}';

            protected string \$page = {$this->shortClass($pageClass)}::class;

            public function fields(): array
            {
                return [
                    Field::text('title')->rules(['required', 'string', 'max:255']),
                ];
            }
        }
        PHP;
    }

    private function shortClass(string $fqcn): string
    {
        return class_basename($fqcn);
    }
}

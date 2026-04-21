<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Support\ClassDiscovery;

final class PageRegistry
{
    /** @var array<string, Page> keyed by page name */
    private array $pages = [];

    private bool $discovered = false;

    public function register(Page $page): void
    {
        $this->pages[$page->getName()] = $page;
    }

    /**
     * Register a page class by its FQCN.
     *
     * @param class-string<Page> $class
     */
    public function registerClass(string $class): void
    {
        $this->register(new $class());
    }

    /**
     * @return array<string, Page>
     */
    public function all(): array
    {
        $this->autoDiscover();

        return collect($this->pages)
            ->filter(fn (Page $p) => $p->isVisible())
            ->sortBy(fn (Page $p) => [$p->getOrder(), $p->getName()])
            ->all();
    }

    public function find(string $name): ?Page
    {
        $this->autoDiscover();

        return $this->pages[$name] ?? null;
    }

    /**
     * Find a registered page by its concrete class name.
     */
    public function findByClass(string $className): ?Page
    {
        $this->autoDiscover();

        foreach ($this->pages as $page) {
            if (get_class($page) === $className) {
                return $page;
            }
        }

        return null;
    }

    public function findOrFail(string $name): Page
    {
        $page = $this->find($name);

        if ($page === null) {
            abort(404, "UI page [{$name}] is not registered.");
        }

        return $page;
    }

    private function autoDiscover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        $path      = config('ui-manager.discovery.pages_path', 'app/Ui/Pages');
        $namespace = config('ui-manager.discovery.pages_namespace', 'App\\Ui\\Pages');

        foreach (ClassDiscovery::discover($path, $namespace, Page::class) as $class) {
            if (! isset($this->pages)) {
                continue;
            }

            $instance = new $class();

            if (! isset($this->pages[$instance->getName()])) {
                $this->pages[$instance->getName()] = $instance;
            }
        }
    }
}

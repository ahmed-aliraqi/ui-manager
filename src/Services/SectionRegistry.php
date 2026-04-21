<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Support\ClassDiscovery;

final class SectionRegistry
{
    /** @var array<string, Section>  keyed as "pageClass::sectionName" */
    private array $sections = [];

    private bool $discovered = false;

    public function register(Section $section): void
    {
        $this->sections[$this->key($section->getPage(), $section->getName())] = $section;
    }

    /**
     * @param class-string<Section> $class
     */
    public function registerClass(string $class): void
    {
        $this->register(new $class());
    }

    /**
     * Find all visible sections for a given page.
     *
     * Accepts the page's FQCN (production path) or its name slug
     * (common in tests / dynamic registration).
     *
     * @return array<string, Section>
     */
    public function forPage(string $pageClassOrName): array
    {
        $this->autoDiscover();

        return collect($this->sections)
            ->filter(function (Section $s) use ($pageClassOrName) {
                return $s->isVisible()
                    && ($s->getPage() === $pageClassOrName
                        || $this->pageMatchesName($s->getPage(), $pageClassOrName));
            })
            ->sortBy(fn (Section $s) => [$s->getOrder(), $s->getName()])
            ->all();
    }

    public function find(string $pageClassOrName, string $sectionName): ?Section
    {
        $this->autoDiscover();

        // Direct key lookup (fast path when full class name is used)
        $direct = $this->sections[$this->key($pageClassOrName, $sectionName)] ?? null;
        if ($direct !== null) {
            return $direct;
        }

        // Fallback: search by page name match
        foreach ($this->sections as $section) {
            if ($section->getName() === $sectionName
                && $this->pageMatchesName($section->getPage(), $pageClassOrName)) {
                return $section;
            }
        }

        return null;
    }

    /**
     * Check if a stored page reference (FQCN or name) matches a given name.
     * Resolves FQCN → registered Page → getName() for comparison.
     */
    private function pageMatchesName(string $storedPage, string $name): bool
    {
        if ($storedPage === $name) {
            return true;
        }

        // $storedPage may be a FQCN; instantiate and compare name
        if (class_exists($storedPage)) {
            try {
                $instance = new $storedPage();
                if (method_exists($instance, 'getName')) {
                    return $instance->getName() === $name;
                }
            } catch (\Throwable) {
                // uninstantiable — skip
            }
        }

        return false;
    }

    /**
     * Find a section by its short name across all pages.
     * Useful when the page context is not known.
     */
    public function findByName(string $sectionName): ?Section
    {
        $this->autoDiscover();

        foreach ($this->sections as $section) {
            if ($section->getName() === $sectionName) {
                return $section;
            }
        }

        return null;
    }

    private function key(string $page, string $name): string
    {
        return "{$page}::{$name}";
    }

    private function autoDiscover(): void
    {
        if ($this->discovered) {
            return;
        }

        $this->discovered = true;

        $path      = config('ui-manager.discovery.sections_path', 'app/Ui/Sections');
        $namespace = config('ui-manager.discovery.sections_namespace', 'App\\Ui\\Sections');

        foreach (ClassDiscovery::discover($path, $namespace, Section::class) as $class) {
            $instance = new $class();
            $key      = $this->key($instance->getPage(), $instance->getName());

            if (! isset($this->sections[$key])) {
                $this->sections[$key] = $instance;
            }
        }
    }
}

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Support\RepeatableSectionView;
use AhmedAliraqi\UiManager\Support\SectionView;
use Illuminate\Support\Facades\Cache;

/**
 * Primary entry point, bound to the "ui" helper and Ui facade.
 *
 *   ui()->section('banner')->field('title')
 *   ui()->section('social')   // RepeatableSectionView — iterable
 */
final class UiManager
{
    public function __construct(
        private readonly SectionRegistry $sectionRegistry,
        private readonly PageRegistry $pageRegistry,
    ) {}

    /** @return SectionView|RepeatableSectionView */
    public function section(string $name): SectionView|RepeatableSectionView
    {
        $definition = $this->sectionRegistry->findByName($name);

        if ($definition === null) {
            throw new \RuntimeException("UI section [{$name}] is not registered.");
        }

        return $definition->isRepeatable()
            ? $this->buildRepeatableView($definition)
            : $this->buildSingleView($definition);
    }

    /** @return SectionView|RepeatableSectionView|null */
    public function sectionOrNull(string $name): SectionView|RepeatableSectionView|null
    {
        try {
            return $this->section($name);
        } catch (\RuntimeException) {
            return null;
        }
    }

    public function pages(): PageRegistry
    {
        return $this->pageRegistry;
    }

    public function sections(): SectionRegistry
    {
        return $this->sectionRegistry;
    }

    // ------------------------------------------------------------------

    private function buildSingleView(Section $definition): SectionView
    {
        // Use the resolved page *name* (not FQCN) so that cache keys match
        // the keys used by flushCache(), which receives the name from route params.
        $pageName = $this->resolvePageName($definition->getPage());
        $cacheKey = $this->cacheKey($pageName, $definition->getName());

        $data = $this->cached($cacheKey, function () use ($definition, $pageName): array {
            $record = UiContent::findSection($pageName, $definition->getName());

            return $record ? ($record->fields ?? []) : [];
        });

        $merged = array_merge($definition->resolveDefaults(), $data);

        return new SectionView($definition, $merged);
    }

    private function buildRepeatableView(Section $definition): RepeatableSectionView
    {
        $pageName = $this->resolvePageName($definition->getPage());
        $cacheKey = $this->cacheKey($pageName, $definition->getName(), 'repeatable');

        $rows = $this->cached($cacheKey, function () use ($definition, $pageName): array {
            $dbRows = UiContent::findRepeatableItems($pageName, $definition->getName())
                ->map(fn (UiContent $c) => $c->fields ?? [])->all();

            return $dbRows !== [] ? $dbRows : $definition->default();
        });

        return new RepeatableSectionView($definition, $rows);
    }

    /**
     * Resolve a page identifier (FQCN or slug) to its canonical name slug.
     */
    private function resolvePageName(string $pageClass): string
    {
        $byName = $this->pageRegistry->find($pageClass);
        if ($byName !== null) {
            return $byName->getName();
        }

        $byClass = $this->pageRegistry->findByClass($pageClass);
        if ($byClass !== null) {
            return $byClass->getName();
        }

        return $pageClass;
    }

    private function cacheKey(string $page, string $section, string $suffix = ''): string
    {
        $prefix = config('ui-manager.cache.prefix', 'ui_manager_');

        return $prefix . md5("{$page}_{$section}_{$suffix}");
    }

    /** @template T @param Closure(): T $callback @return T */
    private function cached(string $key, \Closure $callback): mixed
    {
        if (! config('ui-manager.cache.enabled', true)) {
            return $callback();
        }

        return Cache::remember($key, (int) config('ui-manager.cache.ttl', 3600), $callback);
    }

    public function flushCache(string $page, string $section): void
    {
        Cache::forget($this->cacheKey($page, $section));
        Cache::forget($this->cacheKey($page, $section, 'repeatable'));
    }

    public function flushAllCache(): void
    {
        Cache::flush();
    }
}

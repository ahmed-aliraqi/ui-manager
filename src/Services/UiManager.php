<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Contracts\Repeatable;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Support\RepeatableSectionView;
use AhmedAliraqi\UiManager\Support\SectionView;
use Illuminate\Support\Facades\Cache;

/**
 * Primary entry point, bound to the "ui" helper and Ui facade.
 *
 * Usage:
 *   ui()->section('banner')->field('title')
 *   ui()->section('social')  // RepeatableSectionView for @foreach
 */
final class UiManager
{
    public function __construct(
        private readonly SectionRegistry $sectionRegistry,
        private readonly PageRegistry $pageRegistry,
    ) {}

    /**
     * Return a SectionView (non-repeatable) or RepeatableSectionView.
     *
     * @return SectionView|RepeatableSectionView
     */
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

    /**
     * Same as section() but returns null instead of throwing when not found.
     *
     * @return SectionView|RepeatableSectionView|null
     */
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
        $cacheKey = $this->cacheKey($definition->getPage(), $definition->getName());

        $data = $this->cached($cacheKey, function () use ($definition): array {
            $record = UiContent::findSection(
                $this->resolvePageName($definition->getPage()),
                $definition->getName()
            );

            return $record ? ($record->fields ?? []) : [];
        });

        $merged = array_merge($definition->resolveDefaults(), $data);

        return new SectionView($definition, $merged);
    }

    private function buildRepeatableView(Section $definition): RepeatableSectionView
    {
        $cacheKey = $this->cacheKey($definition->getPage(), $definition->getName(), 'repeatable');

        $rows = $this->cached($cacheKey, function () use ($definition): array {
            return UiContent::findRepeatableItems(
                $this->resolvePageName($definition->getPage()),
                $definition->getName()
            )->map(fn (UiContent $c) => $c->fields ?? [])->all();
        });

        return new RepeatableSectionView($definition, $rows);
    }

    /**
     * Resolve page name string from a Page FQCN or a raw name slug.
     *
     * Precedence:
     *  1. Direct name lookup (fast path when $pageClass is actually a name slug)
     *  2. Class name lookup (when $pageClass is a FQCN or anonymous class name)
     *  3. Fallback: return $pageClass as-is (legacy / unknown pages)
     */
    private function resolvePageName(string $pageClass): string
    {
        // Fast path: might already be registered as a name
        $byName = $this->pageRegistry->find($pageClass);
        if ($byName !== null) {
            return $byName->getName();
        }

        // Slow path: FQCN or anonymous class name
        $byClass = $this->pageRegistry->findByClass($pageClass);
        if ($byClass !== null) {
            return $byClass->getName();
        }

        // Fallback (e.g. page not registered but name was passed directly)
        return $pageClass;
    }

    private function cacheKey(string $page, string $section, string $suffix = ''): string
    {
        $prefix = config('ui-manager.cache.prefix', 'ui_manager_');

        return $prefix . md5("{$page}_{$section}_{$suffix}");
    }

    /**
     * @template T
     * @param  Closure(): T $callback
     * @return T
     */
    private function cached(string $key, \Closure $callback): mixed
    {
        if (! config('ui-manager.cache.enabled', true)) {
            return $callback();
        }

        $ttl = (int) config('ui-manager.cache.ttl', 3600);

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Flush the cache for a specific page+section.
     */
    public function flushCache(string $page, string $section): void
    {
        Cache::forget($this->cacheKey($page, $section));
        Cache::forget($this->cacheKey($page, $section, 'repeatable'));
    }

    /**
     * Flush all ui-manager cache keys.
     */
    public function flushAllCache(): void
    {
        Cache::flush();
    }
}

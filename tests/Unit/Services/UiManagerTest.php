<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Services;

use AhmedAliraqi\UiManager\Contracts\Repeatable;
use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Services\UiManager;
use AhmedAliraqi\UiManager\Support\RepeatableSectionView;
use AhmedAliraqi\UiManager\Support\SectionView;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class UiManagerTest extends TestCase
{
    private UiManager $manager;
    private SectionRegistry $sections;
    private PageRegistry $pages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages   = $this->app->make(PageRegistry::class);
        $this->sections = $this->app->make(SectionRegistry::class);
        $this->manager = $this->app->make(UiManager::class);
    }

    public function test_section_returns_section_view_for_non_repeatable(): void
    {
        $page    = $this->makeTestPage();
        $section = $this->makeTestSection($page);

        $this->pages->register($page);
        $this->sections->register($section);

        $view = $this->manager->section('test-section');

        $this->assertInstanceOf(SectionView::class, $view);
    }

    public function test_section_returns_repeatable_view_for_repeatable(): void
    {
        $page    = $this->makeTestPage();
        $section = $this->makeRepeatableTestSection($page);

        $this->pages->register($page);
        $this->sections->register($section);

        $view = $this->manager->section('test-repeatable');

        $this->assertInstanceOf(RepeatableSectionView::class, $view);
    }

    public function test_section_falls_back_to_defaults(): void
    {
        $page    = $this->makeTestPage();
        $section = $this->makeTestSection($page);

        $this->pages->register($page);
        $this->sections->register($section);

        $view  = $this->manager->section('test-section');
        $value = $view->field('title')->getString();

        $this->assertSame('Default Title', $value);
    }

    public function test_section_uses_db_value_over_default(): void
    {
        $page    = $this->makeTestPage();
        $section = $this->makeTestSection($page);

        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create([
            'layout'  => 'default',
            'page'    => 'test-page',
            'section' => 'test-section',
            'fields'  => ['title' => 'DB Value'],
        ]);

        $view  = $this->manager->section('test-section');
        $value = $view->field('title')->getString();

        $this->assertSame('DB Value', $value);
    }

    public function test_repeatable_section_is_iterable(): void
    {
        $page    = $this->makeTestPage();
        $section = $this->makeRepeatableTestSection($page);

        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create([
            'layout'     => 'default',
            'page'       => 'test-page',
            'section'    => 'test-repeatable',
            'fields'     => ['label' => 'Item One'],
            'sort_order' => 0,
        ]);
        UiContent::create([
            'layout'     => 'default',
            'page'       => 'test-page',
            'section'    => 'test-repeatable',
            'fields'     => ['label' => 'Item Two'],
            'sort_order' => 1,
        ]);

        $view = $this->manager->section('test-repeatable');

        $this->assertInstanceOf(RepeatableSectionView::class, $view);
        $this->assertCount(2, $view);

        $labels = [];
        foreach ($view as $item) {
            $labels[] = $item->field('label')->getString();
        }

        $this->assertSame(['Item One', 'Item Two'], $labels);
    }

    public function test_section_or_null_returns_null_for_unknown(): void
    {
        $view = $this->manager->sectionOrNull('does-not-exist');

        $this->assertNull($view);
    }

    public function test_flush_cache_does_not_throw(): void
    {
        $this->manager->flushCache('test-page', 'test-section');
        $this->assertTrue(true); // no exception thrown
    }

    // ------------------------------------------------------------------

    private function makeTestPage(): Page
    {
        return new class extends Page {
            protected string $name = 'test-page';
        };
    }

    private function makeTestSection(Page $page): Section
    {
        return new class($page::class) extends Section {
            public function __construct(string $pageClass)
            {
                $this->page = $pageClass;
            }

            protected string $name = 'test-section';
            protected string $layout = 'default';
            protected string $page = '';

            public function fields(): array
            {
                return [Field::text('title')];
            }

            public function default(): array
            {
                return ['title' => 'Default Title'];
            }
        };
    }

    private function makeRepeatableTestSection(Page $page): Section
    {
        return new class($page::class) extends Section implements Repeatable {
            public function __construct(string $pageClass)
            {
                $this->page = $pageClass;
            }

            protected string $name = 'test-repeatable';
            protected string $layout = 'default';
            protected string $page = '';

            public function fields(): array
            {
                return [Field::text('label')];
            }
        };
    }
}

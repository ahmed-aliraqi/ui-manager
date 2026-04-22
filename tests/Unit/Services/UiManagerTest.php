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
use AhmedAliraqi\UiManager\Variables\VariableRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class UiManagerTest extends TestCase
{
    private UiManager $manager;
    private SectionRegistry $sections;
    private PageRegistry $pages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pages    = $this->app->make(PageRegistry::class);
        $this->sections = $this->app->make(SectionRegistry::class);
        $this->manager  = $this->app->make(UiManager::class);
    }

    public function test_section_returns_section_view_for_non_repeatable(): void
    {
        [$page, $section] = $this->makePageAndSection();
        $this->pages->register($page);
        $this->sections->register($section);

        $this->assertInstanceOf(SectionView::class, $this->manager->section('test-section'));
    }

    public function test_section_returns_repeatable_view_for_repeatable(): void
    {
        [$page, $section] = $this->makePageAndRepeatableSection();
        $this->pages->register($page);
        $this->sections->register($section);

        $this->assertInstanceOf(RepeatableSectionView::class, $this->manager->section('test-repeatable'));
    }

    public function test_section_falls_back_to_defaults(): void
    {
        [$page, $section] = $this->makePageAndSection();
        $this->pages->register($page);
        $this->sections->register($section);

        $value = $this->manager->section('test-section')->field('title')->getString();

        $this->assertSame('Default Title', $value);
    }

    public function test_section_uses_db_value_over_default(): void
    {
        [$page, $section] = $this->makePageAndSection();
        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create([
            'layout'  => 'default',
            'page'    => 'test-page',
            'section' => 'test-section',
            'fields'  => ['title' => 'DB Value'],
        ]);

        $value = $this->manager->section('test-section')->field('title')->getString();

        $this->assertSame('DB Value', $value);
    }

    public function test_db_value_is_returned_after_save_when_caching_enabled(): void
    {
        // Re-enable caching for this test to verify the cache-key fix
        config(['ui-manager.cache.enabled' => true]);

        [$page, $section] = $this->makePageAndSection();
        $this->pages->register($page);
        $this->sections->register($section);

        // First read — caches empty data, defaults are shown
        $before = $this->manager->section('test-section')->field('title')->getString();
        $this->assertSame('Default Title', $before);

        // Simulate a save: write to DB and flush cache by page NAME (as the controller does)
        UiContent::create([
            'layout'  => 'default',
            'page'    => 'test-page',
            'section' => 'test-section',
            'fields'  => ['title' => 'Updated Title'],
        ]);
        $this->manager->flushCache('test-page', 'test-section');

        // After flush the DB value must be returned — not the stale cached default
        $after = $this->manager->section('test-section')->field('title')->getString();
        $this->assertSame('Updated Title', $after);
    }

    public function test_repeatable_section_is_iterable(): void
    {
        [$page, $section] = $this->makePageAndRepeatableSection();
        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create(['layout' => 'default', 'page' => 'test-page', 'section' => 'test-repeatable', 'fields' => ['label' => 'Item One'], 'sort_order' => 0]);
        UiContent::create(['layout' => 'default', 'page' => 'test-page', 'section' => 'test-repeatable', 'fields' => ['label' => 'Item Two'], 'sort_order' => 1]);

        $view = $this->manager->section('test-repeatable');

        $this->assertCount(2, $view);

        $labels = [];
        foreach ($view as $item) {
            $labels[] = $item->field('label')->getString();
        }

        $this->assertSame(['Item One', 'Item Two'], $labels);
    }

    public function test_repeatable_section_falls_back_to_section_defaults(): void
    {
        $page = new class extends Page {
            protected string $name = 'test-page';
        };

        $section = new class($page::class) extends Section implements Repeatable {
            public function __construct(string $pageClass) { $this->page = $pageClass; }
            protected string $name    = 'test-repeatable';
            protected string $layout  = 'default';
            protected string $page    = '';

            public function fields(): array { return [Field::text('label')]; }

            public function default(): array
            {
                return [
                    ['label' => 'Default A'],
                    ['label' => 'Default B'],
                ];
            }
        };

        $this->pages->register($page);
        $this->sections->register($section);

        $view   = $this->manager->section('test-repeatable');
        $labels = array_map(fn ($item) => $item->field('label')->getString(), iterator_to_array($view));

        $this->assertSame(['Default A', 'Default B'], $labels);
    }

    public function test_section_or_null_returns_null_for_unknown(): void
    {
        $this->assertNull($this->manager->sectionOrNull('does-not-exist'));
    }

    public function test_flush_cache_does_not_throw(): void
    {
        $this->manager->flushCache('test-page', 'test-section');
        $this->assertTrue(true);
    }

    // ------------------------------------------------------------------ repeatable: no variables

    public function test_repeatable_item_field_does_not_parse_variables(): void
    {
        app(VariableRegistry::class)->value('site.name', 'My Site');

        [$page, $section] = $this->makePageAndRepeatableSection();
        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create([
            'layout'     => 'default',
            'page'       => 'test-page',
            'section'    => 'test-repeatable',
            'fields'     => ['label' => 'Visit %site.name%'],
            'sort_order' => 0,
        ]);

        $view  = $this->manager->section('test-repeatable');
        $label = $view->first()->field('label')->getString();

        // %site.name% must NOT be replaced inside repeatable items
        $this->assertSame('Visit %site.name%', $label);
    }

    // ------------------------------------------------------------------ image defaults

    public function test_image_field_with_url_string_default_returns_url(): void
    {
        $page = new class extends Page { protected string $name = 'test-page'; };

        $section = new class($page::class) extends Section {
            public function __construct(string $pageClass) { $this->page = $pageClass; }
            protected string $name   = 'img-section';
            protected string $layout = 'default';
            protected string $page   = '';

            public function fields(): array
            {
                return [Field::image('logo')->default('https://example.com/logo.png')];
            }
        };

        $this->pages->register($page);
        $this->sections->register($section);

        $url = $this->manager->section('img-section')->field('logo')->getUrl();

        $this->assertSame('https://example.com/logo.png', $url);
    }

    public function test_image_field_get_string_returns_empty_not_array_cast(): void
    {
        $page = new class extends Page { protected string $name = 'test-page'; };

        $section = new class($page::class) extends Section {
            public function __construct(string $pageClass) { $this->page = $pageClass; }
            protected string $name   = 'media-section';
            protected string $layout = 'default';
            protected string $page   = '';

            public function fields(): array { return [Field::image('photo')]; }
        };

        $this->pages->register($page);
        $this->sections->register($section);

        UiContent::create([
            'layout'  => 'default',
            'page'    => 'test-page',
            'section' => 'media-section',
            'fields'  => ['photo' => ['id' => 99, 'url' => '/photo.jpg', 'filename' => 'photo.jpg']],
        ]);

        // getString() on a media field must return '' — never 'Array'
        $str = $this->manager->section('media-section')->field('photo')->getString();

        $this->assertSame('', $str);
    }

    // ------------------------------------------------------------------

    private function makePageAndSection(): array
    {
        $page = new class extends Page {
            protected string $name = 'test-page';
        };

        $section = new class($page::class) extends Section {
            public function __construct(string $pageClass) { $this->page = $pageClass; }
            protected string $name   = 'test-section';
            protected string $layout = 'default';
            protected string $page   = '';

            public function fields(): array { return [Field::text('title')]; }
            public function default(): array { return ['title' => 'Default Title']; }
        };

        return [$page, $section];
    }

    private function makePageAndRepeatableSection(): array
    {
        $page = new class extends Page {
            protected string $name = 'test-page';
        };

        $section = new class($page::class) extends Section implements Repeatable {
            public function __construct(string $pageClass) { $this->page = $pageClass; }
            protected string $name   = 'test-repeatable';
            protected string $layout = 'default';
            protected string $page   = '';

            public function fields(): array { return [Field::text('label')]; }
        };

        return [$page, $section];
    }
}

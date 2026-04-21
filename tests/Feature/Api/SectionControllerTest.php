<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Contracts\Repeatable;
use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class SectionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $page = new class extends Page {
            protected string $name = 'home';
        };

        $section = new class extends Section {
            protected string $name = 'banner';
            protected string $layout = 'default';
            protected string $page = 'home';

            public function fields(): array
            {
                return [
                    Field::text('title')->default('Default Banner'),
                    Field::text('subtitle'),
                ];
            }
        };

        $repeatableSection = new class extends Section implements Repeatable {
            protected string $name = 'links';
            protected string $layout = 'default';
            protected string $page = 'home';

            public function fields(): array
            {
                return [
                    Field::text('label'),
                    Field::text('url'),
                ];
            }
        };

        $pageRegistry = $this->app->make(PageRegistry::class);
        $sectionRegistry = $this->app->make(SectionRegistry::class);

        $pageRegistry->register($page);
        $sectionRegistry->register($section);
        $sectionRegistry->register($repeatableSection);
    }

    public function test_show_returns_defaults_when_no_db_record(): void
    {
        $this->getJson($this->apiUrl('pages/home/sections/banner'))
            ->assertOk()
            ->assertJsonPath('data.fields.title', 'Default Banner')
            ->assertJsonPath('data.repeatable', false);
    }

    public function test_update_saves_section_fields(): void
    {
        $this->putJson($this->apiUrl('pages/home/sections/banner'), [
            'fields' => ['title' => 'New Title', 'subtitle' => 'Sub'],
        ])->assertOk();

        $this->assertDatabaseHas('ui_contents', [
            'page'    => 'home',
            'section' => 'banner',
        ]);

        $content = UiContent::where('page', 'home')->where('section', 'banner')->first();
        $this->assertSame('New Title', $content->fields['title']);
    }

    public function test_update_only_saves_declared_fields(): void
    {
        $this->putJson($this->apiUrl('pages/home/sections/banner'), [
            'fields' => [
                'title'        => 'My Title',
                'undeclared'   => 'should be ignored',
            ],
        ])->assertOk();

        $content = UiContent::where('page', 'home')->where('section', 'banner')->first();
        $this->assertArrayNotHasKey('undeclared', $content->fields ?? []);
    }

    public function test_show_includes_stored_value(): void
    {
        UiContent::create([
            'layout'  => 'default',
            'page'    => 'home',
            'section' => 'banner',
            'fields'  => ['title' => 'Stored Title'],
        ]);

        $this->getJson($this->apiUrl('pages/home/sections/banner'))
            ->assertOk()
            ->assertJsonPath('data.fields.title', 'Stored Title');
    }

    public function test_repeatable_section_crud(): void
    {
        // Add items
        $this->postJson($this->apiUrl('pages/home/sections/links/items'), [
            'fields' => ['label' => 'Github', 'url' => 'https://github.com'],
        ])->assertCreated();

        $this->postJson($this->apiUrl('pages/home/sections/links/items'), [
            'fields' => ['label' => 'Twitter', 'url' => 'https://twitter.com'],
        ])->assertCreated();

        $this->assertDatabaseCount('ui_contents', 2);

        // List items
        $response = $this->getJson($this->apiUrl('pages/home/sections/links'));
        $response->assertOk()
            ->assertJsonPath('data.repeatable', true)
            ->assertJsonCount(2, 'data.items');

        // Update item
        $itemId = UiContent::first()->id;
        $this->putJson($this->apiUrl("pages/home/sections/links/items/{$itemId}"), [
            'fields' => ['label' => 'GitHub Updated', 'url' => 'https://github.com'],
        ])->assertOk();

        $this->assertDatabaseHas('ui_contents', ['id' => $itemId]);
        $item = UiContent::find($itemId);
        $this->assertSame('GitHub Updated', $item->fields['label']);

        // Delete item
        $this->deleteJson($this->apiUrl("pages/home/sections/links/items/{$itemId}"))
            ->assertNoContent();

        $this->assertDatabaseMissing('ui_contents', ['id' => $itemId]);
    }

    public function test_reorder_items(): void
    {
        $item1 = UiContent::create([
            'layout' => 'default', 'page' => 'home', 'section' => 'links',
            'fields' => ['label' => 'A'], 'sort_order' => 0,
        ]);
        $item2 = UiContent::create([
            'layout' => 'default', 'page' => 'home', 'section' => 'links',
            'fields' => ['label' => 'B'], 'sort_order' => 1,
        ]);

        $this->postJson($this->apiUrl('pages/home/sections/links/reorder'), [
            'order' => [$item2->id, $item1->id],
        ])->assertOk();

        $this->assertSame(0, UiContent::find($item2->id)->sort_order);
        $this->assertSame(1, UiContent::find($item1->id)->sort_order);
    }

    private function apiUrl(string $path): string
    {
        $prefix = config('ui-manager.routes.api_prefix', 'ui-manager/api');

        return "/{$prefix}/{$path}";
    }
}

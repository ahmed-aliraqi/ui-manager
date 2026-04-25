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

final class ReorderValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $page = new class extends Page {
            protected string $name = 'home';
        };

        $repeatable = new class extends Section implements Repeatable {
            protected string $name   = 'links';
            protected string $layout = 'default';
            protected string $page   = 'home';

            public function fields(): array
            {
                return [Field::text('label')];
            }
        };

        $otherRepeatable = new class extends Section implements Repeatable {
            protected string $name   = 'faq';
            protected string $layout = 'default';
            protected string $page   = 'home';

            public function fields(): array
            {
                return [Field::text('question')];
            }
        };

        $pageRegistry    = $this->app->make(PageRegistry::class);
        $sectionRegistry = $this->app->make(SectionRegistry::class);

        $pageRegistry->register($page);
        $sectionRegistry->register($repeatable);
        $sectionRegistry->register($otherRepeatable);
    }

    public function test_reorder_rejects_ids_from_another_section(): void
    {
        $item1 = UiContent::create([
            'layout' => 'default', 'page' => 'home', 'section' => 'links',
            'fields' => ['label' => 'A'], 'sort_order' => 0,
        ]);

        $otherItem = UiContent::create([
            'layout' => 'default', 'page' => 'home', 'section' => 'faq',
            'fields' => ['question' => 'Q?'], 'sort_order' => 0,
        ]);

        $this->postJson($this->apiUrl('pages/home/sections/links/reorder'), [
            'order' => [$item1->id, $otherItem->id],
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Some IDs do not belong to this section.')
            ->assertJsonFragment(['invalid' => [$otherItem->id]]);
    }

    public function test_reorder_rejects_completely_foreign_ids(): void
    {
        $this->postJson($this->apiUrl('pages/home/sections/links/reorder'), [
            'order' => [9999, 8888],
        ])->assertStatus(422);
    }

    public function test_reorder_accepts_valid_section_ids(): void
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

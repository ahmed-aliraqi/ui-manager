<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class PageControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $page = new class extends Page {
            protected string $name = 'home';
            public function getDisplayName(): string { return 'Home'; }
        };

        $section = new class extends Section {
            protected string $name = 'banner';
            protected string $layout = 'default';
            protected string $page = 'home';

            public function fields(): array
            {
                return [Field::text('title')];
            }
        };

        $this->app->make(PageRegistry::class)->register($page);
        $this->app->make(SectionRegistry::class)->register($section);
    }

    public function test_pages_index_returns_all_pages(): void
    {
        $response = $this->getJson($this->apiUrl('pages'));

        $response->assertOk()
            ->assertJsonStructure(['data' => [['name', 'display_name', 'sections']]])
            ->assertJsonPath('data.0.name', 'home');
    }

    public function test_pages_show_returns_page_with_sections(): void
    {
        $response = $this->getJson($this->apiUrl('pages/home'));

        $response->assertOk()
            ->assertJsonPath('data.name', 'home')
            ->assertJsonPath('data.display_name', 'Home')
            ->assertJsonStructure(['data' => ['sections']]);
    }

    public function test_pages_show_returns_404_for_unknown_page(): void
    {
        $this->getJson($this->apiUrl('pages/unknown'))->assertNotFound();
    }

    public function test_sections_are_included_in_page_response(): void
    {
        $response = $this->getJson($this->apiUrl('pages/home'));

        $response->assertOk()
            ->assertJsonPath('data.sections.0.name', 'banner');
    }

    private function apiUrl(string $path): string
    {
        $prefix = config('ui-manager.routes.api_prefix', 'ui-manager/api');

        return "/{$prefix}/{$path}";
    }
}

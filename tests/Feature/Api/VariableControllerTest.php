<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;
use AhmedAliraqi\UiManager\Variables\VariableRegistry;

final class VariableControllerTest extends TestCase
{
    private string $apiPrefix;

    protected function setUp(): void
    {
        parent::setUp();
        $this->apiPrefix = config('ui-manager.routes.api_prefix', 'ui-manager/api');
    }

    public function test_returns_manually_registered_variables(): void
    {
        app(VariableRegistry::class)->value('site.name', 'My Site');
        app(VariableRegistry::class)->value('site.phone', '+1234567890');

        $this->getJson("/{$this->apiPrefix}/variables")
            ->assertOk()
            ->assertJsonFragment(['key' => 'site.name', 'placeholder' => '%site.name%', 'source' => 'registry'])
            ->assertJsonFragment(['key' => 'site.phone', 'placeholder' => '%site.phone%', 'source' => 'registry']);
    }

    public function test_returns_section_field_variables_for_has_variable_fields(): void
    {
        $page = new class extends Page { protected string $name = 'general'; };

        $section = new class extends Section {
            protected string $name   = 'header';
            protected string $page   = 'general';
            protected string $layout = 'default';

            public function fields(): array
            {
                return [
                    Field::text('app_name')->hasVariable(),
                    Field::text('tagline'),
                    Field::image('logo')->hasVariable(),
                ];
            }
        };

        app(PageRegistry::class)->register($page);
        app(SectionRegistry::class)->register($section);

        $response = $this->getJson("/{$this->apiPrefix}/variables")->assertOk();
        $data     = collect($response->json('data'));

        // app_name has hasVariable → appears
        $this->assertTrue($data->contains('placeholder', '%header.app_name%'));

        // tagline has no hasVariable → must not appear
        $this->assertFalse($data->contains('placeholder', '%header.tagline%'));

        // image field exposes :url and :name formats
        $this->assertTrue($data->contains('placeholder', '%header.logo:url%'));
        $this->assertTrue($data->contains('placeholder', '%header.logo:name%'));

        // label is the field's human-readable label
        $appNameEntry = $data->firstWhere('placeholder', '%header.app_name%');
        $this->assertSame('section', $appNameEntry['source']);
        $this->assertSame('App Name', $appNameEntry['label']);
    }

    public function test_deduplicates_registry_and_section_entries(): void
    {
        app(VariableRegistry::class)->value('header.app_name', 'My App');

        $page = new class extends Page { protected string $name = 'general'; };

        $section = new class extends Section {
            protected string $name   = 'header';
            protected string $page   = 'general';
            protected string $layout = 'default';

            public function fields(): array
            {
                return [Field::text('app_name')->hasVariable()];
            }
        };

        app(PageRegistry::class)->register($page);
        app(SectionRegistry::class)->register($section);

        $response = $this->getJson("/{$this->apiPrefix}/variables")->assertOk();
        $data     = $response->json('data');

        $matches = array_filter($data, fn ($v) => $v['placeholder'] === '%header.app_name%');

        $this->assertCount(1, $matches, 'Duplicate placeholder should appear only once');
        $this->assertSame('registry', array_values($matches)[0]['source'], 'Registry takes precedence');
    }
}

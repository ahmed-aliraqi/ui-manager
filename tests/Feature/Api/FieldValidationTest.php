<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class FieldValidationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('ui-manager.locales', ['en', 'ar']);

        $page = new class extends Page {
            protected string $name = 'home';
        };

        $section = new class extends Section {
            protected string $name   = 'contact';
            protected string $layout = 'default';
            protected string $page   = 'home';

            public function fields(): array
            {
                return [
                    Field::text('heading')->rules(['required', 'max:100']),
                    Field::text('email')->rules(['required', 'email']),
                    Field::textarea('bio'),
                    Field::text('title')->translatable()->rules(['required']),
                ];
            }
        };

        $pageRegistry    = $this->app->make(PageRegistry::class);
        $sectionRegistry = $this->app->make(SectionRegistry::class);

        $pageRegistry->register($page);
        $sectionRegistry->register($section);
    }

    public function test_required_field_missing_returns_422(): void
    {
        $response = $this->putJson('/ui-manager/api/pages/home/sections/contact', [
            'fields' => [
                'heading' => '',
                'email'   => '',
                'bio'     => 'Hello',
                'title'   => ['en' => '', 'ar' => ''],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fields.heading', 'fields.email']);
    }

    public function test_invalid_email_field_returns_422(): void
    {
        $response = $this->putJson('/ui-manager/api/pages/home/sections/contact', [
            'fields' => [
                'heading' => 'Hello',
                'email'   => 'not-an-email',
                'bio'     => '',
                'title'   => ['en' => 'Hi', 'ar' => 'مرحبا'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fields.email']);
        $response->assertJsonMissingValidationErrors(['fields.heading']);
    }

    public function test_valid_data_passes_validation(): void
    {
        $response = $this->putJson('/ui-manager/api/pages/home/sections/contact', [
            'fields' => [
                'heading' => 'Contact Us',
                'email'   => 'hello@example.com',
                'bio'     => 'Some text',
                'title'   => ['en' => 'Contact', 'ar' => 'تواصل'],
            ],
        ]);

        $response->assertStatus(200);
    }

    public function test_translatable_required_field_validates_per_locale(): void
    {
        $response = $this->putJson('/ui-manager/api/pages/home/sections/contact', [
            'fields' => [
                'heading' => 'Contact',
                'email'   => 'a@b.com',
                'bio'     => '',
                'title'   => ['en' => '', 'ar' => 'تواصل'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['fields.title.en']);
        $response->assertJsonMissingValidationErrors(['fields.title.ar']);
    }
}

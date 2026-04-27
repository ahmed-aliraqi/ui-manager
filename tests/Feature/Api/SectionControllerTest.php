<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Feature\Api;

use AhmedAliraqi\UiManager\Contracts\Repeatable;
use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Models\UiContent;
use AhmedAliraqi\UiManager\Models\UiMediaFile;
use AhmedAliraqi\UiManager\Services\PageRegistry;
use AhmedAliraqi\UiManager\Services\SectionRegistry;
use AhmedAliraqi\UiManager\Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class SectionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $page = new class extends Page {
            protected string $name = 'home';
        };

        $section = new class extends Section {
            protected string $name    = 'banner';
            protected string $layout  = 'default';
            protected string $page    = 'home';

            public function fields(): array
            {
                return [
                    Field::text('title')->default('Default Banner'),
                    Field::text('subtitle'),
                ];
            }
        };

        $repeatableSection = new class extends Section implements Repeatable {
            protected string $name    = 'links';
            protected string $layout  = 'default';
            protected string $page    = 'home';

            public function fields(): array
            {
                return [
                    Field::text('label'),
                    Field::text('url'),
                ];
            }
        };

        $imageSection = new class extends Section {
            protected string $name    = 'hero';
            protected string $layout  = 'default';
            protected string $page    = 'home';

            public function fields(): array
            {
                return [
                    Field::text('title')->default('Hero'),
                    Field::image('image'),
                ];
            }
        };

        $pageRegistry    = $this->app->make(PageRegistry::class);
        $sectionRegistry = $this->app->make(SectionRegistry::class);

        $pageRegistry->register($page);
        $sectionRegistry->register($section);
        $sectionRegistry->register($repeatableSection);
        $sectionRegistry->register($imageSection);
    }

    // ------------------------------------------------------------------ show

    public function test_show_returns_defaults_when_no_db_record(): void
    {
        $this->getJson($this->apiUrl('pages/home/sections/banner'))
            ->assertOk()
            ->assertJsonPath('data.fields.title', 'Default Banner')
            ->assertJsonPath('data.repeatable', false);
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

    // ------------------------------------------------------------------ update

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

    public function test_update_persists_all_declared_fields_including_defaults(): void
    {
        // Submit only the title; subtitle should be persisted with its default (null)
        $this->putJson($this->apiUrl('pages/home/sections/banner'), [
            'fields' => ['title' => 'Only Title'],
        ])->assertOk();

        $content = UiContent::where('page', 'home')->where('section', 'banner')->first();
        $this->assertSame('Only Title', $content->fields['title']);
        $this->assertArrayHasKey('subtitle', $content->fields);
    }

    public function test_update_ignores_undeclared_fields(): void
    {
        $this->putJson($this->apiUrl('pages/home/sections/banner'), [
            'fields' => [
                'title'      => 'My Title',
                'undeclared' => 'should be ignored',
            ],
        ])->assertOk();

        $content = UiContent::where('page', 'home')->where('section', 'banner')->first();
        $this->assertArrayNotHasKey('undeclared', $content->fields ?? []);
    }

    // ------------------------------------------------------------------ repeatable

    public function test_repeatable_section_returns_default_items_when_db_is_empty(): void
    {
        $sectionWithDefaults = new class extends Section implements Repeatable {
            protected string $name    = 'social';
            protected string $layout  = 'default';
            protected string $page    = 'home';

            public function fields(): array
            {
                return [Field::text('label'), Field::text('url')];
            }

            public function default(): array
            {
                return [
                    ['label' => 'Facebook', 'url' => 'https://facebook.com'],
                    ['label' => 'Twitter',  'url' => 'https://twitter.com'],
                ];
            }
        };

        $this->app->make(SectionRegistry::class)->register($sectionWithDefaults);

        $response = $this->getJson($this->apiUrl('pages/home/sections/social'));

        $response->assertOk()
            ->assertJsonPath('data.repeatable', true)
            ->assertJsonCount(2, 'data.items')
            ->assertJsonPath('data.items.0.fields.label', 'Facebook')
            ->assertJsonPath('data.items.1.fields.label', 'Twitter')
            ->assertJsonPath('data.items.0.id', null)
            ->assertJsonPath('data.items.0.sort_order', 0)
            ->assertJsonPath('data.items.1.sort_order', 1);
    }

    public function test_repeatable_section_db_items_take_precedence_over_defaults(): void
    {
        $sectionWithDefaults = new class extends Section implements Repeatable {
            protected string $name    = 'social';
            protected string $layout  = 'default';
            protected string $page    = 'home';

            public function fields(): array
            {
                return [Field::text('label')];
            }

            public function default(): array
            {
                return [['label' => 'Default']];
            }
        };

        $this->app->make(SectionRegistry::class)->register($sectionWithDefaults);

        UiContent::create([
            'layout'     => 'default',
            'page'       => 'home',
            'section'    => 'social',
            'fields'     => ['label' => 'DB Item'],
            'sort_order' => 0,
        ]);

        $response = $this->getJson($this->apiUrl('pages/home/sections/social'));

        $response->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.fields.label', 'DB Item');
    }

    public function test_repeatable_section_crud(): void
    {
        $this->postJson($this->apiUrl('pages/home/sections/links/items'), [
            'fields' => ['label' => 'Github', 'url' => 'https://github.com'],
        ])->assertCreated();

        $this->postJson($this->apiUrl('pages/home/sections/links/items'), [
            'fields' => ['label' => 'Twitter', 'url' => 'https://twitter.com'],
        ])->assertCreated();

        $this->assertDatabaseCount('ui_contents', 2);

        $response = $this->getJson($this->apiUrl('pages/home/sections/links'));
        $response->assertOk()
            ->assertJsonPath('data.repeatable', true)
            ->assertJsonCount(2, 'data.items');

        $itemId = UiContent::first()->id;
        $this->putJson($this->apiUrl("pages/home/sections/links/items/{$itemId}"), [
            'fields' => ['label' => 'GitHub Updated', 'url' => 'https://github.com'],
        ])->assertOk();

        $this->assertSame('GitHub Updated', UiContent::find($itemId)->fields['label']);

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

    // ------------------------------------------------------------------ media

    public function test_media_upload_returns_url_and_id(): void
    {
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->postJson($this->apiUrl('media'), [
            'file' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'url', 'filename', 'mime', 'size']]);
    }

    public function test_replacing_image_via_existing_media_id_deletes_old_file(): void
    {
        $owner = UiMediaFile::create();
        $oldMedia = $owner->addMedia(UploadedFile::fake()->image('old.jpg'))
            ->usingFileName('old.jpg')
            ->toMediaCollection('images');

        $oldId = $oldMedia->id;

        $newFile = UploadedFile::fake()->image('new.jpg');

        $response = $this->postJson($this->apiUrl('media'), [
            'file'               => $newFile,
            'existing_media_id'  => $oldId,
        ]);

        $response->assertCreated();

        // Old media record should be gone (singleFile replaced it)
        $this->assertNull(Media::find($oldId));
    }

    public function test_image_field_media_is_deleted_when_section_image_is_replaced(): void
    {
        // Create initial media
        $owner    = UiMediaFile::create();
        $oldMedia = $owner->addMedia(UploadedFile::fake()->image('old.jpg'))
            ->usingFileName('old.jpg')
            ->toMediaCollection('images');

        // Save hero section with that image
        UiContent::create([
            'layout'  => 'default',
            'page'    => 'home',
            'section' => 'hero',
            'fields'  => ['title' => 'Hero', 'image' => ['id' => $oldMedia->id, 'url' => '/old.jpg', 'filename' => 'old.jpg']],
        ]);

        // Upload replacement image first
        $newOwner    = UiMediaFile::create();
        $newMedia    = $newOwner->addMedia(UploadedFile::fake()->image('new.jpg'))
            ->usingFileName('new.jpg')
            ->toMediaCollection('images');

        // Save section with new image
        $this->putJson($this->apiUrl('pages/home/sections/hero'), [
            'fields' => [
                'title' => 'Hero',
                'image' => ['id' => $newMedia->id, 'url' => '/new.jpg', 'filename' => 'new.jpg'],
            ],
        ])->assertOk();

        // Old media should be deleted
        $this->assertNull(Media::find($oldMedia->id));
        // New media should still exist
        $this->assertNotNull(Media::find($newMedia->id));
    }

    public function test_deleting_repeatable_item_removes_its_media(): void
    {
        $owner = UiMediaFile::create();
        $media = $owner->addMedia(UploadedFile::fake()->image('photo.jpg'))
            ->usingFileName('photo.jpg')
            ->toMediaCollection('images');

        // Register a repeatable section with an image field for this test
        $imageRepeatable = new class extends Section implements Repeatable {
            protected string $name   = 'gallery';
            protected string $layout = 'default';
            protected string $page   = 'home';

            public function fields(): array
            {
                return [Field::image('photo')];
            }
        };

        $this->app->make(SectionRegistry::class)->register($imageRepeatable);

        $item = UiContent::create([
            'layout'     => 'default',
            'page'       => 'home',
            'section'    => 'gallery',
            'fields'     => ['photo' => ['id' => $media->id, 'url' => '/photo.jpg', 'filename' => 'photo.jpg']],
            'sort_order' => 0,
        ]);

        $this->deleteJson($this->apiUrl("pages/home/sections/gallery/items/{$item->id}"))
            ->assertNoContent();

        $this->assertNull(Media::find($media->id));
    }

    // ------------------------------------------------------------------

    private function apiUrl(string $path): string
    {
        $prefix = config('ui-manager.routes.api_prefix', 'ui-manager/api');

        return "/{$prefix}/{$path}";
    }
}

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Core;

use AhmedAliraqi\UiManager\Contracts\Repeatable;
use AhmedAliraqi\UiManager\Core\Page;
use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class SectionTest extends TestCase
{
    public function test_section_resolves_defaults_from_fields_and_default_method(): void
    {
        $section = new class extends Section {
            protected string $name    = 'banner';
            protected string $layout  = 'default';
            protected string $page    = 'App\\Ui\\Pages\\Home';

            public function fields(): array
            {
                return [
                    Field::text('title')->default('Field Default'),
                    Field::text('subtitle')->default('Subtitle Default'),
                ];
            }

            public function default(): array
            {
                return ['title' => 'Method Default'];  // overrides field default
            }
        };

        $defaults = $section->resolveDefaults();

        $this->assertSame('Method Default', $defaults['title']); // method wins
        $this->assertSame('Subtitle Default', $defaults['subtitle']);
    }

    public function test_section_is_repeatable_detection(): void
    {
        $plain = new class extends Section {
            protected string $name = 'plain';
            protected string $layout = 'default';
            protected string $page = 'X';
            public function fields(): array { return []; }
        };

        $repeatable = new class extends Section implements Repeatable {
            protected string $name = 'rep';
            protected string $layout = 'default';
            protected string $page = 'X';
            public function fields(): array { return []; }
        };

        $this->assertFalse($plain->isRepeatable());
        $this->assertTrue($repeatable->isRepeatable());
    }

    public function test_section_to_array_structure(): void
    {
        $section = new class extends Section {
            protected string $name = 'hero';
            protected string $layout = 'default';
            protected string $page = 'App\\Ui\\Pages\\Home';
            public function fields(): array
            {
                return [Field::text('title')];
            }
        };

        $array = $section->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('layout', $array);
        $this->assertArrayHasKey('page', $array);
        $this->assertArrayHasKey('fields', $array);
        $this->assertArrayHasKey('repeatable', $array);
        $this->assertCount(1, $array['fields']);
    }

    public function test_page_to_array_structure(): void
    {
        $page = new class extends Page {
            protected string $name = 'about';
            public function getDisplayName(): string { return 'About Us'; }
        };

        $array = $page->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('display_name', $array);
        $this->assertArrayHasKey('visible', $array);
        $this->assertSame('about', $array['name']);
        $this->assertSame('About Us', $array['display_name']);
    }
}

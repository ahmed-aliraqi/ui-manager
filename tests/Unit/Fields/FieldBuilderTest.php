<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Fields;

use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;
use AhmedAliraqi\UiManager\Fields\SelectField;
use AhmedAliraqi\UiManager\Fields\TextField;
use AhmedAliraqi\UiManager\Fields\EditorField;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class FieldBuilderTest extends TestCase
{
    public function test_text_field_creation(): void
    {
        $field = Field::text('title');

        $this->assertInstanceOf(TextField::class, $field);
        $this->assertSame('title', $field->getName());
        $this->assertSame('text', $field->getType());
        $this->assertSame('Title', $field->getLabel());
    }

    public function test_text_field_fluent_builder(): void
    {
        $field = Field::text('app_name')
            ->label('Application Name')
            ->help('Used throughout the site')
            ->rules(['required', 'string', 'max:100'])
            ->default('My App');

        $this->assertSame('Application Name', $field->getLabel());
        $this->assertSame('Used throughout the site', $field->getHelpText());
        $this->assertSame(['required', 'string', 'max:100'], $field->getRules());
        $this->assertSame('My App', $field->getDefault());
    }

    public function test_textarea_field_is_multiline(): void
    {
        $field = Field::textarea('body');

        $this->assertSame('textarea', $field->getType());
        $this->assertTrue($field->toArray()['multiline']);
    }

    public function test_editor_field(): void
    {
        $field = Field::editor('description');

        $this->assertInstanceOf(EditorField::class, $field);
        $this->assertSame('editor', $field->getType());
    }

    public function test_select_field_options(): void
    {
        $field = Field::select('status')
            ->options(['draft' => 'Draft', 'published' => 'Published'])
            ->multiple()
            ->searchable();

        $this->assertInstanceOf(SelectField::class, $field);
        $this->assertSame('select', $field->getType());

        $array = $field->toArray();
        $this->assertCount(2, $array['options']);
        $this->assertSame('draft', $array['options'][0]['value']);
        $this->assertTrue($array['multiple']);
        $this->assertTrue($array['searchable']);
    }

    public function test_image_field(): void
    {
        $field = Field::image('cover')
            ->accept(['image/jpeg', 'image/png'])
            ->maxSize(2048)
            ->dimensions(800, 600);

        $this->assertInstanceOf(ImageField::class, $field);
        $this->assertSame('image', $field->getType());

        $array = $field->toArray();
        $this->assertSame(['image/jpeg', 'image/png'], $array['accept']);
        $this->assertSame(2048, $array['max_size']);
        $this->assertSame(800, $array['width']);
        $this->assertSame(600, $array['height']);
    }

    public function test_file_field(): void
    {
        $field = Field::file('resume')
            ->accept(['application/pdf'])
            ->multiple(false);

        $this->assertInstanceOf(FileField::class, $field);
        $this->assertSame('file', $field->getType());
        $this->assertSame(['application/pdf'], $field->toArray()['accept']);
    }

    public function test_required_method_prepends_rule(): void
    {
        $field = Field::text('name')
            ->rules(['string'])
            ->required();

        $this->assertSame('required', $field->getRules()[0]);
    }

    public function test_nullable_method_appends_rule(): void
    {
        $field = Field::text('name')->nullable();

        $this->assertContains('nullable', $field->getRules());
    }

    public function test_variable_key_generation(): void
    {
        $field = Field::text('title');

        $this->assertSame('banner.title', $field->getVariableKey('banner'));
    }

    public function test_to_array_contains_expected_keys(): void
    {
        $field = Field::text('title')
            ->label('Title')
            ->help('The main title')
            ->default('Hello World');

        $array = $field->toArray();

        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('type', $array);
        $this->assertArrayHasKey('label', $array);
        $this->assertArrayHasKey('help', $array);
        $this->assertArrayHasKey('default', $array);
    }

    public function test_label_auto_generated_from_name(): void
    {
        $this->assertSame('App Name', Field::text('app_name')->getLabel());
        $this->assertSame('My Field', Field::text('my-field')->getLabel());
    }
}

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Fields;

use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class TranslatableFieldTest extends TestCase
{
    // ---------------------------------------------------------- BaseField API

    public function test_field_is_not_translatable_by_default(): void
    {
        $field = Field::text('title');

        $this->assertFalse($field->isTranslatable());
    }

    public function test_translatable_marks_field(): void
    {
        $field = Field::text('title')->translatable();

        $this->assertTrue($field->isTranslatable());
    }

    public function test_toArray_includes_translatable_key_when_true(): void
    {
        $field = Field::text('title')->translatable();

        $this->assertTrue($field->toArray()['translatable']);
    }

    public function test_toArray_omits_translatable_key_when_false(): void
    {
        $field = Field::text('title');

        $this->assertArrayNotHasKey('translatable', $field->toArray());
    }

    // ---------------------------------------------------------- Locale resolution

    public function test_returns_value_for_current_locale(): void
    {
        app()->setLocale('en');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', ['en' => 'Hello', 'ar' => 'مرحبا'], $field);

        $this->assertSame('Hello', $value->getString());
    }

    public function test_returns_value_for_arabic_locale(): void
    {
        app()->setLocale('ar');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', ['en' => 'Hello', 'ar' => 'مرحبا'], $field);

        $this->assertSame('مرحبا', $value->getString());
    }

    public function test_explicit_locale_parameter_overrides_app_locale(): void
    {
        app()->setLocale('en');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', ['en' => 'Hello', 'ar' => 'مرحبا'], $field, locale: 'ar');

        $this->assertSame('مرحبا', $value->getString());
    }

    public function test_falls_back_to_default_locale_when_requested_locale_missing(): void
    {
        config(['ui-manager.default_locale' => 'en']);
        app()->setLocale('fr');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', ['en' => 'Hello', 'ar' => 'مرحبا'], $field);

        $this->assertSame('Hello', $value->getString());
    }

    public function test_falls_back_to_first_available_when_default_locale_also_missing(): void
    {
        config(['ui-manager.default_locale' => 'en']);
        app()->setLocale('fr');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', ['ar' => 'مرحبا', 'es' => 'Hola'], $field);

        // Neither 'fr' nor 'en' exist → first value returned
        $this->assertSame('مرحبا', $value->getString());
    }

    public function test_returns_empty_string_when_no_value(): void
    {
        app()->setLocale('en');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', [], $field);

        $this->assertSame('', $value->getString());
    }

    public function test_handles_null_raw_value(): void
    {
        app()->setLocale('en');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', null, $field);

        $this->assertSame('', $value->getString());
    }

    public function test_handles_legacy_string_value_for_translatable_field(): void
    {
        // Old data stored as plain string — must not break.
        app()->setLocale('en');

        $field = Field::text('title')->translatable();
        $value = new FieldValueData('title', 'Legacy value', $field);

        $this->assertSame('Legacy value', $value->getString());
    }

    // ---------------------------------------------------------- SectionView locale suffix

    public function test_section_view_field_with_locale_suffix(): void
    {
        $section = new class extends \AhmedAliraqi\UiManager\Core\Section {
            protected string $name = 'banner';
            protected string $page = 'home';

            public function fields(): array
            {
                return [
                    Field::text('title')->translatable(),
                ];
            }
        };

        $view = new \AhmedAliraqi\UiManager\Support\SectionView(
            $section,
            ['title' => ['en' => 'Hello', 'ar' => 'مرحبا']],
        );

        $this->assertSame('مرحبا', $view->field('title:ar')->getString());
        $this->assertSame('Hello', $view->field('title:en')->getString());
    }

    // ---------------------------------------------------------- Non-translatable unchanged

    public function test_non_translatable_field_returns_value_as_is(): void
    {
        $field = Field::text('title');
        $value = new FieldValueData('title', 'Hello', $field);

        $this->assertSame('Hello', $value->getString());
    }

    public function test_multiple_field_types_support_translatable(): void
    {
        $textField     = Field::text('t')->translatable();
        $textareaField = Field::textarea('ta')->translatable();
        $editorField   = Field::editor('e')->translatable();

        foreach ([$textField, $textareaField, $editorField] as $field) {
            $this->assertTrue($field->isTranslatable());
        }
    }
}

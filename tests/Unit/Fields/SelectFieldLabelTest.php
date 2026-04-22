<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Fields;

use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class SelectFieldLabelTest extends TestCase
{
    private function makeField(): \AhmedAliraqi\UiManager\Fields\SelectField
    {
        return Field::select('status')->options([
            'draft'     => 'Draft',
            'published' => 'Published',
            'archived'  => 'Archived',
        ]);
    }

    // ---------------------------------------------------------- getString (key by default)

    public function test_get_string_returns_stored_key_by_default(): void
    {
        $value = new FieldValueData('status', 'published', $this->makeField());

        $this->assertSame('published', $value->getString());
    }

    public function test_get_string_returns_key_for_empty_value(): void
    {
        $value = new FieldValueData('status', '', $this->makeField());

        $this->assertSame('', $value->getString());
    }

    // ---------------------------------------------------------- label()

    public function test_label_returns_option_label(): void
    {
        $value = new FieldValueData('status', 'draft', $this->makeField());

        $this->assertSame('Draft', $value->label());
    }

    public function test_label_returns_published_label(): void
    {
        $value = new FieldValueData('status', 'published', $this->makeField());

        $this->assertSame('Published', $value->label());
    }

    public function test_label_returns_key_when_option_not_found(): void
    {
        $value = new FieldValueData('status', 'unknown_key', $this->makeField());

        $this->assertSame('unknown_key', $value->label());
    }

    public function test_label_returns_empty_string_for_non_select_field(): void
    {
        $value = new FieldValueData('title', 'hello', Field::text('title'));

        $this->assertSame('', $value->label());
    }

    // ---------------------------------------------------------- returnLabel()

    public function test_return_label_makes_get_string_return_label(): void
    {
        $field = $this->makeField()->returnLabel();
        $value = new FieldValueData('status', 'draft', $field);

        $this->assertSame('Draft', $value->getString());
    }

    public function test_return_label_is_false_by_default(): void
    {
        $this->assertFalse($this->makeField()->isReturnLabel());
    }

    public function test_return_label_is_true_after_calling_return_label(): void
    {
        $field = $this->makeField()->returnLabel();

        $this->assertTrue($field->isReturnLabel());
    }

    public function test_get_field_options_returns_options_map(): void
    {
        $field = $this->makeField();

        $this->assertSame([
            'draft'     => 'Draft',
            'published' => 'Published',
            'archived'  => 'Archived',
        ], $field->getFieldOptions());
    }

    // ---------------------------------------------------------- toArray

    public function test_to_array_includes_options_as_value_label_pairs(): void
    {
        $field = $this->makeField();
        $array = $field->toArray();

        $this->assertContains(['value' => 'draft', 'label' => 'Draft'], $array['options']);
        $this->assertContains(['value' => 'published', 'label' => 'Published'], $array['options']);
    }

    // ---------------------------------------------------------- backward compat

    public function test_select_value_still_accessible_via_get_string_without_return_label(): void
    {
        $value = new FieldValueData('status', 'archived', $this->makeField());

        $this->assertSame('archived', $value->getString());
        $this->assertSame('Archived', $value->label());
    }
}

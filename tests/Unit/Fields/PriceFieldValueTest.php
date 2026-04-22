<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Fields;

use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class PriceFieldValueTest extends TestCase
{
    public function test_amount_returns_float(): void
    {
        $field = Field::price('cost')->currency('USD');
        $value = new FieldValueData('cost', ['amount' => 99.99, 'currency' => 'USD'], $field);

        $this->assertSame(99.99, $value->amount());
    }

    public function test_amount_returns_null_when_not_set(): void
    {
        $field = Field::price('cost');
        $value = new FieldValueData('cost', ['amount' => null, 'currency' => 'USD'], $field);

        $this->assertNull($value->amount());
    }

    public function test_amount_returns_null_for_empty_value(): void
    {
        $field = Field::price('cost');
        $value = new FieldValueData('cost', null, $field);

        $this->assertNull($value->amount());
    }

    public function test_amount_returns_null_for_non_price_field(): void
    {
        $field = Field::text('title');
        $value = new FieldValueData('title', ['amount' => 50.0, 'currency' => 'USD'], $field);

        $this->assertNull($value->amount());
    }

    public function test_currency_returns_stored_currency(): void
    {
        $field = Field::price('cost')->currency('USD');
        $value = new FieldValueData('cost', ['amount' => 100.0, 'currency' => 'EUR'], $field);

        $this->assertSame('EUR', $value->currency());
    }

    public function test_currency_falls_back_to_field_definition(): void
    {
        $field = Field::price('cost')->currency('GBP');
        $value = new FieldValueData('cost', ['amount' => 50.0], $field);

        $this->assertSame('GBP', $value->currency());
    }

    public function test_currency_returns_empty_string_for_non_price_field(): void
    {
        $field = Field::text('title');
        $value = new FieldValueData('title', 'hello', $field);

        $this->assertSame('', $value->currency());
    }

    public function test_price_data_structure_is_stored_correctly(): void
    {
        $field = Field::price('price')->currency('USD');
        $stored = ['amount' => 100.5, 'currency' => 'USD'];
        $value = new FieldValueData('price', $stored, $field);

        $this->assertSame(100.5, $value->amount());
        $this->assertSame('USD', $value->currency());
    }

    public function test_price_field_get_currency_returns_definition_currency(): void
    {
        $field = Field::price('price')->currency('JPY');

        $this->assertSame('JPY', $field->getCurrency());
    }

    public function test_price_field_validation_includes_numeric_rules(): void
    {
        $field = Field::price('amount')->rules(['required']);

        $this->assertContains('required', $field->getRules());
    }

    public function test_price_to_array_decimals_default(): void
    {
        $field = Field::price('amount');

        $this->assertSame(2, $field->toArray()['decimals']);
    }
}

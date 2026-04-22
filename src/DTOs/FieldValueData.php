<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\DTOs;

use AhmedAliraqi\UiManager\Fields\BaseField;
use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;
use AhmedAliraqi\UiManager\Fields\PriceField;
use AhmedAliraqi\UiManager\Fields\SvgField;

final readonly class FieldValueData
{
    /**
     * @param bool $parseVariables  Set to false for repeatable section items,
     *                              where %placeholder% expansion is intentionally disabled.
     */
    public function __construct(
        public readonly string $name,
        public readonly mixed $rawValue,
        public readonly BaseField $definition,
        public readonly bool $parseVariables = true,
    ) {}

    public function getValue(): mixed
    {
        if ($this->parseVariables && is_string($this->rawValue)) {
            return app(\AhmedAliraqi\UiManager\Services\VariableParser::class)
                ->parse($this->rawValue);
        }

        return $this->rawValue;
    }

    public function getString(): string
    {
        $value = $this->getValue();

        // Arrays (e.g. media field objects) have no meaningful string representation.
        if (is_array($value)) {
            return '';
        }

        return is_string($value) ? $value : (string) ($value ?? '');
    }

    /**
     * Return the public URL for media fields.
     *
     * Checks the stored 'url' key first (fast path).  Falls back to a live
     * Spatie Media Library lookup by 'id' so signed/S3 URLs stay fresh.
     */
    public function getUrl(): string
    {
        $value = $this->rawValue;

        if (is_array($value) && isset($value['url']) && $value['url'] !== '') {
            return (string) $value['url'];
        }

        if (is_array($value) && isset($value['id'])) {
            try {
                $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::find((int) $value['id']);

                return $media?->getUrl() ?? '';
            } catch (\Throwable) {}
        }

        return is_string($value) ? $value : '';
    }

    /**
     * Return the raw SVG markup for SVG icon fields.
     *
     * The stored value IS the SVG content string (not a filename).
     * Returns an empty string when no value is set or the field is not an SvgField.
     */
    public function toSvg(): string
    {
        if (! ($this->definition instanceof SvgField)) {
            return '';
        }

        return is_string($this->rawValue) ? trim($this->rawValue) : '';
    }

    /**
     * Return the numeric amount from a price field.
     */
    public function amount(): float|null
    {
        if (! ($this->definition instanceof PriceField)) {
            return null;
        }

        $value = $this->rawValue;

        if (is_array($value) && array_key_exists('amount', $value)) {
            return $value['amount'] !== null ? (float) $value['amount'] : null;
        }

        return null;
    }

    /**
     * Return the currency string from a price field.
     * Falls back to the currency defined on the field definition.
     */
    public function currency(): string
    {
        if (! ($this->definition instanceof PriceField)) {
            return '';
        }

        $value = $this->rawValue;

        if (is_array($value) && isset($value['currency'])) {
            return (string) $value['currency'];
        }

        return $this->definition->getCurrency();
    }

    public function isMedia(): bool
    {
        return $this->definition instanceof ImageField
            || $this->definition instanceof FileField;
    }

    public function isEmpty(): bool
    {
        return $this->rawValue === null
            || $this->rawValue === ''
            || $this->rawValue === [];
    }

    public function __toString(): string
    {
        return $this->getString();
    }
}

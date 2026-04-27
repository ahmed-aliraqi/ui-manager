<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\DTOs;

use AhmedAliraqi\UiManager\Fields\BaseField;
use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;
use AhmedAliraqi\UiManager\Fields\PriceField;
use AhmedAliraqi\UiManager\Fields\SelectField;

final readonly class FieldValueData
{
    /**
     * @param bool        $parseVariables  Whether to resolve %placeholder% variables in the value.
     * @param string|null $locale          Explicit locale for translatable fields.
     *                                     Null means use app()->getLocale().
     */
    public function __construct(
        public readonly string $name,
        public readonly mixed $rawValue,
        public readonly BaseField $definition,
        public readonly bool $parseVariables = true,
        public readonly ?string $locale = null,
    ) {}

    public function getValue(): mixed
    {
        // Translatable fields: resolve locale before any further processing.
        if ($this->definition->isTranslatable()) {
            return $this->resolveLocaleValue();
        }

        if ($this->parseVariables && is_string($this->rawValue)) {
            return app(\AhmedAliraqi\UiManager\Services\VariableParser::class)
                ->parse($this->rawValue);
        }

        return $this->rawValue;
    }

    public function getString(): string
    {
        // Select fields with returnLabel: return the option label instead of the key.
        if ($this->definition instanceof SelectField && $this->definition->isReturnLabel()) {
            return $this->label();
        }

        $value = $this->getValue();

        // Arrays (e.g. media field objects) have no meaningful string representation.
        if (is_array($value)) {
            return '';
        }

        return is_string($value) ? $value : (string) ($value ?? '');
    }

    /**
     * Resolve the locale-appropriate string for a translatable field.
     * Falls back: requested locale → default_locale → first available value.
     */
    private function resolveLocaleValue(): string
    {
        // Legacy or non-locale string stored — return as-is with variable parsing.
        if (is_string($this->rawValue)) {
            if ($this->parseVariables) {
                return app(\AhmedAliraqi\UiManager\Services\VariableParser::class)
                    ->parse($this->rawValue);
            }

            return $this->rawValue;
        }

        if (! is_array($this->rawValue)) {
            return '';
        }

        $locale        = $this->locale ?? app()->getLocale();
        $defaultLocale = config('ui-manager.default_locale', 'en');

        $rawCopy = $this->rawValue;
        $value   = $rawCopy[$locale]
                ?? $rawCopy[$defaultLocale]
                ?? (count($rawCopy) > 0 ? reset($rawCopy) : '');

        $value = is_string($value) ? $value : '';

        if ($this->parseVariables && $value !== '') {
            return app(\AhmedAliraqi\UiManager\Services\VariableParser::class)
                ->parse($value);
        }

        return $value;
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

    /**
     * Return the option label for a select field.
     * Returns the stored key when no matching label is found, or '' for non-select fields.
     */
    public function label(): string
    {
        if (! ($this->definition instanceof SelectField)) {
            return '';
        }

        $key = is_string($this->rawValue) ? $this->rawValue : '';

        return (string) ($this->definition->getFieldOptions()[$key] ?? $key);
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

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\DTOs;

use AhmedAliraqi\UiManager\Fields\BaseField;
use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;

final readonly class FieldValueData
{
    public function __construct(
        public readonly string $name,
        public readonly mixed $rawValue,
        public readonly BaseField $definition,
    ) {}

    public function getValue(): mixed
    {
        if (is_string($this->rawValue)) {
            return app(\AhmedAliraqi\UiManager\Services\VariableParser::class)
                ->parse($this->rawValue);
        }

        return $this->rawValue;
    }

    public function getString(): string
    {
        $value = $this->getValue();

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

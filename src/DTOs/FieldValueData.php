<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\DTOs;

use AhmedAliraqi\UiManager\Fields\BaseField;
use AhmedAliraqi\UiManager\Fields\FileField;
use AhmedAliraqi\UiManager\Fields\ImageField;
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
     * Reads the stored filename from the icons path configured on the field
     * (or the global config('ui-manager.svg.icons_path') fallback).
     * Returns an empty string when the file is missing or the field is not an SvgField.
     */
    public function toSvg(): string
    {
        if (! ($this->definition instanceof SvgField)) {
            return '';
        }

        $filename = is_string($this->rawValue) ? trim($this->rawValue) : '';

        if ($filename === '') {
            return '';
        }

        $iconsPath = $this->definition->getResolvedIconsPath();
        $fullPath  = rtrim($iconsPath, '/\\') . DIRECTORY_SEPARATOR . basename($filename);

        if (! file_exists($fullPath) || ! is_readable($fullPath)) {
            return '';
        }

        return (string) file_get_contents($fullPath);
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

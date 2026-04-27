<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Support;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\BaseField;

/**
 * Wraps a non-repeatable section's stored data and exposes field values.
 */
final class SectionView
{
    /** @param array<string, mixed> $data  merged DB + default values */
    public function __construct(
        private readonly Section $definition,
        private readonly array $data,
    ) {}

    /**
     * Return a typed FieldValueData object for a given field name.
     *
     * Supports an optional locale suffix for translatable fields:
     *   field('title')     → current app locale
     *   field('title:ar')  → Arabic value
     */
    public function field(string $name): FieldValueData
    {
        // Parse optional locale suffix, e.g. "title:ar" → name="title", locale="ar".
        $locale = null;
        if (str_contains($name, ':')) {
            [$name, $locale] = explode(':', $name, 2);
        }

        $fieldsMap = $this->definition->getFieldsMap();
        $fieldDef  = $fieldsMap[$name] ?? $this->makeDummyField($name);
        $rawValue  = $this->data[$name] ?? $fieldDef->getDefault();

        return new FieldValueData($name, $rawValue, $fieldDef, parseVariables: true, locale: $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    public function getSectionName(): string
    {
        return $this->definition->getName();
    }

    /**
     * Create a passthrough field definition for unknown field names.
     */
    private function makeDummyField(string $name): BaseField
    {
        return \AhmedAliraqi\UiManager\Fields\Field::text($name);
    }
}

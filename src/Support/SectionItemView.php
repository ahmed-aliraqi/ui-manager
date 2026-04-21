<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Support;

use AhmedAliraqi\UiManager\Core\Section;
use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\Field;

/**
 * Represents a single item within a repeatable section.
 */
final class SectionItemView
{
    public function __construct(
        private readonly Section $definition,
        private readonly array $data,
    ) {}

    public function field(string $name): FieldValueData
    {
        $fieldsMap = $this->definition->getFieldsMap();
        $fieldDef  = $fieldsMap[$name] ?? Field::text($name);
        $rawValue  = $this->data[$name] ?? $fieldDef->getDefault();

        return new FieldValueData($name, $rawValue, $fieldDef);
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        return $this->data;
    }
}

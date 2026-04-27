<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Core;

use AhmedAliraqi\UiManager\Contracts\HasFields;
use AhmedAliraqi\UiManager\Fields\BaseField;

abstract class Section implements HasFields
{
    /** Layout this section belongs to. */
    protected string $layout = 'default';

    /** Unique slug-style identifier for this section. */
    protected string $name;

    /** The Page class this section belongs to. */
    protected string $page;

    /** Whether this section appears in the dashboard. */
    protected bool $visible = true;

    /** Sort order within the page tab list. */
    protected int $order = 0;

    /** Human-readable label. Falls back to title-cased $name. */
    protected string $label = '';

    /** Field name used as the item label in the repeatable list. Empty = auto (first string value). */
    protected string $listField = '';

    /**
     * Define the fields for this section.
     *
     * @return BaseField[]
     */
    abstract public function fields(): array;

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label !== ''
            ? $this->label
            : ucwords(str_replace(['-', '_'], ' ', $this->name));
    }

    public function getLayout(): string
    {
        return $this->layout;
    }

    public function getPage(): string
    {
        return $this->page;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function getOrder(): int
    {
        return $this->order;
    }

    public function getListField(): string
    {
        return $this->listField;
    }

    public function isRepeatable(): bool
    {
        return $this instanceof \AhmedAliraqi\UiManager\Contracts\Repeatable;
    }

    /**
     * @return array<string, BaseField>
     */
    public function getFieldsMap(): array
    {
        $map = [];
        foreach ($this->fields() as $field) {
            $map[$field->getName()] = $field;
        }

        return $map;
    }

    /**
     * Pre-seeded items shown when no DB data exists (repeatable sections only).
     * Override to provide default items; each element is a field-map array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function default(): array
    {
        return [];
    }

    /**
     * Build a defaults array from declared field defaults.
     *
     * @return array<string, mixed>
     */
    public function resolveDefaults(): array
    {
        $defaults = [];

        foreach ($this->fields() as $field) {
            $defaults[$field->getName()] = $field->getDefault();
        }

        return $defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name'         => $this->getName(),
            'label'        => $this->getLabel(),
            'layout'       => $this->getLayout(),
            'page'         => $this->getPage(),
            'visible'      => $this->isVisible(),
            'order'        => $this->getOrder(),
            'repeatable'   => $this->isRepeatable(),
            'list_field'   => $this->getListField(),
            'fields'       => array_values(
                array_map(function (BaseField $f) {
                    $arr = $f->toArray();
                    if ($f->isVariableEnabled()) {
                        $arr['variable_formats'] = $f->getVariableFormats($this->getName());
                    }
                    return $arr;
                }, $this->fields())
            ),
        ];
    }
}

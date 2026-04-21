<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\DTOs;

final readonly class SectionData
{
    /**
     * @param array<string, mixed> $fields
     */
    public function __construct(
        public readonly string $page,
        public readonly string $section,
        public readonly string $layout,
        public readonly array $fields,
        public readonly ?int $sortOrder = null,
        public readonly ?int $id = null,
    ) {}

    public function getField(string $name): mixed
    {
        return $this->fields[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'page'       => $this->page,
            'section'    => $this->section,
            'layout'     => $this->layout,
            'fields'     => $this->fields,
            'sort_order' => $this->sortOrder,
        ];
    }
}

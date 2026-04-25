<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class ImageField extends BaseField
{
    /** @var array<string> */
    protected array $accept = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'];

    protected int $maxSize = 5120; // KB

    protected ?int $width = null;

    protected ?int $height = null;

    /** @param array<string> $types */
    public function accept(array $types): static
    {
        $this->accept = $types;

        return $this;
    }

    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;

        return $this;
    }

    public function dimensions(int $width, int $height): static
    {
        $this->width  = $width;
        $this->height = $height;

        return $this;
    }

    public function getType(): string
    {
        return 'image';
    }

    public function getVariableFormats(string $sectionName): array
    {
        $key = "{$sectionName}.{$this->getName()}";

        return ["%{$key}:url%", "%{$key}:name%"];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'accept'   => $this->accept,
            'max_size' => $this->maxSize,
            'width'    => $this->width,
            'height'   => $this->height,
        ], fn ($v) => $v !== null));
    }
}

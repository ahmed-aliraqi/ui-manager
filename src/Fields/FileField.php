<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class FileField extends BaseField
{
    /** @var array<string> */
    protected array $accept = [];

    protected int $maxSize = 10240; // KB

    protected bool $multiple = false;

    /** @param array<string> $mimeTypes */
    public function accept(array $mimeTypes): static
    {
        $this->accept = $mimeTypes;

        return $this;
    }

    public function maxSize(int $kb): static
    {
        $this->maxSize = $kb;

        return $this;
    }

    public function multiple(bool $value = true): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function getType(): string
    {
        return 'file';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'accept'   => $this->accept,
            'max_size' => $this->maxSize,
            'multiple' => $this->multiple,
        ]);
    }
}

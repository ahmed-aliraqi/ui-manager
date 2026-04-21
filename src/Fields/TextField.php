<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class TextField extends BaseField
{
    protected int $maxLength = 0;

    protected bool $multiline = false;

    public function multiline(bool $value = true): static
    {
        $this->multiline = $value;

        return $this;
    }

    public function maxLength(int $length): static
    {
        $this->maxLength = $length;
        $this->rules[] = "max:{$length}";

        return $this;
    }

    public function getType(): string
    {
        return $this->multiline ? 'textarea' : 'text';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'multiline'  => $this->multiline ?: null,
            'max_length' => $this->maxLength ?: null,
        ]));
    }
}

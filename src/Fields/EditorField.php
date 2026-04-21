<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class EditorField extends BaseField
{
    /** @var array<string> */
    protected array $extensions = ['bold', 'italic', 'link', 'bulletList', 'orderedList', 'heading'];

    /** @param array<string> $extensions */
    public function extensions(array $extensions): static
    {
        $this->extensions = $extensions;

        return $this;
    }

    public function getType(): string
    {
        return 'editor';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'extensions' => $this->extensions,
        ]);
    }
}

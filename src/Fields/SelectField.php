<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class SelectField extends BaseField
{
    /** @var array<string|int, string> */
    protected array $fieldOptions = [];

    protected bool $multiple = false;

    protected bool $searchable = false;

    /** When true, getString() returns the option label instead of the stored key. */
    protected bool $returnLabel = false;

    /**
     * @param array<string|int, string> $options  key => label
     */
    public function options(array $options): static
    {
        $this->fieldOptions = $options;

        return $this;
    }

    public function multiple(bool $value = true): static
    {
        $this->multiple = $value;

        return $this;
    }

    public function searchable(bool $value = true): static
    {
        $this->searchable = $value;

        return $this;
    }

    /**
     * Make getString() return the option label instead of the stored key.
     */
    public function returnLabel(): static
    {
        $this->returnLabel = true;

        return $this;
    }

    public function isReturnLabel(): bool
    {
        return $this->returnLabel;
    }

    /**
     * @return array<string|int, string>
     */
    public function getFieldOptions(): array
    {
        return $this->fieldOptions;
    }

    public function getType(): string
    {
        return 'select';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'options'    => array_map(
                fn (string|int $key, string $label) => ['value' => $key, 'label' => $label],
                array_keys($this->fieldOptions),
                array_values($this->fieldOptions)
            ),
            'multiple'   => $this->multiple,
            'searchable' => $this->searchable,
        ]);
    }
}

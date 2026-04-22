<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

class ColorField extends BaseField
{
    protected bool $alpha = false;

    /**
     * Allow an alpha (opacity) channel in the color picker.
     */
    public function alpha(bool $value = true): static
    {
        $this->alpha = $value;

        return $this;
    }

    public function getType(): string
    {
        return 'color';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'alpha' => $this->alpha,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Datetime field — stores an ISO-8601 datetime string (YYYY-MM-DDTHH:MM).
 */
class DatetimeField extends BaseField
{
    protected ?string $min = null;

    protected ?string $max = null;

    public function min(string $datetime): static
    {
        $this->min = $datetime;

        return $this;
    }

    public function max(string $datetime): static
    {
        $this->max = $datetime;

        return $this;
    }

    public function getType(): string
    {
        return 'datetime';
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'min' => $this->min,
            'max' => $this->max,
        ], fn ($v) => $v !== null));
    }
}

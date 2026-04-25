<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Date field — stores an ISO-8601 date string (YYYY-MM-DD).
 */
class DateField extends BaseField
{
    protected ?string $min = null;

    protected ?string $max = null;

    public function min(string $date): static
    {
        $this->min = $date;

        return $this;
    }

    public function max(string $date): static
    {
        $this->max = $date;

        return $this;
    }

    public function getType(): string
    {
        return 'date';
    }

    public function getVariableFormats(string $sectionName): array
    {
        $key = "{$sectionName}.{$this->getName()}";

        return ["%{$key}%", "%{$key}:format(Y-m-d)%"];
    }

    public function toArray(): array
    {
        return array_merge(parent::toArray(), array_filter([
            'min' => $this->min,
            'max' => $this->max,
        ], fn ($v) => $v !== null));
    }
}

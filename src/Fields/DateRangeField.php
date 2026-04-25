<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Date-range field — stores { "start": "YYYY-MM-DD", "end": "YYYY-MM-DD" }.
 */
class DateRangeField extends BaseField
{
    public function getType(): string
    {
        return 'date_range';
    }

    public function getVariableFormats(string $sectionName): array
    {
        $key = "{$sectionName}.{$this->getName()}";

        return ["%{$key}:start%", "%{$key}:end%"];
    }

    /**
     * Convenience: set default as a [start, end] pair.
     *
     * @param string $start  YYYY-MM-DD
     * @param string $end    YYYY-MM-DD
     */
    public function defaultRange(string $start, string $end): static
    {
        $this->default = ['start' => $start, 'end' => $end];

        return $this;
    }
}

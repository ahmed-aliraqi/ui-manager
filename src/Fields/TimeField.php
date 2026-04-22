<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Time field — stores time as HH:MM (24-hour).
 */
class TimeField extends BaseField
{
    public function getType(): string
    {
        return 'time';
    }
}

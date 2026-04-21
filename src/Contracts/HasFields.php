<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Contracts;

use AhmedAliraqi\UiManager\Fields\BaseField;

interface HasFields
{
    /**
     * Define the fields for this section.
     *
     * @return BaseField[]
     */
    public function fields(): array;
}

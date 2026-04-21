<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Exceptions;

use RuntimeException;

class UiManagerException extends RuntimeException
{
    public static function pageNotFound(string $name): self
    {
        return new self("UI page [{$name}] is not registered.");
    }

    public static function sectionNotFound(string $name, string $page = ''): self
    {
        $context = $page ? " for page [{$page}]" : '';

        return new self("UI section [{$name}]{$context} is not registered.");
    }
}

<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Variables\VariableRegistry;

/**
 * Parses strings containing variable placeholders like %section.field%
 * and resolves them to their actual values.
 *
 * Circular reference protection is enforced via a resolution-depth counter.
 */
final class VariableParser
{
    private int $currentDepth = 0;

    public function __construct(
        private readonly VariableRegistry $registry,
    ) {}

    /**
     * Replace all %key% placeholders in the given text.
     */
    public function parse(string $text, int $depth = 0): string
    {
        $maxDepth = (int) config('ui-manager.variables.max_depth', 10);

        if ($depth >= $maxDepth) {
            return $text;
        }

        $start = config('ui-manager.variables.delimiter_start', '%');
        $end   = config('ui-manager.variables.delimiter_end', '%');

        $pattern = '/' . preg_quote($start, '/') . '([a-zA-Z0-9_.-]+)' . preg_quote($end, '/') . '/';

        return preg_replace_callback($pattern, function (array $matches) use ($depth): string {
            $key   = $matches[1];
            $value = $this->registry->resolve($key);

            if ($value === null) {
                return $matches[0]; // leave placeholder intact when unresolvable
            }

            $resolved = (string) $value;

            // Recursively parse resolved values (with depth guard)
            return $this->parse($resolved, $depth + 1);
        }, $text) ?? $text;
    }

    /**
     * Extract all variable keys referenced in a string.
     *
     * @return array<string>
     */
    public function extractKeys(string $text): array
    {
        $start   = config('ui-manager.variables.delimiter_start', '%');
        $end     = config('ui-manager.variables.delimiter_end', '%');
        $pattern = '/' . preg_quote($start, '/') . '([a-zA-Z0-9_.-]+)' . preg_quote($end, '/') . '/';

        preg_match_all($pattern, $text, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Build the variable placeholder string for a given key.
     */
    public function placeholder(string $key): string
    {
        $start = config('ui-manager.variables.delimiter_start', '%');
        $end   = config('ui-manager.variables.delimiter_end', '%');

        return "{$start}{$key}{$end}";
    }
}

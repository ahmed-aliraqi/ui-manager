<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Variables;

use Closure;
use InvalidArgumentException;

/**
 * Central registry for all resolvable variables.
 *
 * Variables follow the pattern: section_name.field_name  (e.g. "header.app_name")
 * Resolvers can also be registered under arbitrary dot-notation keys.
 */
final class VariableRegistry
{
    /** @var array<string, Closure(): mixed> */
    private array $resolvers = [];

    /**
     * Register a variable resolver.
     *
     * @param Closure(): mixed $resolver
     */
    public function register(string $key, Closure $resolver): void
    {
        $this->resolvers[$key] = $resolver;
    }

    /**
     * Register a static value as a variable.
     */
    public function value(string $key, mixed $value): void
    {
        $this->resolvers[$key] = static fn () => $value;
    }

    /**
     * Resolve a variable key to its value.
     *
     * @throws InvalidArgumentException when the key is unknown and strict mode is on
     */
    public function resolve(string $key, bool $strict = false): mixed
    {
        if (isset($this->resolvers[$key])) {
            return ($this->resolvers[$key])();
        }

        // Fallback: try to resolve from the UiManager (section.field pattern)
        if (str_contains($key, '.')) {
            [$section, $field] = explode('.', $key, 2);

            try {
                $sectionView = app(\AhmedAliraqi\UiManager\Services\UiManager::class)
                    ->section($section);

                if ($sectionView !== null) {
                    return $sectionView->field($field)->getString();
                }
            } catch (\Throwable) {
                // swallow — return null below
            }
        }

        if ($strict) {
            throw new InvalidArgumentException("Unknown UI variable: [{$key}]");
        }

        return null;
    }

    /**
     * @return array<string>
     */
    public function keys(): array
    {
        return array_keys($this->resolvers);
    }

    public function has(string $key): bool
    {
        return isset($this->resolvers[$key]);
    }
}

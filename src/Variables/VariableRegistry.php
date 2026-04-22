<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Variables;

use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use Closure;
use InvalidArgumentException;

/**
 * Central registry for all resolvable variables.
 *
 * Variables follow the pattern: section_name.field_name  (e.g. "header.app_name")
 * Resolvers can also be registered under arbitrary dot-notation keys.
 *
 * Modifiers are supported via a colon suffix on the key:
 *   %header.logo%       → raw value (array for media fields)
 *   %header.logo:url%   → public URL string
 *   %header.logo:name%  → original filename string
 *   %header.logo:size%  → file size in bytes (string)
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
     * Resolve a variable key (with optional :modifier) to its value.
     *
     * @throws InvalidArgumentException when the key is unknown and strict mode is on
     */
    public function resolve(string $key, bool $strict = false): mixed
    {
        // Split off optional modifier: "header.logo:url" → ["header.logo", "url"]
        $modifier = null;
        $baseKey  = $key;

        if (str_contains($key, ':')) {
            [$baseKey, $modifier] = explode(':', $key, 2);
        }

        // Direct resolver lookup
        if (isset($this->resolvers[$baseKey])) {
            $value = ($this->resolvers[$baseKey])();

            return $this->applyModifier($value, $modifier);
        }

        // Fallback: try to resolve from the UiManager (section.field pattern)
        if (str_contains($baseKey, '.')) {
            [$section, $field] = explode('.', $baseKey, 2);

            try {
                $sectionView = app(\AhmedAliraqi\UiManager\Services\UiManager::class)
                    ->section($section);

                if ($sectionView !== null) {
                    $fieldValue = $sectionView->field($field);

                    return $modifier !== null
                        ? $this->applyModifierToField($fieldValue, $modifier)
                        : $fieldValue->getString();
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

    /**
     * Apply a modifier to an arbitrary resolved value (registered resolver path).
     */
    private function applyModifier(mixed $value, ?string $modifier): mixed
    {
        if ($modifier === null) {
            return $value;
        }

        if (is_array($value)) {
            return match ($modifier) {
                'url'  => (string) ($value['url'] ?? ''),
                'name' => (string) ($value['filename'] ?? ''),
                'size' => (string) ($value['size'] ?? ''),
                default => $value,
            };
        }

        // String or scalar: return as-is (modifier not applicable)
        return is_string($value) ? $value : null;
    }

    /**
     * Apply a modifier to a FieldValueData object (section.field fallback path).
     */
    private function applyModifierToField(FieldValueData $fieldValue, string $modifier): string
    {
        return match ($modifier) {
            'url'  => $fieldValue->getUrl(),
            'name' => is_array($fieldValue->rawValue) ? (string) ($fieldValue->rawValue['filename'] ?? '') : '',
            'size' => is_array($fieldValue->rawValue) ? (string) ($fieldValue->rawValue['size'] ?? '') : '',
            default => $fieldValue->getString(),
        };
    }
}

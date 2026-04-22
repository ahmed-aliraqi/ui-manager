<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * URL field — stores a validated URL string.
 *
 * The 'url' Laravel validation rule is always included in getRules() so
 * SaveSectionRequest validates it automatically.
 */
class UrlField extends BaseField
{
    public function getType(): string
    {
        return 'url';
    }

    /**
     * Always append 'url' to the declared rules so validation is never skipped.
     *
     * @return array<int, string|array<mixed>>
     */
    public function getRules(): array
    {
        $rules = parent::getRules();

        if (! in_array('url', $rules, true)) {
            $rules[] = 'url';
        }

        return $rules;
    }

    public function toArray(): array
    {
        // Use getRules() so the 'url' rule is always visible to the frontend.
        $base          = parent::toArray();
        $base['rules'] = $this->getRules();

        return $base;
    }
}

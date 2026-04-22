<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * SVG icon picker field.
 *
 * Icons are loaded at runtime from the path configured via
 * config('ui-manager.svg.icons_path') — defaults to resource_path('ui-icons').
 *
 * The stored value is the icon filename (e.g. "facebook.svg").
 * Retrieve SVG markup in Blade via FieldValueData::toSvg().
 */
class SvgField extends BaseField
{
    protected ?string $iconsPath = null;

    /**
     * Override the icons directory for this specific field.
     * When null, the global config('ui-manager.svg.icons_path') is used.
     */
    public function iconsPath(string $path): static
    {
        $this->iconsPath = $path;

        return $this;
    }

    public function getResolvedIconsPath(): string
    {
        return $this->iconsPath
            ?? config('ui-manager.svg.icons_path')
            ?? resource_path('ui-icons');
    }

    public function getType(): string
    {
        return 'svg';
    }
}

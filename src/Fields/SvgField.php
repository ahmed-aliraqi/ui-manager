<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * SVG icon picker field.
 *
 * Icons are bundled inside the package at resources/icons/.
 * The stored value is the raw SVG markup string (not a filename).
 * Retrieve it in Blade via FieldValueData::toSvg().
 */
class SvgField extends BaseField
{
    protected ?string $iconsPath = null;

    /**
     * Override the icons directory (e.g. for a custom icon set).
     * When null, the package's own resources/icons/ is used.
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
            ?? dirname(__DIR__, 2) . '/resources/icons';
    }

    public function getType(): string
    {
        return 'svg';
    }
}

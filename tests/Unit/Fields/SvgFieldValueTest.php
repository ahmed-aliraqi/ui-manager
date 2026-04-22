<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Tests\Unit\Fields;

use AhmedAliraqi\UiManager\DTOs\FieldValueData;
use AhmedAliraqi\UiManager\Fields\Field;
use AhmedAliraqi\UiManager\Fields\SvgField;
use AhmedAliraqi\UiManager\Tests\TestCase;

final class SvgFieldValueTest extends TestCase
{
    public function test_svg_field_defaults_to_package_icons_path(): void
    {
        $field = Field::svg('icon');

        $path = $field->getResolvedIconsPath();

        $this->assertStringContainsString('resources/icons', $path);
        $this->assertStringNotContainsString('ui-icons', $path);
    }

    public function test_svg_field_config_override_takes_precedence(): void
    {
        config(['ui-manager.svg.icons_path' => '/custom/path']);

        $field = Field::svg('icon');

        $this->assertSame('/custom/path', $field->getResolvedIconsPath());

        config(['ui-manager.svg.icons_path' => null]);
    }

    public function test_to_svg_returns_stored_svg_content_directly(): void
    {
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/></svg>';

        $field = Field::svg('icon');
        $value = new FieldValueData('icon', $svgContent, $field);

        $this->assertSame($svgContent, $value->toSvg());
    }

    public function test_to_svg_returns_empty_string_when_no_value(): void
    {
        $field = Field::svg('icon');
        $value = new FieldValueData('icon', null, $field);

        $this->assertSame('', $value->toSvg());
    }

    public function test_to_svg_returns_empty_string_for_non_svg_field(): void
    {
        $field = Field::text('title');
        $value = new FieldValueData('title', '<svg>not-an-svg-field</svg>', $field);

        $this->assertSame('', $value->toSvg());
    }

    public function test_to_svg_trims_whitespace(): void
    {
        $svgContent = '  <svg><circle/></svg>  ';

        $field = Field::svg('icon');
        $value = new FieldValueData('icon', $svgContent, $field);

        $this->assertSame('<svg><circle/></svg>', $value->toSvg());
    }

    public function test_get_string_returns_svg_content(): void
    {
        $svgContent = '<svg><path d="M0 0"/></svg>';

        $field = Field::svg('icon');
        $value = new FieldValueData('icon', $svgContent, $field);

        $this->assertSame($svgContent, $value->getString());
    }

    public function test_svg_field_is_not_media(): void
    {
        $field = Field::svg('icon');
        $value = new FieldValueData('icon', '<svg/>', $field);

        $this->assertFalse($value->isMedia());
    }
}

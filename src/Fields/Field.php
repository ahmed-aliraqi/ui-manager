<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Fields;

/**
 * Static factory for creating field instances.
 *
 * Usage:
 *   Field::text('title')->rules(['required'])->help('...')
 *   Field::editor('body')
 *   Field::select('status')->options(['draft' => 'Draft', 'published' => 'Published'])
 *   Field::image('cover')
 *   Field::file('cv')->accept(['application/pdf'])
 *   Field::color('bg_color')
 *   Field::date('published_at')
 *   Field::time('start_time')
 *   Field::datetime('event_at')
 *   Field::dateRange('range')
 *   Field::url('website')
 *   Field::price('price')->currency('USD')
 */
final class Field
{
    public static function text(string $name): TextField
    {
        return new TextField($name);
    }

    public static function textarea(string $name): TextField
    {
        return (new TextField($name))->multiline();
    }

    public static function editor(string $name): EditorField
    {
        return new EditorField($name);
    }

    public static function select(string $name): SelectField
    {
        return new SelectField($name);
    }

    public static function image(string $name): ImageField
    {
        return new ImageField($name);
    }

    public static function file(string $name): FileField
    {
        return new FileField($name);
    }

    public static function color(string $name): ColorField
    {
        return new ColorField($name);
    }

    public static function date(string $name): DateField
    {
        return new DateField($name);
    }

    public static function time(string $name): TimeField
    {
        return new TimeField($name);
    }

    public static function datetime(string $name): DatetimeField
    {
        return new DatetimeField($name);
    }

    public static function dateRange(string $name): DateRangeField
    {
        return new DateRangeField($name);
    }

    public static function url(string $name): UrlField
    {
        return new UrlField($name);
    }

    public static function price(string $name): PriceField
    {
        return new PriceField($name);
    }
}

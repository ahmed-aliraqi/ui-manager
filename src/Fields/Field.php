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
}

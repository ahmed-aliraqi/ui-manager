<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Facades;

use AhmedAliraqi\UiManager\Services\UiManager;
use AhmedAliraqi\UiManager\Support\RepeatableSectionView;
use AhmedAliraqi\UiManager\Support\SectionView;
use Illuminate\Support\Facades\Facade;

/**
 * @method static SectionView|RepeatableSectionView section(string $name)
 * @method static SectionView|RepeatableSectionView|null sectionOrNull(string $name)
 * @method static \AhmedAliraqi\UiManager\Services\PageRegistry pages()
 * @method static \AhmedAliraqi\UiManager\Services\SectionRegistry sections()
 * @method static void flushCache(string $page, string $section)
 * @method static void flushAllCache()
 *
 * @see UiManager
 */
class Ui extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return UiManager::class;
    }
}

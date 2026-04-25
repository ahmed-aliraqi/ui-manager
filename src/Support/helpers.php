<?php

declare(strict_types=1);

use AhmedAliraqi\UiManager\Services\UiManager;

if (! function_exists('ui')) {
    /**
     * Return the UiManager instance (or call a section directly).
     *
     * Examples:
     *   ui()->section('banner')->field('title')
     *   ui('banner')->field('title')                   // shorthand
     *   ui('hero', layout: 'homepage')->field('title') // layout-specific variant
     */
    function ui(?string $section = null, ?string $layout = null): UiManager|\AhmedAliraqi\UiManager\Support\SectionView|\AhmedAliraqi\UiManager\Support\RepeatableSectionView
    {
        $manager = app(UiManager::class);

        if ($section !== null) {
            return $manager->section($section, $layout);
        }

        return $manager;
    }
}

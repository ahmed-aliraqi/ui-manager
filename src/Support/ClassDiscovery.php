<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Support;

use ReflectionClass;
use Symfony\Component\Finder\Finder;

final class ClassDiscovery
{
    /**
     * Find all concrete classes in $path that extend $baseClass.
     *
     * @template T of object
     * @param  class-string<T> $baseClass
     * @return array<class-string<T>>
     */
    public static function discover(string $path, string $namespace, string $baseClass): array
    {
        $fullPath = base_path($path);

        if (! is_dir($fullPath)) {
            return [];
        }

        $finder  = Finder::create()->files()->name('*.php')->in($fullPath);
        $classes = [];

        foreach ($finder as $file) {
            $relativePath = $file->getRelativePathname();
            $className    = $namespace . '\\' . str_replace(['/', '.php'], ['\\', ''], $relativePath);

            if (! class_exists($className)) {
                continue;
            }

            $ref = new ReflectionClass($className);

            if ($ref->isAbstract() || $ref->isInterface() || $ref->isTrait()) {
                continue;
            }

            if ($ref->isSubclassOf($baseClass)) {
                /** @var class-string<T> $className */
                $classes[] = $className;
            }
        }

        return $classes;
    }
}

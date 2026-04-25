<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int    $id
 * @property string $layout
 * @property string $page
 * @property string $section
 * @property array<string, mixed>|null $fields
 * @property int|null $sort_order
 *
 * @method static Builder forPage(string $page)
 * @method static Builder forSection(string $page, string $section)
 */
class UiContent extends Model
{
    /** @var array<int, string> */
    protected $fillable = [
        'layout',
        'page',
        'section',
        'fields',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'fields'     => 'array',
        'sort_order' => 'integer',
    ];

    protected $table = 'ui_contents';

    public function scopeForPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page);
    }

    public function scopeForSection(Builder $query, string $page, string $section): Builder
    {
        return $query->where('page', $page)->where('section', $section);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Retrieve the single record for a non-repeatable section, or null.
     */
    public static function findSection(string $page, string $section, ?string $layout = null): ?self
    {
        return static::forSection($page, $section)
            ->when($layout !== null, fn (Builder $q) => $q->where('layout', $layout))
            ->whereNull('sort_order')
            ->first();
    }

    /**
     * Retrieve all ordered items for a repeatable section.
     *
     * @return Collection<int, static>
     */
    public static function findRepeatableItems(string $page, string $section, ?string $layout = null): Collection
    {
        return static::forSection($page, $section)
            ->when($layout !== null, fn (Builder $q) => $q->where('layout', $layout))
            ->whereNotNull('sort_order')
            ->ordered()
            ->get();
    }

    /**
     * Get a single field value by name.
     */
    public function getField(string $name): mixed
    {
        return $this->fields[$name] ?? null;
    }
}

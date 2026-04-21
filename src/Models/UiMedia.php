<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * @property int    $id
 * @property string $collection
 * @property string $disk
 * @property string $path
 * @property string $filename
 * @property string|null $mime_type
 * @property int    $size
 * @property array<string, mixed>|null $custom_properties
 */
class UiMedia extends Model
{
    protected $table = 'ui_media';

    /** @var array<int, string> */
    protected $fillable = [
        'collection',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'custom_properties',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'size'              => 'integer',
        'custom_properties' => 'array',
    ];

    public function getUrl(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }
}

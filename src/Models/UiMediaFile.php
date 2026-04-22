<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Lightweight owner model for Spatie Media Library.
 * One record is created per uploaded file; singleFile() ensures the old
 * physical file is deleted when the slot is updated.
 */
class UiMediaFile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table = 'ui_media_files';

    /** @var array<int, string> */
    protected $fillable = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->singleFile();
        $this->addMediaCollection('files')->singleFile();
    }
}

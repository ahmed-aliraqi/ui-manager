<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Models\UiMediaFile;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class MediaUploadService
{
    /**
     * Upload a file via Spatie Media Library.
     *
     * When $existingMediaId is supplied the upload replaces the file on the
     * same UiMediaFile owner — singleFile() automatically deletes the old one.
     */
    public function upload(UploadedFile $file, ?int $existingMediaId = null): Media
    {
        $collection = str_starts_with($file->getMimeType() ?? '', 'image/') ? 'images' : 'files';

        if ($existingMediaId !== null) {
            $existing = Media::find($existingMediaId);
            if ($existing && $existing->model instanceof UiMediaFile) {
                return $existing->model
                    ->addMedia($file)
                    ->usingFileName($file->getClientOriginalName())
                    ->toMediaCollection($collection);
            }
        }

        $owner = UiMediaFile::create();

        return $owner
            ->addMedia($file)
            ->usingFileName($file->getClientOriginalName())
            ->toMediaCollection($collection);
    }

    public function delete(int $mediaId): void
    {
        Media::find($mediaId)?->delete();
    }
}

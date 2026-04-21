<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Services;

use AhmedAliraqi\UiManager\Models\UiMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class MediaUploadService
{
    public function upload(UploadedFile $file, string $collection = 'default'): UiMedia
    {
        $disk      = config('ui-manager.media.disk', 'public');
        $extension = $file->getClientOriginalExtension();
        $filename  = Str::uuid() . '.' . $extension;
        $path      = "ui-manager/{$collection}/{$filename}";

        Storage::disk($disk)->putFileAs(
            "ui-manager/{$collection}",
            $file,
            $filename
        );

        return UiMedia::create([
            'collection' => $collection,
            'disk'       => $disk,
            'path'       => $path,
            'filename'   => $file->getClientOriginalName(),
            'mime_type'  => $file->getMimeType(),
            'size'       => $file->getSize(),
        ]);
    }

    public function delete(int $id): void
    {
        $media = UiMedia::findOrFail($id);
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();
    }
}

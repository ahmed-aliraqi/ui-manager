<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Services\MediaUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class MediaController extends Controller
{
    public function __construct(
        private readonly MediaUploadService $uploadService,
    ) {}

    /**
     * POST /api/media
     *
     * Accepts:
     *  - file             : UploadedFile (required)
     *  - existing_media_id: integer (optional) — when provided the new file
     *                        replaces the old one on the same owner model;
     *                        singleFile() automatically deletes the old file.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file'               => 'required|file|max:51200',
            'existing_media_id'  => 'nullable|integer',
        ]);

        $media = $this->uploadService->upload(
            $request->file('file'),
            $request->filled('existing_media_id') ? (int) $request->input('existing_media_id') : null,
        );

        return response()->json([
            'data' => [
                'id'       => $media->id,
                'url'      => $media->getUrl(),
                'filename' => $media->file_name,
                'mime'     => $media->mime_type,
                'size'     => $media->size,
            ],
        ], 201);
    }

    /**
     * DELETE /api/media/{media}
     */
    public function destroy(int $media): JsonResponse
    {
        $this->uploadService->delete($media);

        return response()->json(null, 204);
    }
}

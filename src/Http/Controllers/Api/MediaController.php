<?php

declare(strict_types=1);

namespace AhmedAliraqi\UiManager\Http\Controllers\Api;

use AhmedAliraqi\UiManager\Models\UiMedia;
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
     * Upload a file and return URL + metadata.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file'       => 'required|file|max:51200',
            'collection' => 'nullable|string|max:64',
        ]);

        $media = $this->uploadService->upload(
            $request->file('file'),
            $request->input('collection', 'default')
        );

        return response()->json([
            'data' => [
                'id'       => $media->id,
                'url'      => $media->getUrl(),
                'filename' => $media->filename,
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

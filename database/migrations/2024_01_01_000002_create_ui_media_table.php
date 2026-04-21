<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight media table for tracking uploaded assets without requiring
 * a full Spatie Media Library setup when the host app hasn't configured it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_media', function (Blueprint $table): void {
            $table->id();
            $table->string('collection')->default('default');
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('filename');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->json('custom_properties')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_media');
    }
};

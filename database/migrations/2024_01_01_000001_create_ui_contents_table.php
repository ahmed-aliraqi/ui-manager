<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ui_contents', function (Blueprint $table): void {
            $table->id();
            $table->string('layout')->default('default')->index();
            $table->string('page')->index();
            $table->string('section')->index();
            $table->json('fields')->nullable();

            // For repeatable sections: each row is one item.
            // NULL means the section is not repeatable (single record).
            $table->unsignedSmallInteger('sort_order')->nullable();

            $table->timestamps();

            // Unique constraint for non-repeatable sections
            $table->index(['page', 'section', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ui_contents');
    }
};

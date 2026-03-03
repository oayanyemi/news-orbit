<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table): void {
            $table->id();
            $table->string('source');
            $table->string('external_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('url');
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('category')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->unique(['source', 'external_id']);
            $table->index('source');
            $table->index('category');
            $table->index('author');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};

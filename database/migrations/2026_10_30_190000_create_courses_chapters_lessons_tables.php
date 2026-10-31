<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Courses Table
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->string('slug', 255);
            $table->text('description')->nullable();
            $table->string('cover_image', 512)->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
            $table->unique(['user_id', 'slug']);
        });

        // 2. Chapters Table
        Schema::create('chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_id', 'sort_order']);
        });

        // 3. Lessons Table
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chapter_id')->constrained('chapters')->onDelete('cascade');
            $table->foreignId('aggregated_content_id')->nullable()->constrained('aggregated_contents')->onDelete('set null');
            $table->string('title', 255);
            $table->longText('content')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['chapter_id', 'sort_order']);
            $table->index('aggregated_content_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
        Schema::dropIfExists('chapters');
        Schema::dropIfExists('courses');
    }
};


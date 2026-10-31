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
        // 1. Tags table (scoped per user)
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name', 64);
            $table->string('slug', 64);
            $table->timestamps();

            $table->unique(['user_id', 'slug']);
        });

        // 2. Pivot table: aggregated_content_tag
        Schema::create('aggregated_content_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade');
            $table->foreignId('tag_id')->constrained('tags')->onDelete('cascade');

            $table->unique(['aggregated_content_id', 'tag_id']);
        });

        // 3. Personal Notes table
        Schema::create('personal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade');
            $table->mediumText('note_content');
            $table->timestamps();

            $table->index(['user_id', 'aggregated_content_id']);
        });

        // 4. Bookmarks table
        Schema::create('bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'aggregated_content_id']);
        });

        // 5. Content Reading Histories & Study Tracker
        Schema::create('content_reading_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade');
            $table->unsignedInteger('seconds_spent')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('last_read_at')->useCurrent();
            $table->unsignedInteger('view_count')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'aggregated_content_id']);
            $table->index(['user_id', 'last_read_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_reading_histories');
        Schema::dropIfExists('bookmarks');
        Schema::dropIfExists('personal_notes');
        Schema::dropIfExists('aggregated_content_tag');
        Schema::dropIfExists('tags');
    }
};


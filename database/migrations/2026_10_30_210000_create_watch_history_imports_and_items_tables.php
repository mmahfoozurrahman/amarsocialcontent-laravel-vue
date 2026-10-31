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
        Schema::create('watch_history_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_type', 10); // json, html
            $table->unsignedInteger('total_items_found')->default(0);
            $table->unsignedInteger('items_selected')->default(0);
            $table->string('status', 30)->default('parsed'); // uploaded, parsed, processing, completed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('watch_history_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watch_history_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('youtube_video_id', 32);
            $table->string('youtube_url', 512);
            $table->string('title', 512);
            $table->string('channel_name', 255)->nullable();
            $table->string('channel_url', 512)->nullable();
            $table->string('content_type', 20)->default('video'); // video, short
            $table->timestamp('watched_at')->nullable();
            $table->boolean('is_aggregated')->default(false);
            $table->foreignId('aggregated_content_id')->nullable()->constrained('aggregated_contents')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'youtube_video_id']);
            $table->index(['user_id', 'channel_name']);
            $table->index(['user_id', 'watched_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watch_history_items');
        Schema::dropIfExists('watch_history_imports');
    }
};


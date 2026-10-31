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
        Schema::create('aggregated_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id', 64);
            $table->string('youtube_url', 512);
            $table->string('title', 255);
            $table->string('bengali_title', 255)->nullable();
            $table->string('channel_name', 191)->nullable();
            $table->string('thumbnail_url', 512)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->enum('content_type', ['short', 'video'])->default('video');
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->string('share_token', 64)->nullable()->unique();
            $table->enum('status', [
                'pending',
                'extracting_transcript',
                'processing_transcript',
                'generating_bengali',
                'generating_embeddings',
                'completed',
                'failed'
            ])->default('pending');
            $table->text('error_message')->nullable();
            $table->string('ai_provider', 64)->nullable();
            $table->string('ai_model', 64)->nullable();
            $table->unsignedSmallInteger('reading_time_minutes')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'youtube_video_id']);
        });

        Schema::create('content_transcripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade')->unique();
            $table->longText('full_transcript');
            $table->string('language', 16)->default('en');
            $table->boolean('is_manual')->default(false);
            $table->timestamps();
        });

        Schema::create('content_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade');
            $table->unsignedSmallInteger('chunk_index');
            $table->mediumText('original_text');
            $table->mediumText('translated_text')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->timestamps();

            $table->index(['aggregated_content_id', 'chunk_index']);
        });

        Schema::create('content_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aggregated_content_id')->constrained('aggregated_contents')->onDelete('cascade')->unique();
            $table->text('introduction')->nullable();
            $table->longText('main_discussion');
            $table->text('important_concepts')->nullable();
            $table->text('examples_applications')->nullable();
            $table->text('takeaways')->nullable();
            $table->text('key_terms')->nullable();
            $table->longText('raw_markdown');
            $table->timestamps();

            // Full-text index for natural language search on MySQL
            if (DB::getDriverName() === 'mysql') {
                $table->fullText('raw_markdown');
            }
        });

        Schema::create('ai_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('aggregated_content_id')->nullable()->constrained('aggregated_contents')->onDelete('set null');
            $table->string('provider', 32);
            $table->string('model', 64);
            $table->string('operation_type', 32);
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->enum('status', ['success', 'failed', 'fallback_triggered']);
            $table->text('error_details')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_processing_logs');
        Schema::dropIfExists('content_articles');
        Schema::dropIfExists('content_chunks');
        Schema::dropIfExists('content_transcripts');
        Schema::dropIfExists('aggregated_contents');
    }
};

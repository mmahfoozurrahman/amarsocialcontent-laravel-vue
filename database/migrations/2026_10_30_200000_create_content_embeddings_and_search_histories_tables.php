<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Content Embeddings Table
        Schema::create('content_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aggregated_content_id')
                ->constrained('aggregated_contents')
                ->cascadeOnDelete();
            $table->foreignId('content_chunk_id')
                ->nullable()
                ->constrained('content_chunks')
                ->cascadeOnDelete();
            $table->json('embedding_vector');
            $table->unsignedSmallInteger('dimensions')->default(768);
            $table->string('model_name', 64)->default('text-embedding-004');
            $table->timestamps();

            $table->index('aggregated_content_id');
        });

        // 2. Search Histories Table
        Schema::create('search_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('query', 255);
            $table->enum('search_type', ['hybrid', 'semantic', 'fulltext'])->default('hybrid');
            $table->unsignedInteger('results_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // 3. Ensure FullText index on aggregated_contents if MySQL
        if (DB::getDriverName() === 'mysql') {
            Schema::table('aggregated_contents', function (Blueprint $table) {
                $table->fullText(['title', 'bengali_title'], 'aggregated_contents_titles_fulltext');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('aggregated_contents', function (Blueprint $table) {
                $table->dropIndex('aggregated_contents_titles_fulltext');
            });
        }

        Schema::dropIfExists('search_histories');
        Schema::dropIfExists('content_embeddings');
    }
};


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
        // Drop the foreign keys first so the table/column renames are unhindered.
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropForeign(['chapter_id']);
        });

        Schema::rename('courses', 'collections');
        Schema::rename('chapters', 'sections');
        Schema::rename('lessons', 'notes');

        Schema::table('sections', function (Blueprint $table) {
            $table->renameColumn('course_id', 'collection_id');
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->renameColumn('chapter_id', 'section_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->foreign('collection_id')->references('id')->on('collections')->cascadeOnDelete();
        });
        Schema::table('notes', function (Blueprint $table) {
            $table->foreign('section_id')->references('id')->on('sections')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['section_id']);
        });
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['collection_id']);
        });

        Schema::rename('collections', 'courses');
        Schema::rename('sections', 'chapters');
        Schema::rename('notes', 'lessons');

        Schema::table('chapters', function (Blueprint $table) {
            $table->renameColumn('collection_id', 'course_id');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->renameColumn('section_id', 'chapter_id');
        });

        Schema::table('chapters', function (Blueprint $table) {
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreign('chapter_id')->references('id')->on('chapters')->cascadeOnDelete();
        });
    }
};

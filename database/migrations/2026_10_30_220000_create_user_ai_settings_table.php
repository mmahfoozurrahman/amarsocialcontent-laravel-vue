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
        Schema::create('user_ai_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('primary_provider', 32)->default('groq'); // 'groq' or 'gemini'
            $table->text('groq_api_key')->nullable(); // Encrypted at model level
            $table->text('gemini_api_key')->nullable(); // Encrypted at model level
            $table->string('groq_model', 64)->default('openai/gpt-oss-120b');
            $table->string('gemini_model', 64)->default('gemini-1.5-flash');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ai_settings');
    }
};


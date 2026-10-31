<?php

namespace App\Services\AI\Contracts;

interface AIProviderInterface
{
    /**
     * Generate a comprehensive, mentor-style Bengali educational article from video transcript.
     *
     * @param string $transcript Full transcript text or concatenated chunk text
     * @param array $metadata Video title, channel, duration, format
     * @return array [
     *    'bengali_title' => string,
     *    'introduction' => string,
     *    'main_discussion' => string,
     *    'important_concepts' => string,
     *    'examples_applications' => string,
     *    'takeaways' => string,
     *    'key_terms' => string,
     *    'raw_markdown' => string,
     *    'reading_time_minutes' => int,
     *    'prompt_tokens' => int,
     *    'completion_tokens' => int,
     *    'latency_ms' => int,
     *    'model' => string,
     * ]
     */
    public function generateMentorBengaliArticle(string $transcript, array $metadata): array;

    /**
     * Determine if this provider is configured with valid credentials.
     */
    public function isAvailable(): bool;

    /**
     * Provider unique identifier (e.g. 'gemini', 'groq').
     */
    public function getName(): string;

    /**
     * Active model name.
     */
    public function getModel(): string;
}


<?php

namespace App\Services\AI\Providers;

use App\Services\AI\BengaliPromptBuilder;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiProvider implements AIProviderInterface
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?: config('services.ai.gemini.api_key', env('GEMINI_API_KEY'));
        $this->model = $model ?: config('services.ai.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash'));
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'gemini';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function generateMentorBengaliArticle(string $transcript, array $metadata): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Gemini API key is not configured.');
        }

        $systemPrompt = BengaliPromptBuilder::buildSystemPrompt();
        $userPrompt = BengaliPromptBuilder::buildUserPrompt($transcript, $metadata);

        $startTime = microtime(true);

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemPrompt],
                ],
            ],
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $userPrompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'temperature' => 0.4,
                'maxOutputTokens' => 8192,
            ],
        ];

        $response = Http::timeout(120)->post($endpoint, $payload);
        $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $status = $response->status();
            $errorBody = $response->body();
            throw new RuntimeException("Gemini API Error (HTTP {$status}): {$errorBody}", $status);
        }

        $data = $response->json();
        $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $usage = $data['usageMetadata'] ?? [];

        $promptTokens = $usage['promptTokenCount'] ?? 0;
        $completionTokens = $usage['candidatesTokenCount'] ?? 0;

        $parsedJson = json_decode($rawText, true);
        if (!$parsedJson || !is_array($parsedJson)) {
            // Strip code fences if present
            $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
            $parsedJson = json_decode($cleaned, true);
        }

        if (!$parsedJson || !isset($parsedJson['bengali_title'])) {
            throw new RuntimeException('Gemini failed to return the expected structured JSON.');
        }

        return $this->assembleArticlePayload($parsedJson, $promptTokens, $completionTokens, $latencyMs);
    }

    protected function assembleArticlePayload(array $json, int $promptTokens, int $completionTokens, int $latencyMs): array
    {
        $bengaliTitle = $json['bengali_title'] ?? 'শিক্ষণীয় ভিডিও আলোচনা';
        $intro = $json['introduction'] ?? '';
        $main = $json['main_discussion'] ?? '';
        $concepts = $json['important_concepts'] ?? '';
        $examples = $json['examples_applications'] ?? '';
        $takeaways = $json['takeaways'] ?? '';
        $terms = $json['key_terms'] ?? '';

        // Assemble full markdown document
        $markdown = "# {$bengaliTitle}\n\n";
        if ($intro) {
            $markdown .= "## ভূমিকা ও গুরুত্ব\n{$intro}\n\n";
        }
        if ($main) {
            $markdown .= "## মূল আলোচনা\n{$main}\n\n";
        }
        if ($concepts) {
            $markdown .= "## গুরুত্বপূর্ণ ধারণাসমূহ\n{$concepts}\n\n";
        }
        if ($examples) {
            $markdown .= "## বাস্তব উদাহরণ ও কেইস স্টাডি\n{$examples}\n\n";
        }
        if ($takeaways) {
            $markdown .= "## গুরুত্বপূর্ণ শিক্ষণীয় বিষয় (Takeaways)\n{$takeaways}\n\n";
        }
        if ($terms) {
            $markdown .= "## প্রযুক্তিগত টার্মস ও শব্দকোষ\n{$terms}\n\n";
        }

        // Estimate reading time (average 180 Bengali words per minute)
        $wordCount = count(preg_split('/\s+/u', strip_tags($markdown)));
        $readingTimeMinutes = max(1, (int) ceil($wordCount / 180));

        return [
            'bengali_title' => $bengaliTitle,
            'introduction' => $intro,
            'main_discussion' => $main,
            'important_concepts' => $concepts,
            'examples_applications' => $examples,
            'takeaways' => $takeaways,
            'key_terms' => $terms,
            'raw_markdown' => trim($markdown),
            'reading_time_minutes' => $readingTimeMinutes,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'latency_ms' => $latencyMs,
            'model' => $this->model,
            'provider' => $this->getName(),
        ];
    }
}


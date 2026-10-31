<?php

namespace App\Services\AI\Providers;

use App\Services\AI\BengaliPromptBuilder;
use App\Services\AI\Contracts\AIProviderInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqProvider implements AIProviderInterface
{
    protected ?string $apiKey;
    protected string $model;

    public function __construct(?string $apiKey = null, ?string $model = null)
    {
        $this->apiKey = $apiKey ?: config('services.ai.groq.api_key', env('GROQ_API_KEY'));
        $this->model = $model ?: config('services.ai.groq.model', env('GROQ_MODEL', 'openai/gpt-oss-120b'));
    }

    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }

    public function getName(): string
    {
        return 'groq';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function generateMentorBengaliArticle(string $transcript, array $metadata): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Groq API key is not configured.');
        }

        $systemPrompt = BengaliPromptBuilder::buildSystemPrompt();
        $userPrompt = BengaliPromptBuilder::buildUserPrompt($transcript, $metadata);

        $startTime = microtime(true);

        $endpoint = 'https://api.groq.com/openai/v1/chat/completions';

        $payload = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'response_format' => ['type' => 'json_object'],
            'temperature' => 0.4,
            'max_tokens' => 8192,
        ];

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post($endpoint, $payload);

        $latencyMs = (int) round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $status = $response->status();
            $errorBody = $response->body();
            throw new RuntimeException("Groq API Error (HTTP {$status}): {$errorBody}", $status);
        }

        $data = $response->json();
        $rawText = $data['choices'][0]['message']['content'] ?? '';
        $usage = $data['usage'] ?? [];

        $promptTokens = $usage['prompt_tokens'] ?? 0;
        $completionTokens = $usage['completion_tokens'] ?? 0;

        $parsedJson = json_decode($rawText, true);
        if (!$parsedJson || !is_array($parsedJson)) {
            $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($rawText));
            $parsedJson = json_decode($cleaned, true);
        }

        if (!$parsedJson || !isset($parsedJson['bengali_title'])) {
            throw new RuntimeException('Groq failed to return the expected structured JSON.');
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


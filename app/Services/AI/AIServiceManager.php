<?php

namespace App\Services\AI;

use App\Models\AIProcessingLog;
use App\Models\AggregatedContent;
use App\Models\User;
use App\Services\AI\Contracts\AIProviderInterface;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\GroqProvider;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class AIServiceManager
{
    protected array $providers = [];
    protected string $primaryProvider;
    protected string $fallbackProvider;

    public function __construct(
        ?GeminiProvider $gemini = null,
        ?GroqProvider $groq = null
    ) {
        $this->providers['gemini'] = $gemini ?: new GeminiProvider();
        $this->providers['groq'] = $groq ?: new GroqProvider();

        $this->primaryProvider = config('services.ai.primary', env('AI_PRIMARY_PROVIDER', 'gemini'));
        $this->fallbackProvider = config('services.ai.fallback', env('AI_FALLBACK_PROVIDER', 'groq'));
    }

    public function getProvider(string $name): AIProviderInterface
    {
        if (!isset($this->providers[$name])) {
            throw new RuntimeException("Unknown AI provider: {$name}");
        }
        return $this->providers[$name];
    }

    /**
     * Resolve providers specifically configured for the given user.
     */
    public function resolveProvidersForUser(User $user): array
    {
        $setting = $user->aiSetting;

        // Resolve Groq: user-specific key or fallback to system Groq provider
        if ($setting?->hasGroqKey()) {
            $groqKey = $setting->groq_api_key;
            $groqModel = $setting->groq_model ?: config('services.ai.groq.model', 'openai/gpt-oss-120b');
            $groqProvider = new GroqProvider($groqKey, $groqModel);
        } else {
            $groqProvider = $this->providers['groq'] ?? new GroqProvider();
        }

        // Resolve Gemini: user-specific key or fallback to system Gemini provider
        if ($setting?->hasGeminiKey()) {
            $geminiKey = $setting->gemini_api_key;
            $geminiModel = $setting->gemini_model ?: config('services.ai.gemini.model', 'gemini-3-flash-preview');
            if (in_array($geminiModel, ['gemini-1.5-flash', 'gemini-2.5-flash'])) {
                $geminiModel = 'gemini-3-flash-preview';
            }
            $geminiProvider = new GeminiProvider($geminiKey, $geminiModel);
        } else {
            $geminiProvider = $this->providers['gemini'] ?? new GeminiProvider();
        }

        $primaryChoice = $setting?->primary_provider ?: $this->primaryProvider;
        $fallbackChoice = ($primaryChoice === 'groq') ? 'gemini' : 'groq';

        $primary = ($primaryChoice === 'groq') ? $groqProvider : $geminiProvider;
        $fallback = ($fallbackChoice === 'groq') ? $groqProvider : $geminiProvider;

        // If primary key is empty but fallback is configured, swap to available
        if (!$primary->isAvailable() && $fallback->isAvailable()) {
            $primary = $fallback;
        }

        if (!$primary->isAvailable()) {
            throw new RuntimeException("আপনার অ্যাকাউন্টে কোনো কার্যকর AI API Key (Groq বা Gemini) সেট করা নেই। অনুগ্রহ করে প্রোফাইল সেটিংস থেকে আপনার নিজস্ব এপিআই কী সেট করুন।");
        }

        return [$primary, $fallback];
    }

    /**
     * Synthesize Bengali mentor-style educational article with automated failover.
     */
    public function generateBengaliContent(string $transcript, array $metadata, User $user, ?AggregatedContent $content = null): array
    {
        [$primary, $fallback] = $this->resolveProvidersForUser($user);

        // Step 1: Try Primary Provider
        try {
            $result = $primary->generateMentorBengaliArticle($transcript, $metadata);

            $this->logSuccess($user, $content, $primary, $result);
            return $result;
        } catch (Throwable $primaryException) {
            Log::warning("Primary AI Provider ({$primary->getName()}) failed: " . $primaryException->getMessage(), [
                'user_id' => $user->id,
                'content_id' => $content?->id,
            ]);

            $this->logFallbackTriggered($user, $content, $primary, $primaryException);

            // Step 2: Try Fallback Provider
            if ($fallback->isAvailable() && $fallback->getName() !== $primary->getName()) {
                try {
                    $fallbackResult = $fallback->generateMentorBengaliArticle($transcript, $metadata);

                    $this->logSuccess($user, $content, $fallback, $fallbackResult);
                    return $fallbackResult;
                } catch (Throwable $fallbackException) {
                    Log::error("Fallback AI Provider ({$fallback->getName()}) also failed: " . $fallbackException->getMessage(), [
                        'user_id' => $user->id,
                        'content_id' => $content?->id,
                    ]);

                    $this->logFailure($user, $content, $fallback, $fallbackException);
                    throw new RuntimeException("উভয় এআই সার্ভিস (Gemini ও Groq) অনুরোধ প্রসেস করতে ব্যর্থ হয়েছে: " . $fallbackException->getMessage());
                }
            }

            $this->logFailure($user, $content, $primary, $primaryException);
            throw new RuntimeException("এআই কনটেন্ট জেনারেট করতে সমস্যা হয়েছে: " . $primaryException->getMessage());
        }
    }

    protected function logSuccess(User $user, ?AggregatedContent $content, AIProviderInterface $provider, array $result): void
    {
        AIProcessingLog::create([
            'user_id' => $user->id,
            'aggregated_content_id' => $content?->id,
            'provider' => $provider->getName(),
            'model' => $provider->getModel(),
            'operation_type' => 'generate_article',
            'prompt_tokens' => $result['prompt_tokens'] ?? 0,
            'completion_tokens' => $result['completion_tokens'] ?? 0,
            'latency_ms' => $result['latency_ms'] ?? 0,
            'status' => 'success',
            'error_details' => null,
            'created_at' => now(),
        ]);
    }

    protected function logFallbackTriggered(User $user, ?AggregatedContent $content, AIProviderInterface $provider, Throwable $e): void
    {
        AIProcessingLog::create([
            'user_id' => $user->id,
            'aggregated_content_id' => $content?->id,
            'provider' => $provider->getName(),
            'model' => $provider->getModel(),
            'operation_type' => 'generate_article',
            'status' => 'fallback_triggered',
            'error_details' => $e->getMessage(),
            'created_at' => now(),
        ]);
    }

    protected function logFailure(User $user, ?AggregatedContent $content, AIProviderInterface $provider, Throwable $e): void
    {
        AIProcessingLog::create([
            'user_id' => $user->id,
            'aggregated_content_id' => $content?->id,
            'provider' => $provider->getName(),
            'model' => $provider->getModel(),
            'operation_type' => 'generate_article',
            'status' => 'failed',
            'error_details' => $e->getMessage(),
            'created_at' => now(),
        ]);
    }
}


<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AIProcessingLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIHealthController extends Controller
{
    /**
     * Get AI provider health, active configuration, and failure metrics.
     */
    public function health(Request $request): JsonResponse
    {
        $primaryProvider = config('services.ai.primary_provider', 'groq');
        $fallbackProvider = config('services.ai.fallback_provider', 'gemini');

        $geminiKeyConfigured = !empty(config('services.ai.gemini.api_key'));
        $groqKeyConfigured = !empty(config('services.ai.groq.api_key'));

        $geminiModel = config('services.ai.gemini.model', 'gemini-1.5-flash');
        $groqModel = config('services.ai.groq.model', 'openai/gpt-oss-120b');

        // Total processing logs in database
        $totalCalls = AIProcessingLog::count();
        $successfulCalls = AIProcessingLog::where('status', 'success')->count();
        $fallbackCalls = AIProcessingLog::where('status', 'fallback_triggered')->count();
        $failedCalls = AIProcessingLog::where('status', 'failed')->count();

        // Average latency per provider
        $groqLatency = AIProcessingLog::where('provider', 'groq')
            ->where('status', 'success')
            ->avg('latency_ms');

        $geminiLatency = AIProcessingLog::where('provider', 'gemini')
            ->where('status', 'success')
            ->avg('latency_ms');

        // Total tokens
        $totalPromptTokens = AIProcessingLog::sum('prompt_tokens') ?? 0;
        $totalCompletionTokens = AIProcessingLog::sum('completion_tokens') ?? 0;

        // Provider status: determine if healthy based on the latest 5 attempts
        $recentGeminiLogs = AIProcessingLog::where('provider', 'gemini')->latest()->take(5)->get();
        $recentGeminiFails = $recentGeminiLogs->where('status', 'failed')->count();

        $recentGroqLogs = AIProcessingLog::where('provider', 'groq')->latest()->take(5)->get();
        $recentGroqFails = $recentGroqLogs->where('status', 'failed')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'configuration' => [
                    'primary_provider' => $primaryProvider,
                    'fallback_provider' => $fallbackProvider,
                    'gemini' => [
                        'model' => $geminiModel,
                        'is_configured' => $geminiKeyConfigured,
                        'status' => $geminiKeyConfigured ? ($recentGeminiFails >= 4 ? 'degraded' : 'operational') : 'unconfigured',
                        'avg_latency_ms' => $geminiLatency ? round($geminiLatency) : 0,
                    ],
                    'groq' => [
                        'model' => $groqModel,
                        'is_configured' => $groqKeyConfigured,
                        'status' => $groqKeyConfigured ? ($recentGroqFails >= 4 ? 'degraded' : 'operational') : 'unconfigured',
                        'avg_latency_ms' => $groqLatency ? round($groqLatency) : 0,
                    ],
                ],
                'metrics' => [
                    'total_calls' => $totalCalls,
                    'successful_calls' => $successfulCalls,
                    'fallback_calls' => $fallbackCalls,
                    'failed_calls' => $failedCalls,
                    'success_rate' => $totalCalls > 0 ? round(($successfulCalls / $totalCalls) * 100, 1) : 100,
                    'total_tokens' => [
                        'prompt' => $totalPromptTokens,
                        'completion' => $totalCompletionTokens,
                        'total' => $totalPromptTokens + $totalCompletionTokens,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Get paginated AI processing logs.
     */
    public function logs(Request $request): JsonResponse
    {
        $query = AIProcessingLog::with([
            'user:id,name,email',
            'content:id,title,bengali_title,content_type',
        ]);

        if ($request->filled('provider')) {
            $query->where('provider', $request->input('provider'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('operation')) {
            $query->where('operation_type', $request->input('operation'));
        }

        $logs = $query->latest()->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }
}


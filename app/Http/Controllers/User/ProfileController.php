<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\UserAiSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

class ProfileController extends Controller
{
    /**
     * Get profile information and user's AI configuration.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $setting = $user->aiSetting;

        $globalGroqConfigured = !empty(config('services.ai.groq.api_key'));
        $globalGeminiConfigured = !empty(config('services.ai.gemini.api_key'));

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'is_active' => $user->is_active,
                    'created_at' => $user->created_at->toIso8601String(),
                ],
                'ai_settings' => [
                    'primary_provider' => $setting?->primary_provider ?? 'groq',
                    'groq_configured' => $setting?->hasGroqKey() ?? false,
                    'groq_masked_key' => $setting?->getMaskedGroqKey(),
                    'groq_model' => $setting?->groq_model ?? 'openai/gpt-oss-120b',
                    'gemini_configured' => $setting?->hasGeminiKey() ?? false,
                    'gemini_masked_key' => $setting?->getMaskedGeminiKey(),
                    'gemini_model' => $setting?->gemini_model ?? 'gemini-1.5-flash',
                    'is_ready' => ($setting?->hasAnyKey() || $globalGroqConfigured || $globalGeminiConfigured),
                    'has_own_key' => $setting?->hasAnyKey() ?? false,
                ],
            ],
        ]);
    }

    /**
     * Update user's personal profile (name).
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
        ]);

        $user = $request->user();
        $user->update(['name' => $validated['name']]);

        return response()->json([
            'success' => true,
            'message' => 'প্রোফাইল তথ্য সফলভাবে আপডেট করা হয়েছে।',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Update user's personal encrypted AI API keys and preferences.
     */
    public function updateAiSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'primary_provider' => ['required', 'in:groq,gemini'],
            'groq_api_key' => ['nullable', 'string'],
            'gemini_api_key' => ['nullable', 'string'],
            'groq_model' => ['nullable', 'string', 'max:64'],
            'gemini_model' => ['nullable', 'string', 'max:64'],
            'clear_groq' => ['nullable', 'boolean'],
            'clear_gemini' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $setting = $user->aiSetting ?: new UserAiSetting(['user_id' => $user->id]);

        $setting->primary_provider = $validated['primary_provider'];
        if (!empty($validated['groq_model'])) {
            $setting->groq_model = $validated['groq_model'];
        }
        if (!empty($validated['gemini_model'])) {
            $setting->gemini_model = $validated['gemini_model'];
        }

        // Handle Groq key update or clearing
        if (!empty($validated['clear_groq'])) {
            $setting->groq_api_key = null;
        } elseif (!empty($validated['groq_api_key'])) {
            $setting->groq_api_key = trim($validated['groq_api_key']);
        }

        // Handle Gemini key update or clearing
        if (!empty($validated['clear_gemini'])) {
            $setting->gemini_api_key = null;
        } elseif (!empty($validated['gemini_api_key'])) {
            $setting->gemini_api_key = trim($validated['gemini_api_key']);
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'message' => 'আপনার এআই এপিআই কী ও সেটিংস সুরক্ষিতভাবে (Encrypted) সংরক্ষিত হয়েছে!',
            'data' => [
                'primary_provider' => $setting->primary_provider,
                'groq_configured' => $setting->hasGroqKey(),
                'groq_masked_key' => $setting->getMaskedGroqKey(),
                'groq_model' => $setting->groq_model,
                'gemini_configured' => $setting->hasGeminiKey(),
                'gemini_masked_key' => $setting->getMaskedGeminiKey(),
                'gemini_model' => $setting->gemini_model,
                'has_own_key' => $setting->hasAnyKey(),
            ],
        ]);
    }

    /**
     * Test an API Key on the fly to verify validity.
     */
    public function testAiConnection(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:groq,gemini'],
            'api_key' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $setting = $user->aiSetting;

        $provider = $validated['provider'];
        $apiKey = $validated['api_key'];

        // If key not provided in payload, fallback to user's saved key
        if (empty($apiKey)) {
            $apiKey = ($provider === 'groq') ? $setting?->groq_api_key : $setting?->gemini_api_key;
        }

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'পরীক্ষা করার জন্য অনুগ্রহ করে একটি এপিআই কী (API Key) লিখুন।',
            ], 422);
        }

        try {
            if ($provider === 'groq') {
                $response = Http::withToken($apiKey)
                    ->timeout(8)
                    ->get('https://api.groq.com/openai/v1/models');

                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Groq API Key সফলভাবে যাচাই হয়েছে! সার্ভিস সক্রিয় ও প্রস্তুত রয়েছে।',
                    ]);
                }

                $err = $response->json('error.message') ?? 'অকার্যকর বা ভুল Groq API Key।';
                return response()->json([
                    'success' => false,
                    'message' => "Groq ভেরিফিকেশন ব্যর্থ হয়েছে: {$err}",
                ], 422);
            }

            if ($provider === 'gemini') {
                $response = Http::timeout(8)
                    ->get("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");

                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Google Gemini API Key সফলভাবে যাচাই হয়েছে! সার্ভিস সক্রিয় ও প্রস্তুত রয়েছে।',
                    ]);
                }

                $err = $response->json('error.message') ?? 'অকার্যকর বা ভুল Gemini API Key।';
                return response()->json([
                    'success' => false,
                    'message' => "Gemini ভেরিফিকেশন ব্যর্থ হয়েছে: {$err}",
                ], 422);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => "কানেকশন এরর: {$e->getMessage()}",
            ], 422);
        }

        return response()->json(['success' => false, 'message' => 'অসমর্থিত প্রোভাইডার।'], 422);
    }
}


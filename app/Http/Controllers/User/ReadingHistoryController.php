<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AggregatedContent;
use App\Models\ContentReadingHistory;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReadingHistoryController extends Controller
{
    /**
     * Record study time and mark completed status for an article.
     */
    public function updateProgress(Request $request, int $contentId): JsonResponse
    {
        $content = AggregatedContent::findOrFail($contentId);
        Gate::authorize('view', $content);

        $validated = $request->validate([
            'additional_seconds' => ['nullable', 'integer', 'min:1', 'max:7200'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        $userId = $request->user()->id;
        $additionalSeconds = $validated['additional_seconds'] ?? 0;

        $history = ContentReadingHistory::firstOrNew([
            'user_id' => $userId,
            'aggregated_content_id' => $content->id,
        ]);

        $history->seconds_spent = ($history->seconds_spent ?? 0) + $additionalSeconds;
        $history->last_read_at = Carbon::now();

        if (isset($validated['is_completed'])) {
            $history->is_completed = (bool) $validated['is_completed'];
        }

        if (!$history->exists) {
            $history->view_count = 1;
        }

        $history->save();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Get learning timeline analytics (e.g. 7-day study stats and streak).
     */
    public function getTimeline(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $sevenDaysAgo = Carbon::now()->subDays(7)->startOfDay();

        $recentReads = ContentReadingHistory::where('user_id', $userId)
            ->where('last_read_at', '>=', $sevenDaysAgo)
            ->with(['content' => fn ($q) => $q->select('id', 'title', 'bengali_title', 'thumbnail_url', 'content_type')])
            ->orderByDesc('last_read_at')
            ->get();

        $totalSeconds7Days = $recentReads->sum('seconds_spent');
        $completedCount = ContentReadingHistory::where('user_id', $userId)->where('is_completed', true)->count();
        $totalArticlesStudied = ContentReadingHistory::where('user_id', $userId)->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_seconds_last_7_days' => $totalSeconds7Days,
                'total_minutes_last_7_days' => (int) round($totalSeconds7Days / 60),
                'completed_articles_count' => $completedCount,
                'total_articles_studied' => $totalArticlesStudied,
                'recent_reads' => $recentReads,
            ],
        ]);
    }
}


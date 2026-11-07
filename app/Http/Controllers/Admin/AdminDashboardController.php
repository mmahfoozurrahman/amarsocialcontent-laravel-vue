<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AggregatedContent;
use App\Models\Collection;
use App\Models\ContentReadingHistory;
use App\Models\Note;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Get platform overview statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $activeUsers = User::where('is_active', true)->count();

        $totalContents = AggregatedContent::count();
        $completedContents = AggregatedContent::where('status', 'completed')->count();
        $pendingContents = AggregatedContent::whereIn('status', [
            'pending', 'extracting_transcript', 'processing_transcript', 'generating_bengali', 'generating_embeddings',
        ])->count();
        $failedContents = AggregatedContent::where('status', 'failed')->count();

        // Format Breakdown
        $formatBreakdown = AggregatedContent::select('content_type', DB::raw('count(*) as count'))
            ->groupBy('content_type')
            ->pluck('count', 'content_type')
            ->toArray();

        // Total study time logged in minutes (from seconds_spent)
        $totalStudySeconds = ContentReadingHistory::sum('seconds_spent') ?? 0;
        $totalStudyMinutes = round($totalStudySeconds / 60);

        // Second Brain metrics
        $totalCollections = Collection::count();
        $totalNotes = Note::count();

        // Recent 5 users
        $recentUsers = User::select(['id', 'name', 'email', 'role', 'is_active', 'created_at'])
            ->latest()
            ->take(5)
            ->get();

        // Log admin dashboard inspection in audit trail
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'admin_view_dashboard',
            'entity_type' => 'system',
            'entity_id' => null,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr($request->userAgent() ?? '', 0, 250),
            'details' => ['timestamp' => now()->toIso8601String()],
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'users' => [
                    'total' => $totalUsers,
                    'active' => $activeUsers,
                    'admins' => $adminCount,
                    'recent' => $recentUsers,
                ],
                'contents' => [
                    'total' => $totalContents,
                    'completed' => $completedContents,
                    'pending' => $pendingContents,
                    'failed' => $failedContents,
                    'completion_rate' => $totalContents > 0 ? round(($completedContents / $totalContents) * 100, 1) : 0,
                    'format_breakdown' => [
                        'video' => $formatBreakdown['video'] ?? 0,
                        'short' => $formatBreakdown['short'] ?? 0,
                    ],
                ],
                'learning' => [
                    'total_study_minutes' => $totalStudyMinutes,
                    'total_collections' => $totalCollections,
                    'total_notes' => $totalNotes,
                ],
            ],
        ]);
    }
}

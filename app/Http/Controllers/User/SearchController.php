<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SearchHistory;
use App\Services\Search\SemanticSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __construct(
        protected SemanticSearchService $searchService
    ) {}

    /**
     * Execute user-scoped hybrid / semantic / fulltext search.
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:hybrid,semantic,fulltext'],
            'content_type' => ['nullable', 'in:all,video,short'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = $validated['q'] ?? '';
        $options = [
            'search_type' => $validated['type'] ?? 'hybrid',
            'content_type' => $validated['content_type'] ?? 'all',
            'limit' => $validated['limit'] ?? 20,
        ];

        $results = $this->searchService->search($request->user(), $query, $options);

        return response()->json([
            'success' => true,
            'data' => $results['results'],
            'meta' => [
                'query' => $results['query'],
                'total' => $results['total'],
                'search_type' => $results['search_type'],
            ],
        ]);
    }

    /**
     * Get recent search queries for the user.
     */
    public function history(Request $request): JsonResponse
    {
        $history = $request->user()
            ->searchHistories()
            ->select(['id', 'query', 'search_type', 'results_count', 'created_at'])
            ->latest()
            ->take(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $history,
        ]);
    }

    /**
     * Clear all search queries for the user.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        $request->user()->searchHistories()->delete();

        return response()->json([
            'success' => true,
            'message' => 'সার্চ হিস্ট্রি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }
}


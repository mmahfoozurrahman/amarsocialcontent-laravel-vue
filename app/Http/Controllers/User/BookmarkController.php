<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\AggregatedContentResource;
use App\Models\AggregatedContent;
use App\Models\Bookmark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    /**
     * List user's bookmarked articles.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $bookmarkedContentIds = Bookmark::where('user_id', $request->user()->id)
            ->pluck('aggregated_content_id');

        $articles = AggregatedContent::whereIn('id', $bookmarkedContentIds)
            ->with(['tags', 'article'])
            ->latest()
            ->paginate(12);

        return AggregatedContentResource::collection($articles);
    }

    /**
     * Toggle bookmark state for a content.
     */
    public function toggle(Request $request, int $contentId): JsonResponse
    {
        $content = AggregatedContent::findOrFail($contentId);
        Gate::authorize('view', $content);

        $userId = $request->user()->id;

        $existing = Bookmark::where('user_id', $userId)
            ->where('aggregated_content_id', $content->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $isBookmarked = false;
            $message = 'বুকমার্ক সরানো হয়েছে।';
        } else {
            Bookmark::create([
                'user_id' => $userId,
                'aggregated_content_id' => $content->id,
            ]);
            $isBookmarked = true;
            $message = 'বুকমার্ক যুক্ত হয়েছে।';
        }

        return response()->json([
            'success' => true,
            'is_bookmarked' => $isBookmarked,
            'message' => $message,
        ]);
    }
}


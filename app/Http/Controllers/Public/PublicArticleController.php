<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicArticleResource;
use App\Models\AggregatedContent;
use Illuminate\Http\JsonResponse;

class PublicArticleController extends Controller
{
    /**
     * Display a published article publicly using the cryptographic share token.
     */
    public function show(string $shareToken): JsonResponse
    {
        $article = AggregatedContent::where('share_token', $shareToken)
            ->where('visibility', 'public')
            ->where('status', 'completed')
            ->with(['article', 'tags'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => new PublicArticleResource($article),
        ]);
    }
}


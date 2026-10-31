<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AggregatedContent;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class TagController extends Controller
{
    /**
     * List all tags owned by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $tags = $request->user()->tags()
            ->withCount('aggregatedContents')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Create a new tag for the user.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
        ]);

        $name = trim($validated['name']);
        $slug = Str::slug($name);

        $tag = Tag::firstOrCreate(
            [
                'user_id' => $request->user()->id,
                'slug' => $slug,
            ],
            [
                'name' => $name,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'ট্যাগ সফলভাবে তৈরি হয়েছে।',
            'data' => $tag,
        ], 201);
    }

    /**
     * Update an existing tag.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        Gate::authorize('update', $tag);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64'],
        ]);

        $name = trim($validated['name']);
        $tag->update([
            'name' => $name,
            'slug' => Str::slug($name),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'ট্যাগ আপডেট সম্পন্ন হয়েছে।',
            'data' => $tag,
        ]);
    }

    /**
     * Delete a tag.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $tag = Tag::findOrFail($id);
        Gate::authorize('delete', $tag);

        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'ট্যাগটি মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Sync tags for a specific article.
     */
    public function syncArticleTags(Request $request, int $contentId): JsonResponse
    {
        $content = AggregatedContent::findOrFail($contentId);
        Gate::authorize('update', $content);

        $validated = $request->validate([
            'tags' => ['array'],
            'tags.*' => ['string', 'max:64'],
        ]);

        $user = $request->user();
        $tagIds = [];

        foreach ($validated['tags'] ?? [] as $tagName) {
            $cleaned = trim($tagName);
            if (!empty($cleaned)) {
                $tag = Tag::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'slug' => Str::slug($cleaned),
                    ],
                    [
                        'name' => $cleaned,
                    ]
                );
                $tagIds[] = $tag->id;
            }
        }

        $content->tags()->sync($tagIds);

        return response()->json([
            'success' => true,
            'message' => 'ট্যাগ সফলভাবে যুক্ত করা হয়েছে।',
            'data' => $content->fresh('tags')->tags,
        ]);
    }
}


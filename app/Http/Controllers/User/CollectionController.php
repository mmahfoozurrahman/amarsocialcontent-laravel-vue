<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CollectionController extends Controller
{
    /**
     * Display a listing of the user's collections.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $collections = $request->user()->collections()
            ->with(['sections.notes'])
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(12);

        return CollectionResource::collection($collections);
    }

    /**
     * Store a newly created collection.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:private,public'],
            'cover_image' => ['nullable', 'string', 'max:512'],
        ]);

        $maxSort = $request->user()->collections()->max('sort_order') ?? 0;

        $collection = Collection::create([
            'user_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'] ?? 'private',
            'cover_image' => $validated['cover_image'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'কালেকশনস সফলভাবে তৈরি হয়েছে।',
            'data' => new CollectionResource($collection),
        ], 201);
    }

    /**
     * Display the specified collection with its full hierarchy.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $collection = Collection::with([
            'sections' => function ($q) {
                $q->orderBy('sort_order')->with([
                    'notes' => function ($lq) {
                        $lq->orderBy('sort_order')->with('aggregatedContent.article');
                    },
                ]);
            },
        ])->findOrFail($id);

        Gate::authorize('view', $collection);

        return response()->json([
            'success' => true,
            'data' => new CollectionResource($collection),
        ]);
    }

    /**
     * Update the specified collection.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        Gate::authorize('update', $collection);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:private,public'],
            'cover_image' => ['nullable', 'string', 'max:512'],
        ]);

        $collection->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'কালেকশনস আপডেট সম্পন্ন হয়েছে।',
            'data' => new CollectionResource($collection),
        ]);
    }

    /**
     * Remove the specified collection.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $collection = Collection::findOrFail($id);
        Gate::authorize('delete', $collection);

        $collection->delete();

        return response()->json([
            'success' => true,
            'message' => 'কালেকশনসটি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Reorder user's collections.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collection_ids' => ['required', 'array'],
            'collection_ids.*' => ['integer'],
        ]);

        $userId = $request->user()->id;

        foreach ($validated['collection_ids'] as $index => $collectionId) {
            Collection::where('id', $collectionId)
                ->where('user_id', $userId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'কালেকশনসের ক্রম সফলভাবে আপডেট হয়েছে।',
        ]);
    }
}


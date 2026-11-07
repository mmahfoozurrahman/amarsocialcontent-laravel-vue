<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionResource;
use App\Models\Section;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SectionController extends Controller
{
    /**
     * Store a newly created section inside a collection.
     */
    public function store(Request $request, int $collectionId): JsonResponse
    {
        $collection = Collection::findOrFail($collectionId);
        Gate::authorize('update', $collection);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $maxSort = $collection->sections()->max('sort_order') ?? 0;

        $section = Section::create([
            'collection_id' => $collection->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'সেকশনস সফলভাবে তৈরি হয়েছে।',
            'data' => new SectionResource($section->load('notes')),
        ], 201);
    }

    /**
     * Update the specified section.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $section = Section::findOrFail($id);
        Gate::authorize('update', $section);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $section->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'সেকশনস আপডেট সম্পন্ন হয়েছে।',
            'data' => new SectionResource($section),
        ]);
    }

    /**
     * Remove the specified section.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $section = Section::findOrFail($id);
        Gate::authorize('delete', $section);

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'সেকশনসটি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Reorder sections within a collection.
     */
    public function reorder(Request $request, int $collectionId): JsonResponse
    {
        $collection = Collection::findOrFail($collectionId);
        Gate::authorize('update', $collection);

        $validated = $request->validate([
            'section_ids' => ['required', 'array'],
            'section_ids.*' => ['integer'],
        ]);

        foreach ($validated['section_ids'] as $index => $sectionId) {
            Section::where('id', $sectionId)
                ->where('collection_id', $collection->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'সেকশনসের ক্রম সফলভাবে আপডেট হয়েছে।',
        ]);
    }
}


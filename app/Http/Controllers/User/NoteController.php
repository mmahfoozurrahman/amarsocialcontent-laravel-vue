<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\AggregatedContentResource;
use App\Http\Resources\NoteResource;
use App\Models\AggregatedContent;
use App\Models\Note;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class NoteController extends Controller
{
    /**
     * Store a newly created note inside a section.
     */
    public function store(Request $request, int $sectionId): JsonResponse
    {
        $section = Section::with('collection')->findOrFail($sectionId);
        Gate::authorize('update', $section);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'aggregated_content_id' => ['nullable', 'integer', 'exists:aggregated_contents,id'],
            'visibility' => ['nullable', 'in:private,public'],
        ]);

        // CRITICAL TENANT CHECK: If linking an aggregated article, ensure caller owns it!
        if (! empty($validated['aggregated_content_id'])) {
            $article = AggregatedContent::findOrFail($validated['aggregated_content_id']);
            if ($article->user_id !== $request->user()->id) {
                throw ValidationException::withMessages([
                    'aggregated_content_id' => ['আপনি শুধুমাত্র আপনার নিজের সংরক্ষিত আর্টিকেল নোটসে যুক্ত করতে পারবেন।'],
                ]);
            }
        }

        $maxSort = $section->notes()->max('sort_order') ?? 0;

        $note = Note::create([
            'section_id' => $section->id,
            'aggregated_content_id' => $validated['aggregated_content_id'] ?? null,
            'title' => trim($validated['title']),
            'content' => $validated['content'] ?? null,
            'visibility' => $validated['visibility'] ?? 'private',
            'sort_order' => $maxSort + 1,
            'is_completed' => false,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'নোটস সফলভাবে তৈরি হয়েছে।',
            'data' => new NoteResource($note->load('aggregatedContent.article')),
        ], 201);
    }

    /**
     * Display the specified note with collection hierarchy and content.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $note = Note::with([
            'section.collection.sections.notes',
            'aggregatedContent.article',
            'aggregatedContent.tags',
        ])->findOrFail($id);

        Gate::authorize('view', $note);

        return response()->json([
            'success' => true,
            'data' => new NoteResource($note),
        ]);
    }

    /**
     * Update the specified note.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $note = Note::with('section.collection')->findOrFail($id);
        Gate::authorize('update', $note);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'aggregated_content_id' => ['nullable', 'integer', 'exists:aggregated_contents,id'],
            'section_id' => ['sometimes', 'integer', 'exists:sections,id'],
            'visibility' => ['nullable', 'in:private,public'],
            'is_completed' => ['nullable', 'boolean'],
        ]);

        // If section_id is being moved, verify caller owns destination section
        if (! empty($validated['section_id']) && $validated['section_id'] != $note->section_id) {
            $targetSection = Section::with('collection')->findOrFail($validated['section_id']);
            Gate::authorize('update', $targetSection);
        }

        // CRITICAL TENANT CHECK: If changing linked article, verify ownership!
        if (array_key_exists('aggregated_content_id', $validated) && ! empty($validated['aggregated_content_id'])) {
            $article = AggregatedContent::findOrFail($validated['aggregated_content_id']);
            if ($article->user_id !== $request->user()->id) {
                throw ValidationException::withMessages([
                    'aggregated_content_id' => ['আপনি শুধুমাত্র আপনার নিজের সংরক্ষিত আর্টিকেল নোটসে যুক্ত করতে পারবেন।'],
                ]);
            }
        }

        $note->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'নোটস আপডেট সম্পন্ন হয়েছে।',
            'data' => new NoteResource($note->fresh(['aggregatedContent.article'])),
        ]);
    }

    /**
     * Remove the specified note.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $note = Note::with('section.collection')->findOrFail($id);
        Gate::authorize('delete', $note);

        $note->delete();

        return response()->json([
            'success' => true,
            'message' => 'নোটসটি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Reorder notes within a section.
     */
    public function reorder(Request $request, int $sectionId): JsonResponse
    {
        $section = Section::with('collection')->findOrFail($sectionId);
        Gate::authorize('update', $section);

        $validated = $request->validate([
            'note_ids' => ['required', 'array'],
            'note_ids.*' => ['integer'],
        ]);

        foreach ($validated['note_ids'] as $index => $noteId) {
            Note::where('id', $noteId)
                ->where('section_id', $section->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'নোটসের ক্রম সফলভাবে আপডেট হয়েছে।',
        ]);
    }

    /**
     * List user's articles not yet assigned to any note.
     */
    public function unassignedArticles(Request $request): JsonResponse
    {
        $assignedIds = Note::whereNotNull('aggregated_content_id')->pluck('aggregated_content_id');

        $unassigned = $request->user()->aggregatedContents()
            ->where('status', 'completed')
            ->whereNotIn('id', $assignedIds)
            ->with('article')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => AggregatedContentResource::collection($unassigned),
        ]);
    }
}

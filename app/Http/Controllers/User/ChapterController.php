<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\ChapterResource;
use App\Models\Chapter;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ChapterController extends Controller
{
    /**
     * Store a newly created chapter inside a course.
     */
    public function store(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $maxSort = $course->chapters()->max('sort_order') ?? 0;

        $chapter = Chapter::create([
            'course_id' => $course->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'চ্যাপ্টার সফলভাবে তৈরি হয়েছে।',
            'data' => new ChapterResource($chapter->load('lessons')),
        ], 201);
    }

    /**
     * Update the specified chapter.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $chapter = Chapter::findOrFail($id);
        Gate::authorize('update', $chapter);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $chapter->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'চ্যাপ্টার আপডেট সম্পন্ন হয়েছে।',
            'data' => new ChapterResource($chapter),
        ]);
    }

    /**
     * Remove the specified chapter.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $chapter = Chapter::findOrFail($id);
        Gate::authorize('delete', $chapter);

        $chapter->delete();

        return response()->json([
            'success' => true,
            'message' => 'চ্যাপ্টারটি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Reorder chapters within a course.
     */
    public function reorder(Request $request, int $courseId): JsonResponse
    {
        $course = Course::findOrFail($courseId);
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'chapter_ids' => ['required', 'array'],
            'chapter_ids.*' => ['integer'],
        ]);

        foreach ($validated['chapter_ids'] as $index => $chapterId) {
            Chapter::where('id', $chapterId)
                ->where('course_id', $course->id)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'চ্যাপ্টারের ক্রম সফলভাবে আপডেট হয়েছে।',
        ]);
    }
}


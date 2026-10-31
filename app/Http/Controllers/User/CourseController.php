<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class CourseController extends Controller
{
    /**
     * Display a listing of the user's courses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $courses = $request->user()->courses()
            ->with(['chapters.lessons'])
            ->orderBy('sort_order')
            ->latest('id')
            ->paginate(12);

        return CourseResource::collection($courses);
    }

    /**
     * Store a newly created course.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:private,public'],
            'cover_image' => ['nullable', 'string', 'max:512'],
        ]);

        $maxSort = $request->user()->courses()->max('sort_order') ?? 0;

        $course = Course::create([
            'user_id' => $request->user()->id,
            'title' => trim($validated['title']),
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'] ?? 'private',
            'cover_image' => $validated['cover_image'] ?? null,
            'sort_order' => $maxSort + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'কোর্স সফলভাবে তৈরি হয়েছে।',
            'data' => new CourseResource($course),
        ], 201);
    }

    /**
     * Display the specified course with its full hierarchy.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $course = Course::with([
            'chapters' => function ($q) {
                $q->orderBy('sort_order')->with([
                    'lessons' => function ($lq) {
                        $lq->orderBy('sort_order')->with('aggregatedContent.article');
                    },
                ]);
            },
        ])->findOrFail($id);

        Gate::authorize('view', $course);

        return response()->json([
            'success' => true,
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Update the specified course.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        Gate::authorize('update', $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'visibility' => ['nullable', 'in:private,public'],
            'cover_image' => ['nullable', 'string', 'max:512'],
        ]);

        $course->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'কোর্স আপডেট সম্পন্ন হয়েছে।',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Remove the specified course.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $course = Course::findOrFail($id);
        Gate::authorize('delete', $course);

        $course->delete();

        return response()->json([
            'success' => true,
            'message' => 'কোর্সটি সফলভাবে মুছে ফেলা হয়েছে।',
        ]);
    }

    /**
     * Reorder user's courses.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'course_ids' => ['required', 'array'],
            'course_ids.*' => ['integer'],
        ]);

        $userId = $request->user()->id;

        foreach ($validated['course_ids'] as $index => $courseId) {
            Course::where('id', $courseId)
                ->where('user_id', $userId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json([
            'success' => true,
            'message' => 'কোর্সের ক্রম সফলভাবে আপডেট হয়েছে।',
        ]);
    }
}


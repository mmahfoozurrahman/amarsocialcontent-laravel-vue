<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AggregatedContent;
use App\Models\PersonalNote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PersonalNoteController extends Controller
{
    /**
     * Create or update a private note for a specific content.
     */
    public function updateOrCreate(Request $request, int $contentId): JsonResponse
    {
        $content = AggregatedContent::findOrFail($contentId);
        Gate::authorize('view', $content);

        $validated = $request->validate([
            'note_content' => ['required', 'string', 'max:50000'],
        ]);

        $note = PersonalNote::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'aggregated_content_id' => $content->id,
            ],
            [
                'note_content' => $validated['note_content'],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'ব্যক্তিগত নোট সফলভাবে সংরক্ষিত হয়েছে।',
            'data' => $note,
        ]);
    }

    /**
     * Delete a private note.
     */
    public function destroy(Request $request, int $contentId): JsonResponse
    {
        $content = AggregatedContent::findOrFail($contentId);
        Gate::authorize('view', $content);

        PersonalNote::where('user_id', $request->user()->id)
            ->where('aggregated_content_id', $content->id)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'নোট মুছে ফেলা হয়েছে।',
        ]);
    }
}


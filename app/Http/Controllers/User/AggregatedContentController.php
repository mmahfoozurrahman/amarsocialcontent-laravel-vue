<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\ManualTranscriptRequest;
use App\Http\Requests\User\StoreYouTubeContentRequest;
use App\Http\Resources\AggregatedContentResource;
use App\Jobs\ProcessYouTubeContentJob;
use App\Models\AggregatedContent;
use App\Models\ContentTranscript;
use App\Services\BengaliContentGeneratorService;
use App\Services\YouTubeTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class AggregatedContentController extends Controller
{
    /**
     * Display a listing of the user's aggregated contents.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = $request->query('status');
        $contentType = $request->query('content_type');

        $query = $request->user()->aggregatedContents()->with(['article'])->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($contentType) {
            $query->where('content_type', $contentType);
        }

        $items = $query->paginate(12);

        return AggregatedContentResource::collection($items);
    }

    /**
     * Store and initiate aggregation for a YouTube URL.
     */
    public function store(
        StoreYouTubeContentRequest $request,
        YouTubeTranscriptService $youtubeService,
        BengaliContentGeneratorService $generatorService
    ): JsonResponse {
        $validated = $request->validated();
        $user = $request->user();

        // Parse YouTube URL
        $parsed = $youtubeService->parseYouTubeUrl($validated['youtube_url']);
        $videoId = $parsed['video_id'];
        $contentType = $parsed['content_type'];

        // Check if video is already saved by this user
        $existing = AggregatedContent::where('user_id', $user->id)
            ->where('youtube_video_id', $videoId)
            ->first();

        if ($existing && $existing->isCompleted()) {
            return response()->json([
                'success' => true,
                'message' => 'এই ভিডিওটি ইতিমধ্যে আপনার লাইব্রেরিতে সংরক্ষিত রয়েছে।',
                'data' => new AggregatedContentResource($existing->load('article')),
                'is_duplicate' => true,
            ], 200);
        }

        // Fetch oEmbed metadata
        $metadata = $youtubeService->fetchMetadata($videoId, $contentType);

        $manualTranscript = $validated['manual_transcript'] ?? null;

        $content = AggregatedContent::updateOrCreate(
            [
                'user_id' => $user->id,
                'youtube_video_id' => $videoId,
            ],
            [
                'youtube_url' => $parsed['clean_url'],
                'title' => $metadata['title'],
                'channel_name' => $metadata['channel_name'],
                'thumbnail_url' => $metadata['thumbnail_url'],
                'content_type' => $contentType,
                'status' => 'pending',
                'error_message' => null,
            ]
        );

        // Automatically tag with channel name by default
        if (!empty($metadata['channel_name'])) {
            $channelTagName = trim($metadata['channel_name']);
            $tag = \App\Models\Tag::firstOrCreate(
                ['user_id' => $user->id, 'name' => $channelTagName],
                ['slug' => \Illuminate\Support\Str::slug($channelTagName)]
            );
            $content->tags()->syncWithoutDetaching([$tag->id]);
        }

        if ($manualTranscript) {
            ContentTranscript::updateOrCreate(
                ['aggregated_content_id' => $content->id],
                [
                    'full_transcript' => $manualTranscript,
                    'language' => 'manual',
                    'is_manual' => true,
                ]
            );
        }

        // Dispatch background processing job
        if (config('queue.default') === 'sync') {
            ProcessYouTubeContentJob::dispatchSync($content, $manualTranscript);
        } else {
            ProcessYouTubeContentJob::dispatch($content, $manualTranscript);
        }

        return response()->json([
            'success' => true,
            'message' => 'ভিডিও বিশ্লেষণ ও বাংলা আর্টিকেলে রূপান্তরের কাজ শুরু হয়েছে।',
            'data' => new AggregatedContentResource($content->fresh(['article', 'transcript'])),
        ], 201);
    }

    /**
     * Display the specified aggregated content.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $content = AggregatedContent::with(['article', 'transcript', 'chunks', 'tags'])->findOrFail($id);

        Gate::authorize('view', $content);

        // Record or increment view in reading histories
        if ($request->user()) {
            $history = \App\Models\ContentReadingHistory::firstOrNew([
                'user_id' => $request->user()->id,
                'aggregated_content_id' => $content->id,
            ]);
            $history->view_count = ($history->view_count ?? 0) + 1;
            $history->last_read_at = now();
            $history->save();
        }

        return response()->json([
            'success' => true,
            'data' => new AggregatedContentResource($content),
        ]);
    }

    /**
     * Update title, markdown content, or visibility.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $content = AggregatedContent::findOrFail($id);
        Gate::authorize('update', $content);

        $validated = $request->validate([
            'bengali_title' => ['nullable', 'string', 'max:255'],
            'visibility' => ['nullable', 'in:private,public'],
            'raw_markdown' => ['nullable', 'string'],
        ]);

        if (isset($validated['bengali_title'])) {
            $content->bengali_title = $validated['bengali_title'];
        }

        if (isset($validated['visibility'])) {
            $content->visibility = $validated['visibility'];
            if ($validated['visibility'] === 'public') {
                $content->ensureShareToken();
            }
        }

        $content->save();

        if (isset($validated['raw_markdown']) && $content->article) {
            $content->article->update([
                'raw_markdown' => $validated['raw_markdown'],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'আর্টিকেল সফলভাবে আপডেট করা হয়েছে।',
            'data' => new AggregatedContentResource($content->fresh(['article', 'tags', 'transcript'])),
        ]);
    }

    /**
     * Manually submit a transcript when auto-extraction is unavailable.
     */
    public function submitManualTranscript(
        ManualTranscriptRequest $request,
        int $id,
        BengaliContentGeneratorService $generatorService
    ): JsonResponse {
        $content = AggregatedContent::findOrFail($id);

        Gate::authorize('update', $content);

        $transcriptText = trim($request->validated()['transcript']);

        // 1. Immediately save transcript record
        ContentTranscript::updateOrCreate(
            ['aggregated_content_id' => $content->id],
            [
                'full_transcript' => $transcriptText,
                'language' => 'manual',
                'is_manual' => true,
            ]
        );

        $content->update([
            'status' => 'processing_transcript',
            'error_message' => null,
        ]);

        // 2. Dispatch background processing job
        if (config('queue.default') === 'sync') {
            ProcessYouTubeContentJob::dispatchSync($content, $transcriptText);
            $content->refresh();
        } else {
            ProcessYouTubeContentJob::dispatch($content, $transcriptText);
        }

        return response()->json([
            'success' => true,
            'message' => 'ম্যানুয়াল ট্রান্সক্রিপ্ট সফলভাবে গ্রহণ করা হয়েছে এবং এআই বিশ্লেষণ শুরু হয়েছে।',
            'data' => new AggregatedContentResource($content->fresh(['article', 'transcript'])),
        ]);
    }

    /**
     * Retry failed content generation.
     */
    public function retry(
        Request $request,
        int $id,
        YouTubeTranscriptService $youtubeService,
        BengaliContentGeneratorService $generatorService
    ): JsonResponse {
        $content = AggregatedContent::findOrFail($id);

        Gate::authorize('retry', $content);

        $content->update(['status' => 'pending', 'error_message' => null]);

        if (config('queue.default') === 'sync') {
            ProcessYouTubeContentJob::dispatchSync($content);
        } else {
            ProcessYouTubeContentJob::dispatch($content);
        }

        return response()->json([
            'success' => true,
            'message' => 'পুনরায় প্রসেসিং শুরু হয়েছে।',
            'data' => new AggregatedContentResource($content->fresh(['article', 'transcript'])),
        ]);
    }

    /**
     * Delete an aggregated content.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $content = AggregatedContent::findOrFail($id);

        Gate::authorize('delete', $content);

        $content->delete();

        return response()->json([
            'success' => true,
            'message' => 'কনটেন্টটি সফলভাবে লাইব্রেরি থেকে মুছে ফেলা হয়েছে।',
        ]);
    }
}

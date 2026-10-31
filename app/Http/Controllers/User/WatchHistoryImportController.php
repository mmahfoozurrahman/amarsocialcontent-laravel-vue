<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessYouTubeContentJob;
use App\Models\AggregatedContent;
use App\Models\Tag;
use App\Models\WatchHistoryImport;
use App\Models\WatchHistoryItem;
use App\Services\Import\GoogleTakeoutWatchHistoryParser;
use App\Services\YouTubeTranscriptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WatchHistoryImportController extends Controller
{
    public function __construct(
        protected GoogleTakeoutWatchHistoryParser $parser,
        protected YouTubeTranscriptService $youtubeService
    ) {}

    /**
     * Check if a watch-history file exists on server public directory.
     */
    public function serverFileStatus(): JsonResponse
    {
        $possibleFiles = ['watch-history.json', 'watch-history.html', 'watch-history.htm'];
        $found = null;

        foreach ($possibleFiles as $file) {
            $path = public_path($file);
            if (file_exists($path) && is_readable($path)) {
                $bytes = filesize($path);
                $formattedSize = $bytes >= 1048576 
                    ? round($bytes / 1048576, 1) . ' MB' 
                    : round($bytes / 1024, 1) . ' KB';

                $found = [
                    'file_name' => $file,
                    'file_size' => $bytes,
                    'file_size_formatted' => $formattedSize,
                ];
                break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'has_server_file' => !is_null($found),
                'file_info' => $found,
            ],
        ]);
    }

    /**
     * Load watch-history directly from public folder on server.
     */
    public function loadServerFile(Request $request): JsonResponse
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        $fileName = $request->input('file_name', 'watch-history.json');
        // Sanitize: allow only safe basename
        $fileName = basename($fileName);
        $filePath = public_path($fileName);

        if (!file_exists($filePath) || !is_readable($filePath)) {
            return response()->json([
                'success' => false,
                'message' => "পাবলিক ফোল্ডারে '{$fileName}' ফাইলটি পাওয়া যায়নি।",
            ], 404);
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'json';

        try {
            $parsedRecords = $this->parser->parse($filePath, $extension);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ফাইল প্রসেস করতে ব্যর্থ হয়েছে: ' . $e->getMessage(),
            ], 422);
        }

        if ($parsedRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'ফাইলে কোনো কার্যকর ইউটিউব ভিডিও হিস্ট্রি পাওয়া যায়নি।',
            ], 422);
        }

        return $this->processAndStoreRecords($request->user(), $fileName, $extension, $parsedRecords);
    }

    /**
     * Upload and parse Google Takeout watch-history file (JSON or HTML).
     */
    public function upload(Request $request): JsonResponse
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        if (!$request->hasFile('file')) {
            $maxUpload = ini_get('upload_max_filesize');
            return response()->json([
                'success' => false,
                'message' => "কোনো ফাইল পাওয়া যায়নি অথবা ফাইলের আকার সার্ভার লিমিটের ({$maxUpload}) চেয়ে বড়।",
            ], 422);
        }

        $request->validate([
            'file' => ['required', 'file', 'max:102400'], // Up to 100MB
        ]);

        $uploadedFile = $request->file('file');
        $originalName = $uploadedFile->getClientOriginalName();
        $extension = $uploadedFile->getClientOriginalExtension() ?: 'json';

        $tempPath = $uploadedFile->getRealPath();

        try {
            $parsedRecords = $this->parser->parse($tempPath, $extension);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'ফাইল প্রসেস করতে ব্যর্থ হয়েছে: ' . $e->getMessage(),
            ], 422);
        }

        if ($parsedRecords->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'ফাইলে কোনো কার্যকর ইউটিউব ভিডিও হিস্ট্রি পাওয়া যায়নি।',
            ], 422);
        }

        return $this->processAndStoreRecords($request->user(), $originalName, $extension, $parsedRecords);
    }

    /**
     * Shared helper to persist parsed records and return response.
     */
    protected function processAndStoreRecords($user, string $originalName, string $extension, \Illuminate\Support\Collection $parsedRecords): JsonResponse
    {
        // 1. Create Import Record
        $import = WatchHistoryImport::create([
            'user_id' => $user->id,
            'file_name' => $originalName,
            'file_type' => strtolower($extension),
            'total_items_found' => $parsedRecords->count(),
            'items_selected' => 0,
            'status' => 'parsed',
        ]);

        // 2. Fetch existing saved video IDs for this user to tag status
        $existingVideoIds = AggregatedContent::where('user_id', $user->id)
            ->pluck('id', 'youtube_video_id')
            ->toArray();

        // 3. Bulk insert items in chunks
        $now = now();
        $insertData = [];

        foreach ($parsedRecords as $rec) {
            $vId = $rec['youtube_video_id'];
            $isSaved = isset($existingVideoIds[$vId]);
            $aggId = $existingVideoIds[$vId] ?? null;

            $insertData[] = [
                'watch_history_import_id' => $import->id,
                'user_id' => $user->id,
                'youtube_video_id' => $vId,
                'youtube_url' => $rec['youtube_url'],
                'title' => mb_substr($rec['title'], 0, 500),
                'channel_name' => mb_substr($rec['channel_name'], 0, 250),
                'channel_url' => $rec['channel_url'] ? mb_substr($rec['channel_url'], 0, 500) : null,
                'content_type' => $rec['content_type'],
                'watched_at' => $rec['watched_at'],
                'is_aggregated' => $isSaved,
                'aggregated_content_id' => $aggId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($insertData, 500) as $chunk) {
            WatchHistoryItem::insert($chunk);
        }

        // Get unique channel count
        $uniqueChannels = $parsedRecords->pluck('channel_name')->unique()->filter()->count();

        return response()->json([
            'success' => true,
            'message' => "সফলভাবে {$parsedRecords->count()} টি ভিডিও হিস্ট্রি লোড হয়েছে ({$uniqueChannels} টি চ্যানেল থেকে)।",
            'data' => [
                'import_id' => $import->id,
                'file_name' => $import->file_name,
                'total_items' => $parsedRecords->count(),
                'unique_channels_count' => $uniqueChannels,
            ],
        ]);
    }

    /**
     * Get list of watch history items with filtering and search.
     */
    public function items(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WatchHistoryItem::where('user_id', $user->id);

        if ($request->filled('import_id')) {
            $query->where('watch_history_import_id', $request->input('import_id'));
        }

        if ($request->filled('channel')) {
            $query->where('channel_name', $request->input('channel'));
        }

        if ($request->filled('search')) {
            $s = trim($request->input('search'));
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('channel_name', 'like', "%{$s}%");
            });
        }

        if ($request->input('status') === 'saved') {
            $query->where('is_aggregated', true);
        } elseif ($request->input('status') === 'not_saved') {
            $query->where('is_aggregated', false);
        }

        if ($request->filled('content_type') && in_array($request->input('content_type'), ['video', 'short'])) {
            $query->where('content_type', $request->input('content_type'));
        }

        $items = $query->orderByDesc('watched_at')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => $items->items(),
            'pagination' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    /**
     * Get top channels with counts for the user's latest import.
     */
    public function channels(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = WatchHistoryItem::where('user_id', $user->id);

        if ($request->filled('import_id')) {
            $query->where('watch_history_import_id', $request->input('import_id'));
        }

        $channels = $query->select('channel_name', DB::raw('count(*) as count'))
            ->whereNotNull('channel_name')
            ->where('channel_name', '!=', 'Unknown Channel')
            ->groupBy('channel_name')
            ->orderByDesc('count')
            ->take(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $channels,
        ]);
    }

    /**
     * Batch import selected videos into AmarSocialContent knowledge library.
     */
    public function import(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_ids' => ['required', 'array', 'min:1', 'max:50'],
            'item_ids.*' => ['integer'],
        ]);

        $user = $request->user();
        $items = WatchHistoryItem::where('user_id', $user->id)
            ->whereIn('id', $validated['item_ids'])
            ->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'কোনো ভিডিও পাওয়া যায়নি।',
            ], 404);
        }

        $queuedCount = 0;
        $skippedCount = 0;

        foreach ($items as $item) {
            // Check if already aggregated
            $existing = AggregatedContent::where('user_id', $user->id)
                ->where('youtube_video_id', $item->youtube_video_id)
                ->first();

            if ($existing) {
                $item->update([
                    'is_aggregated' => true,
                    'aggregated_content_id' => $existing->id,
                ]);
                $skippedCount++;
                continue;
            }

            // Fetch metadata from YouTube, prioritizing actual Google Takeout title/channel if fallback returned
            try {
                $metadata = $this->youtubeService->fetchMetadata($item->youtube_video_id, $item->content_type);
            } catch (\Throwable $e) {
                $metadata = [];
            }

            $finalTitle = (!empty($metadata['title']) && !str_starts_with($metadata['title'], 'ইউটিউব ভিডিও'))
                ? $metadata['title']
                : ($item->title ?: ($metadata['title'] ?? 'YouTube Video'));

            $finalChannel = (!empty($metadata['channel_name']) && !in_array($metadata['channel_name'], ['YouTube Creator', 'অজানা চ্যানেল', 'Unknown Channel']))
                ? $metadata['channel_name']
                : ($item->channel_name ?: ($metadata['channel_name'] ?? 'Unknown Channel'));

            // Create AggregatedContent record
            $content = AggregatedContent::create([
                'user_id' => $user->id,
                'youtube_video_id' => $item->youtube_video_id,
                'youtube_url' => $item->youtube_url,
                'title' => $finalTitle,
                'channel_name' => $finalChannel,
                'thumbnail_url' => $metadata['thumbnail_url'] ?? "https://img.youtube.com/vi/{$item->youtube_video_id}/hqdefault.jpg",
                'content_type' => $item->content_type,
                'duration_seconds' => $metadata['duration_seconds'] ?? 0,
                'status' => 'pending',
                'visibility' => 'private',
            ]);

            // Auto-tag channel name
            $channelName = trim($content->channel_name);
            if (!empty($channelName) && $channelName !== 'Unknown Channel') {
                $tagSlug = Str::slug($channelName);
                if (empty($tagSlug)) {
                    $tagSlug = 'tag-' . substr(md5($channelName), 0, 8);
                }

                $channelTag = Tag::firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'slug' => $tagSlug,
                    ],
                    [
                        'name' => $channelName,
                        'color' => '#0d6efd',
                    ]
                );

                $content->tags()->syncWithoutDetaching([$channelTag->id]);
            }

            // Dispatch background queue worker
            if (config('queue.default') === 'sync') {
                ProcessYouTubeContentJob::dispatchSync($content);
            } else {
                ProcessYouTubeContentJob::dispatch($content);
            }

            $item->update([
                'is_aggregated' => true,
                'aggregated_content_id' => $content->id,
            ]);

            $queuedCount++;
        }

        return response()->json([
            'success' => true,
            'message' => "{$queuedCount} টি ভিডিও এআই প্রসেসিংয়ের জন্য কিউতে পাঠানো হয়েছে!" . ($skippedCount > 0 ? " ({$skippedCount} টি ইতিমধ্যে লাইব্রেরিতে থাকায় স্কিপ করা হয়েছে)" : ''),
            'data' => [
                'queued_count' => $queuedCount,
                'skipped_count' => $skippedCount,
            ],
        ]);
    }
}

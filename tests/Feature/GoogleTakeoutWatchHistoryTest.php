<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\User;
use App\Models\WatchHistoryImport;
use App\Models\WatchHistoryItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GoogleTakeoutWatchHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_and_parse_takeout_json_file(): void
    {
        $user = User::factory()->create();

        $sampleJson = json_encode([
            [
                'header' => 'YouTube',
                'title' => 'Watched Why 99% of Developers Never Reach Senior Level',
                'titleUrl' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'subtitles' => [
                    [
                        'name' => 'Zen van Riel',
                        'url' => 'https://www.youtube.com/channel/UC123456',
                    ],
                ],
                'time' => '2026-10-25T14:22:10.000Z',
                'products' => ['YouTube'],
            ],
            [
                'header' => 'YouTube',
                'title' => 'Watched The Era of Chatbots is Over',
                'titleUrl' => 'https://www.youtube.com/shorts/abcdefghijk',
                'subtitles' => [
                    [
                        'name' => 'Vaibhav Sisinty',
                        'url' => 'https://www.youtube.com/channel/UC789012',
                    ],
                ],
                'time' => '2026-10-26T10:15:00.000Z',
                'products' => ['YouTube'],
            ],
            // Ad / Deleted without url - should be safely ignored
            [
                'header' => 'YouTube',
                'title' => 'An Ad you watched',
                'time' => '2026-10-26T11:00:00.000Z',
            ],
        ]);

        $file = UploadedFile::fake()->createWithContent('watch-history.json', $sampleJson);

        $response = $this->actingAs($user)->postJson('/api/user/watch-history/upload', [
            'file' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_items' => 2,
                    'unique_channels_count' => 2,
                ],
            ]);

        $this->assertDatabaseHas('watch_history_imports', [
            'user_id' => $user->id,
            'file_name' => 'watch-history.json',
            'total_items_found' => 2,
        ]);

        $this->assertDatabaseHas('watch_history_items', [
            'user_id' => $user->id,
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'title' => 'Why 99% of Developers Never Reach Senior Level',
            'channel_name' => 'Zen van Riel',
            'content_type' => 'video',
        ]);

        $this->assertDatabaseHas('watch_history_items', [
            'user_id' => $user->id,
            'youtube_video_id' => 'abcdefghijk',
            'title' => 'The Era of Chatbots is Over',
            'channel_name' => 'Vaibhav Sisinty',
            'content_type' => 'short',
        ]);
    }

    public function test_user_can_filter_items_and_view_channels(): void
    {
        $user = User::factory()->create();

        $import = WatchHistoryImport::create([
            'user_id' => $user->id,
            'file_name' => 'watch-history.json',
            'file_type' => 'json',
            'total_items_found' => 3,
            'status' => 'parsed',
        ]);

        WatchHistoryItem::create([
            'watch_history_import_id' => $import->id,
            'user_id' => $user->id,
            'youtube_video_id' => 'vid11111111',
            'youtube_url' => 'https://www.youtube.com/watch?v=vid11111111',
            'title' => 'Modern PHP 8.4 Deep Dive',
            'channel_name' => 'Laracasts',
            'content_type' => 'video',
            'watched_at' => now()->subDay(),
        ]);

        WatchHistoryItem::create([
            'watch_history_import_id' => $import->id,
            'user_id' => $user->id,
            'youtube_video_id' => 'vid22222222',
            'youtube_url' => 'https://www.youtube.com/watch?v=vid22222222',
            'title' => 'Vue 3 Full Course',
            'channel_name' => 'Laracasts',
            'content_type' => 'video',
            'watched_at' => now(),
        ]);

        WatchHistoryItem::create([
            'watch_history_import_id' => $import->id,
            'user_id' => $user->id,
            'youtube_video_id' => 'vid33333333',
            'youtube_url' => 'https://www.youtube.com/shorts/vid33333333',
            'title' => 'Quick CSS Trick',
            'channel_name' => 'Kevin Powell',
            'content_type' => 'short',
            'watched_at' => now()->subHours(2),
        ]);

        // 1. Channels endpoint
        $channelsResponse = $this->actingAs($user)->getJson('/api/user/watch-history/channels');
        $channelsResponse->assertStatus(200);
        $this->assertEquals('Laracasts', $channelsResponse->json('data.0.channel_name'));
        $this->assertEquals(2, $channelsResponse->json('data.0.count'));

        // 2. Filter items by channel
        $filteredResponse = $this->actingAs($user)->getJson('/api/user/watch-history/items?channel=Laracasts');
        $filteredResponse->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // 3. Filter items by search keyword
        $searchResponse = $this->actingAs($user)->getJson('/api/user/watch-history/items?search=CSS');
        $searchResponse->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_user_can_bulk_import_selected_videos_into_knowledge_library(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $import = WatchHistoryImport::create([
            'user_id' => $user->id,
            'file_name' => 'watch-history.json',
            'file_type' => 'json',
            'total_items_found' => 2,
            'status' => 'parsed',
        ]);

        $item1 = WatchHistoryItem::create([
            'watch_history_import_id' => $import->id,
            'user_id' => $user->id,
            'youtube_video_id' => 'bulkVid11111',
            'youtube_url' => 'https://www.youtube.com/watch?v=bulkVid11111',
            'title' => 'System Design Basics',
            'channel_name' => 'ByteByteGo',
            'content_type' => 'video',
            'watched_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson('/api/user/watch-history/import', [
            'item_ids' => [$item1->id],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'queued_count' => 1,
                    'skipped_count' => 0,
                ],
            ]);

        $this->assertDatabaseHas('aggregated_contents', [
            'user_id' => $user->id,
            'youtube_video_id' => 'bulkVid11111',
            'channel_name' => 'ByteByteGo',
        ]);

        // Auto-tagged channel name check
        $content = AggregatedContent::where('youtube_video_id', 'bulkVid11111')->first();
        $this->assertTrue($content->tags()->where('name', 'ByteByteGo')->exists());

        // Staging item marked as aggregated
        $this->assertDatabaseHas('watch_history_items', [
            'id' => $item1->id,
            'is_aggregated' => true,
            'aggregated_content_id' => $content->id,
        ]);
    }

    public function test_tenant_isolation_user_b_cannot_access_user_a_items(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $importA = WatchHistoryImport::create([
            'user_id' => $userA->id,
            'file_name' => 'watch-history.json',
            'file_type' => 'json',
            'total_items_found' => 1,
            'status' => 'parsed',
        ]);

        $itemA = WatchHistoryItem::create([
            'watch_history_import_id' => $importA->id,
            'user_id' => $userA->id,
            'youtube_video_id' => 'secretVid123',
            'youtube_url' => 'https://www.youtube.com/watch?v=secretVid123',
            'title' => 'User A Private Study',
            'channel_name' => 'Secret Academy',
            'content_type' => 'video',
            'watched_at' => now(),
        ]);

        // User B viewing items gets 0 items
        $response = $this->actingAs($userB)->getJson('/api/user/watch-history/items');
        $response->assertStatus(200)->assertJsonCount(0, 'data');

        // User B attempting to import User A's item gets 404
        $importResponse = $this->actingAs($userB)->postJson('/api/user/watch-history/import', [
            'item_ids' => [$itemA->id],
        ]);
        $importResponse->assertStatus(404);
    }

    public function test_user_can_check_server_file_status_and_load_directly(): void
    {
        $user = User::factory()->create();

        // 1. Check server file status
        $statusResponse = $this->actingAs($user)->getJson('/api/user/watch-history/server-file-status');
        $statusResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => ['has_server_file', 'file_info']
            ]);

        // 2. Load directly if public file exists
        if (file_exists(public_path('watch-history.json'))) {
            $loadResponse = $this->actingAs($user)->postJson('/api/user/watch-history/load-server-file', [
                'file_name' => 'watch-history.json',
            ]);
            $loadResponse->assertStatus(200)
                ->assertJsonPath('success', true);
            $this->assertDatabaseHas('watch_history_imports', ['user_id' => $user->id]);
        }
    }
}


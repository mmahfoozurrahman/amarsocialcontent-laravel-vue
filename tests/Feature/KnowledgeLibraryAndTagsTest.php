<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\ContentArticle;
use App\Models\PersonalNote;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KnowledgeLibraryAndTagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_creation_and_scoping_per_user(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // User A creates tag
        $response = $this->actingAs($userA)
            ->postJson('/api/user/tags', [
                'name' => 'Artificial Intelligence',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'name' => 'Artificial Intelligence',
                    'slug' => 'artificial-intelligence',
                ],
            ]);

        $tagA = Tag::where('user_id', $userA->id)->first();
        $this->assertNotNull($tagA);

        // User B tries to delete User A's tag
        $deleteResponse = $this->actingAs($userB)
            ->deleteJson("/api/user/tags/{$tagA->id}");

        $deleteResponse->assertStatus(403);
    }

    public function test_user_can_sync_tags_to_their_article(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $content = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'sample123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sample123',
            'title' => 'Sample Tech Video',
            'bengali_title' => 'নমুনা টেক ভিডিও',
            'status' => 'completed',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/user/articles/{$content->id}/tags", [
                'tags' => ['Laravel', 'Vue 3', 'Architecture'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(3, $content->tags()->count());
        $this->assertTrue($content->tags->contains('name', 'Laravel'));
    }

    public function test_personal_notes_are_strictly_isolated_between_users(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $content = AggregatedContent::create([
            'user_id' => $userA->id,
            'youtube_video_id' => 'sample123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sample123',
            'title' => 'Shared Tech Video',
            'bengali_title' => 'টেক ভিডিও',
            'visibility' => 'public',
            'status' => 'completed',
        ]);

        // User A adds a private note
        $this->actingAs($userA)
            ->postJson("/api/user/articles/{$content->id}/notes", [
                'note_content' => 'My secret private architecture insight.',
            ])->assertStatus(200);

        $this->assertDatabaseHas('personal_notes', [
            'user_id' => $userA->id,
            'note_content' => 'My secret private architecture insight.',
        ]);

        // User B views the same public article
        $viewResponse = $this->actingAs($userB)
            ->getJson("/api/user/articles/{$content->id}");

        $viewResponse->assertStatus(200);
        // User B must NOT see User A's private note
        $this->assertNull($viewResponse->json('data.personal_note'));
    }

    public function test_bookmark_toggle_and_listing(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $content = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'sample123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sample123',
            'title' => 'Bookmarkable Video',
            'bengali_title' => 'বুকমার্ক ভিডিও',
            'status' => 'completed',
        ]);

        // Toggle on
        $toggleOn = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/user/articles/{$content->id}/bookmark");
        $toggleOn->assertStatus(200)
            ->assertJson(['is_bookmarked' => true]);

        // Check bookmarks list
        $listResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/bookmarks');
        $listResponse->assertStatus(200);
        $this->assertCount(1, $listResponse->json('data'));

        // Toggle off
        $toggleOff = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/user/articles/{$content->id}/bookmark");
        $toggleOff->assertStatus(200)
            ->assertJson(['is_bookmarked' => false]);
    }

    public function test_reading_progress_and_timeline(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $content = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'sample123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sample123',
            'title' => 'Studied Video',
            'bengali_title' => 'পঠিত ভিডিও',
            'status' => 'completed',
        ]);

        // Record 120 seconds of reading
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson("/api/user/articles/{$content->id}/reading-progress", [
                'additional_seconds' => 120,
                'is_completed' => true,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'seconds_spent' => 120,
                    'is_completed' => true,
                ],
            ]);

        // Check timeline endpoint
        $timelineResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/reading-timeline');

        $timelineResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_seconds_last_7_days' => 120,
                    'total_minutes_last_7_days' => 2,
                    'completed_articles_count' => 1,
                ],
            ]);
    }

    public function test_public_sharing_quarantine_protects_private_data(): void
    {
        $user = User::factory()->create(['email' => 'author@example.com']);
        $token = $user->createToken('test')->plainTextToken;

        $content = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'sample123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sample123',
            'title' => 'Architecture in 2026',
            'bengali_title' => 'আর্কিটেকচার ২০২৬',
            'status' => 'completed',
            'visibility' => 'private',
        ]);

        ContentArticle::create([
            'aggregated_content_id' => $content->id,
            'introduction' => 'ভূমিকা...',
            'main_discussion' => 'মূল আলোচনা...',
            'raw_markdown' => '# আর্কিটেকচার ২০২৬\n\n## ভূমিকা...',
        ]);

        PersonalNote::create([
            'user_id' => $user->id,
            'aggregated_content_id' => $content->id,
            'note_content' => 'Top secret private author note',
        ]);

        // 1. Switch visibility to public
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/user/articles/{$content->id}", [
                'visibility' => 'public',
            ]);

        $updateResponse->assertStatus(200);
        $shareToken = $updateResponse->json('data.share_token');
        $this->assertNotEmpty($shareToken);
        $this->assertEquals(32, strlen($shareToken));

        // 2. Unauthenticated visitor accesses public route
        $publicResponse = $this->getJson("/api/public/articles/{$shareToken}");
        $publicResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'share_token' => $shareToken,
                    'bengali_title' => 'আর্কিটেকচার ২০২৬',
                ],
            ]);

        // Verify quarantine: author email and private note are NOT exposed
        $responseContent = $publicResponse->getContent();
        $this->assertStringNotContainsString('author@example.com', $responseContent);
        $this->assertStringNotContainsString('Top secret private author note', $responseContent);

        // 3. Reverting back to private immediately invalidates public route
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->putJson("/api/user/articles/{$content->id}", [
                'visibility' => 'private',
            ])->assertStatus(200);

        $this->getJson("/api/public/articles/{$shareToken}")
            ->assertStatus(404);
    }
}

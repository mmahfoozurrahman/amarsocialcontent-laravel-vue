<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\Section;
use App\Models\Collection;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecondBrainKnowledgeOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_collection_section_and_note(): void
    {
        $user = User::factory()->create();

        // 1. Create Collection
        $collectionResponse = $this->actingAs($user)
            ->postJson('/api/user/collections', [
                'title' => 'Mastering Laravel Architecture',
                'description' => 'Comprehensive collection on advanced Laravel concepts.',
            ]);

        $collectionResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Mastering Laravel Architecture',
                    'slug' => 'mastering-laravel-architecture',
                ],
            ]);

        $collectionId = $collectionResponse->json('data.id');

        // 2. Create Section
        $sectionResponse = $this->actingAs($user)
            ->postJson("/api/user/collections/{$collectionId}/sections", [
                'title' => 'Section 1: Multi-Tenancy & Security',
                'description' => 'Isolating tenant records cleanly.',
            ]);

        $sectionResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Section 1: Multi-Tenancy & Security',
                ],
            ]);

        $sectionId = $sectionResponse->json('data.id');

        // 3. Create Note with Custom Notes
        $noteResponse = $this->actingAs($user)
            ->postJson("/api/user/sections/{$sectionId}/notes", [
                'title' => 'Note 1.1: IDOR Attack Mitigations',
                'content' => 'Always verify user ownership on the server side.',
            ]);

        $noteResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Note 1.1: IDOR Attack Mitigations',
                    'is_completed' => false,
                ],
            ]);
    }

    public function test_user_can_link_aggregated_article_to_note(): void
    {
        $user = User::factory()->create();

        $article = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'sampleYt123',
            'youtube_url' => 'https://www.youtube.com/watch?v=sampleYt123',
            'title' => 'Clean Code Principles',
            'bengali_title' => 'ক্লিন কোড নীতি ও আর্কিটেকচার',
            'status' => 'completed',
        ]);

        $collection = Collection::create([
            'user_id' => $user->id,
            'title' => 'Clean Code Collection',
        ]);

        $section = Section::create([
            'collection_id' => $collection->id,
            'title' => 'SOLID Foundations',
        ]);

        // Link the article to note
        $response = $this->actingAs($user)
            ->postJson("/api/user/sections/{$section->id}/notes", [
                'title' => 'Introduction to Single Responsibility',
                'aggregated_content_id' => $article->id,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'aggregated_content_id' => $article->id,
                    'is_linked_to_article' => true,
                ],
            ]);

        $this->assertDatabaseHas('notes', [
            'section_id' => $section->id,
            'aggregated_content_id' => $article->id,
        ]);
    }

    public function test_user_a_cannot_link_user_b_article_to_their_note(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Article owned by User B
        $articleB = AggregatedContent::create([
            'user_id' => $userB->id,
            'youtube_video_id' => 'userBvid',
            'youtube_url' => 'https://www.youtube.com/watch?v=userBvid',
            'title' => 'User B Secret Architecture',
            'bengali_title' => 'গোপনীয় টেকনিক',
            'status' => 'completed',
        ]);

        // Collection owned by User A
        $collectionA = Collection::create([
            'user_id' => $userA->id,
            'title' => 'User A Collection',
        ]);

        $sectionA = Section::create([
            'collection_id' => $collectionA->id,
            'title' => 'Section A',
        ]);

        // User A attempts to steal and attach User B's article
        $response = $this->actingAs($userA)
            ->postJson("/api/user/sections/{$sectionA->id}/notes", [
                'title' => 'Unauthorized Linked Note',
                'aggregated_content_id' => $articleB->id,
            ]);

        // Must be rejected with 422 validation error
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['aggregated_content_id']);
    }

    public function test_lms_hierarchy_idor_defense_blocks_cross_tenant_manipulation(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $collectionB = Collection::create([
            'user_id' => $userB->id,
            'title' => 'User B Private Collection',
            'visibility' => 'private',
        ]);

        $sectionB = Section::create([
            'collection_id' => $collectionB->id,
            'title' => 'User B Section',
        ]);

        $noteB = Note::create([
            'section_id' => $sectionB->id,
            'title' => 'User B Note',
        ]);

        // 1. User A tries to view User B private collection
        $this->actingAs($userA)
            ->getJson("/api/user/collections/{$collectionB->id}")
            ->assertStatus(403);

        // 2. User A tries to add a section to User B collection
        $this->actingAs($userA)
            ->postJson("/api/user/collections/{$collectionB->id}/sections", [
                'title' => 'Hacked Section',
            ])->assertStatus(403);

        // 3. User A tries to add a note to User B section
        $this->actingAs($userA)
            ->postJson("/api/user/sections/{$sectionB->id}/notes", [
                'title' => 'Hacked Note',
            ])->assertStatus(403);

        // 4. User A tries to update or delete User B note
        $this->actingAs($userA)
            ->putJson("/api/user/notes/{$noteB->id}", [
                'title' => 'Altered Note Title',
            ])->assertStatus(403);

        $this->actingAs($userA)
            ->deleteJson("/api/user/notes/{$noteB->id}")
            ->assertStatus(403);
    }

    public function test_note_completion_and_collection_progress_percentage(): void
    {
        $user = User::factory()->create();

        $collection = Collection::create([
            'user_id' => $user->id,
            'title' => 'Progress Tracked Collection',
        ]);

        $section = Section::create([
            'collection_id' => $collection->id,
            'title' => 'Section 1',
        ]);

        $note1 = Note::create([
            'section_id' => $section->id,
            'title' => 'Note 1',
            'is_completed' => false,
        ]);

        $note2 = Note::create([
            'section_id' => $section->id,
            'title' => 'Note 2',
            'is_completed' => false,
        ]);

        $this->assertEquals(0, $collection->progressPercentage());

        // Mark note 1 as completed
        $this->actingAs($user)
            ->putJson("/api/user/notes/{$note1->id}", [
                'is_completed' => true,
            ])->assertStatus(200);

        $this->assertEquals(50, $collection->fresh()->progressPercentage());

        // Mark note 2 as completed
        $this->actingAs($user)
            ->putJson("/api/user/notes/{$note2->id}", [
                'is_completed' => true,
            ])->assertStatus(200);

        $this->assertEquals(100, $collection->fresh()->progressPercentage());
    }

    public function test_unassigned_articles_filter(): void
    {
        $user = User::factory()->create();

        // 2 Completed Articles
        $article1 = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'art1',
            'youtube_url' => 'https://www.youtube.com/watch?v=art1',
            'title' => 'Article 1',
            'status' => 'completed',
        ]);

        $article2 = AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'art2',
            'youtube_url' => 'https://www.youtube.com/watch?v=art2',
            'title' => 'Article 2',
            'status' => 'completed',
        ]);

        $collection = Collection::create(['user_id' => $user->id, 'title' => 'Test Collection']);
        $section = Section::create(['collection_id' => $collection->id, 'title' => 'Test Section']);

        // Attach article 1 to note
        Note::create([
            'section_id' => $section->id,
            'title' => 'Note 1',
            'aggregated_content_id' => $article1->id,
        ]);

        // Unassigned should only return article 2
        $response = $this->actingAs($user)
            ->getJson('/api/user/unassigned-articles');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals($article2->id, $response->json('data.0.id'));
    }
}


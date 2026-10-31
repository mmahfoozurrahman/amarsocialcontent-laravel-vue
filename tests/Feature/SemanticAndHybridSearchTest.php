<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\ContentArticle;
use App\Models\ContentEmbedding;
use App\Models\User;
use App\Services\Search\EmbeddingService;
use App\Services\Search\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SemanticAndHybridSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected User $userB;
    protected AggregatedContent $contentA1;
    protected AggregatedContent $contentA2;
    protected AggregatedContent $contentB1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->userA = User::factory()->create([
            'email' => 'user_a@example.com',
            'role' => 'user',
        ]);

        $this->userB = User::factory()->create([
            'email' => 'user_b@example.com',
            'role' => 'user',
        ]);

        // User A Content 1: AI Agents
        $this->contentA1 = AggregatedContent::create([
            'user_id' => $this->userA->id,
            'youtube_video_id' => 'video_ai_1',
            'youtube_url' => 'https://www.youtube.com/shorts/video_ai_1',
            'title' => 'The Era of Chatbots is Over. Era of AI Agents.',
            'bengali_title' => 'চ্যাটবটের যুগ শেষ, স্বয়ংক্রিয় এআই এজেন্টের নতুন সূচনা',
            'channel_name' => 'AI Mentor',
            'thumbnail_url' => 'https://img.youtube.com/vi/video_ai_1/hqdefault.jpg',
            'content_type' => 'short',
            'status' => 'completed',
            'reading_time_minutes' => 3,
        ]);

        ContentArticle::create([
            'aggregated_content_id' => $this->contentA1->id,
            'introduction' => 'এআই এজেন্ট স্বয়ংক্রিয়ভাবে কাজ করতে পারে যা চ্যাটবট পারে না।',
            'main_discussion' => 'টাস্ক অটোমেশন এবং ওয়েব ব্রাউজিং সক্ষমতা।',
            'important_concepts' => 'অটোনোমাস এজেন্ট, মাল্টি-স্টেপ রিজনিলিং',
            'examples_applications' => 'কোডিং অ্যাসিস্ট্যান্ট এবং অটোমেটেড রিসার্চ।',
            'takeaways' => 'এআই এজেন্ট ভবিষ্যৎ প্রযুক্তি পরিচালন করবে।',
            'key_terms' => 'AI Agent: স্বয়ংক্রিয় এজেন্ট',
            'raw_markdown' => "# চ্যাটবটের যুগ শেষ\n\nএআই এজেন্ট স্বয়ংক্রিয়ভাবে কাজ করতে পারে।",
        ]);

        // User A Content 2: MacBook
        $this->contentA2 = AggregatedContent::create([
            'user_id' => $this->userA->id,
            'youtube_video_id' => 'video_mac_2',
            'youtube_url' => 'https://www.youtube.com/watch?v=video_mac_2',
            'title' => 'Apple Touchscreen MacBook Pro Announced',
            'bengali_title' => 'অ্যাপল টাচস্ক্রিন ম্যাকবুক প্রো উন্মোচন',
            'channel_name' => 'Tech Talks',
            'thumbnail_url' => 'https://img.youtube.com/vi/video_mac_2/hqdefault.jpg',
            'content_type' => 'video',
            'status' => 'completed',
            'reading_time_minutes' => 6,
        ]);

        ContentArticle::create([
            'aggregated_content_id' => $this->contentA2->id,
            'introduction' => 'ম্যাকবুকে প্রথমবার ওলেড টাচস্ক্রিন ডিসপ্লে যুক্ত হয়েছে।',
            'main_discussion' => 'নতুন এম-সিরিজ প্রসেসর এবং দীর্ঘ ব্যাটারি ব্যাকআপ।',
            'important_concepts' => 'টাচ ডিসপ্লে, হার্ডওয়্যার আর্কিটেকচার',
            'examples_applications' => 'গ্রাফিক ডিজাইন ও কোডিং প্রোডাক্টিভিটি।',
            'takeaways' => 'প্রফেশনাল ডেভেলপারদের কাজের গতি বৃদ্ধি পাবে।',
            'key_terms' => 'Touchscreen: স্পর্শ সংবেদনশীল ডিসপ্লে',
            'raw_markdown' => "# ম্যাকবুক প্রো\n\nম্যাকবুকে প্রথমবার ওলেড টাচস্ক্রিন ডিসপ্লে।",
        ]);

        // User B Content: Secret Database Patterns (Should NOT be visible to User A)
        $this->contentB1 = AggregatedContent::create([
            'user_id' => $this->userB->id,
            'youtube_video_id' => 'video_user_b',
            'youtube_url' => 'https://www.youtube.com/watch?v=video_user_b',
            'title' => 'User B Secret Database Design Architecture',
            'bengali_title' => 'গোপন ডাটাবেস ডিজাইন আর্কিটেকচার',
            'channel_name' => 'Secret Channel',
            'thumbnail_url' => 'https://img.youtube.com/vi/video_user_b/hqdefault.jpg',
            'content_type' => 'video',
            'status' => 'completed',
            'reading_time_minutes' => 5,
        ]);

        ContentArticle::create([
            'aggregated_content_id' => $this->contentB1->id,
            'introduction' => 'ইউজার বি এর নিজস্ব গোপন নথিপত্র।',
            'main_discussion' => 'সিক্রেট এলগরিদম আলোচনা।',
            'important_concepts' => 'গোপন ডাটাবেস',
            'examples_applications' => 'অনুমতিহীন প্রবেশ নিষিদ্ধ।',
            'takeaways' => 'নিরাপত্তা নিশ্চিত করুন।',
            'key_terms' => 'Secret: গোপনীয়',
            'raw_markdown' => "# গোপন ডাটাবেস ডিজাইন\n\nইউজার বি এর নিজস্ব তথ্য।",
        ]);

        // Index all contents into embeddings
        $searchService = app(SemanticSearchService::class);
        $searchService->indexContent($this->contentA1);
        $searchService->indexContent($this->contentA2);
        $searchService->indexContent($this->contentB1);
    }

    public function test_user_can_perform_hybrid_search_and_receive_ranked_results(): void
    {
        $token = $this->userA->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/search?q=এআই এজেন্ট&type=hybrid');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'meta' => [
                    'query' => 'এআই এজেন্ট',
                    'search_type' => 'hybrid',
                ],
            ]);

        $results = $response->json('data');
        $this->assertNotEmpty($results);
        $this->assertEquals($this->contentA1->id, $results[0]['id']);
        $this->assertGreaterThanOrEqual(15, $results[0]['relevance_percent']);
        $this->assertStringContainsString('mark', $results[0]['excerpt']);
    }

    public function test_multi_tenant_search_isolation_prevents_viewing_other_users_articles(): void
    {
        $token = $this->userA->createToken('test_token')->plainTextToken;

        // User A searches for keyword unique to User B's content ("গোপন ডাটাবেস")
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/search?q=গোপন ডাটাবেস&type=hybrid');

        $response->assertStatus(200);
        $results = $response->json('data');

        // Results should NOT contain User B's content
        $resultIds = collect($results)->pluck('id')->all();
        $this->assertNotContains($this->contentB1->id, $resultIds);
    }

    public function test_search_filters_by_content_type(): void
    {
        $token = $this->userA->createToken('test_token')->plainTextToken;

        // Search only Shorts
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/search?q=অ্যাপল&content_type=short');

        $response->assertStatus(200);
        $results = $response->json('data');
        foreach ($results as $item) {
            $this->assertEquals('short', $item['content_type']);
        }
    }

    public function test_search_history_is_recorded_and_can_be_cleared(): void
    {
        $token = $this->userA->createToken('test_token')->plainTextToken;

        // Execute search
        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/search?q=ম্যাকবুক ডিসপ্লে');

        // Check history endpoint
        $historyRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user/search/history');

        $historyRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertNotEmpty($historyRes->json('data'));
        $this->assertEquals('ম্যাকবুক ডিসপ্লে', $historyRes->json('data.0.query'));

        // Clear history
        $clearRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/user/search/history');

        $clearRes->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('search_histories', [
            'user_id' => $this->userA->id,
            'query' => 'ম্যাকবুক ডিসপ্লে',
        ]);
    }
}


<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\User;
use App\Services\AI\AIServiceManager;
use App\Services\AI\Providers\GeminiProvider;
use App\Services\AI\Providers\GroqProvider;
use App\Services\YouTubeTranscriptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class YouTubeAggregationAndAITest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.ai.primary' => 'gemini',
            'services.ai.fallback' => 'groq',
        ]);
        // Prevent external network calls by faking oEmbed
        Http::fake([
            'https://www.youtube.com/oembed*' => Http::response([
                'title' => 'Software Architecture Patterns in 2026',
                'author_name' => 'Tech Mentor Academy',
                'thumbnail_url' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            ], 200),
        ]);
    }

    public function test_youtube_service_parses_regular_and_shorts_urls_correctly(): void
    {
        $service = new YouTubeTranscriptService;

        $regular = $service->parseYouTubeUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
        $this->assertEquals('dQw4w9WgXcQ', $regular['video_id']);
        $this->assertEquals('video', $regular['content_type']);

        $short = $service->parseYouTubeUrl('https://www.youtube.com/shorts/9bZkp7q19f0');
        $this->assertEquals('9bZkp7q19f0', $short['video_id']);
        $this->assertEquals('short', $short['content_type']);
    }

    public function test_invalid_youtube_url_is_rejected(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/user/articles', [
                'youtube_url' => 'https://facebook.com/watch?v=123',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['youtube_url']);
    }

    public function test_ingestion_with_manual_transcript_generates_mentor_bengali_article(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Mock Gemini Provider
        $mockGemini = Mockery::mock(GeminiProvider::class);
        $mockGemini->shouldReceive('isAvailable')->andReturn(true);
        $mockGemini->shouldReceive('getName')->andReturn('gemini');
        $mockGemini->shouldReceive('getModel')->andReturn('gemini-1.5-flash');
        $mockGemini->shouldReceive('generateMentorBengaliArticle')
            ->once()
            ->andReturn([
                'bengali_title' => 'আধুনিক সফটওয়্যার আর্কিটেকচার প্যাটার্ন',
                'introduction' => 'এই ভিডিওতে আধুনিক সিস্টেম ডিজাইনের মূল ভিত্তি তুলে ধরা হয়েছে।',
                'main_discussion' => 'মাইক্রোসার্ভিস ও মনোলিথের মধ্যে ভারসাম্য বজায় রাখার উপায় ব্যাখ্যা করা হয়েছে।',
                'important_concepts' => '১. ডিকাপলিং ২. অ্যাসিঙ্ক্রোনাস কিউ',
                'examples_applications' => 'ই-কমার্স চেকআউট সিস্টেমে কিউ ব্যবহারের বাস্তব উদাহরণ।',
                'takeaways' => 'সবসময় আর্কিটেকচার জটিল না করে সিম্পল ডিজাইন দিয়ে শুরু করুন।',
                'key_terms' => 'Message Broker: মেসেজ আদান-প্রদানের মাধ্যম',
                'raw_markdown' => "# আধুনিক সফটওয়্যার আর্কিটেকচার প্যাটার্ন\n\n## ভূমিকা\nবিস্তারিত...",
                'reading_time_minutes' => 3,
                'prompt_tokens' => 450,
                'completion_tokens' => 620,
                'latency_ms' => 1200,
                'provider' => 'gemini',
                'model' => 'gemini-1.5-flash',
            ]);

        $this->app->instance(AIServiceManager::class, new AIServiceManager($mockGemini));

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/user/articles', [
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'manual_transcript' => 'In this tutorial we explore modern software architecture patterns, message brokers, and why you should avoid premature optimization.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'youtube_video_id' => 'dQw4w9WgXcQ',
                    'bengali_title' => 'আধুনিক সফটওয়্যার আর্কিটেকচার প্যাটার্ন',
                    'status' => 'completed',
                    'content_type' => 'video',
                ],
            ]);

        $this->assertDatabaseHas('aggregated_contents', [
            'user_id' => $user->id,
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'status' => 'completed',
            'bengali_title' => 'আধুনিক সফটওয়্যার আর্কিটেকচার প্যাটার্ন',
        ]);

        $this->assertDatabaseHas('content_articles', [
            'raw_markdown' => "# আধুনিক সফটওয়্যার আর্কিটেকচার প্যাটার্ন\n\n## ভূমিকা\nবিস্তারিত...",
        ]);

        $this->assertDatabaseHas('ai_processing_logs', [
            'user_id' => $user->id,
            'provider' => 'gemini',
            'status' => 'success',
        ]);
    }

    public function test_ai_failover_triggers_groq_when_gemini_fails(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        // Mock Gemini to throw rate limit error
        $mockGemini = Mockery::mock(GeminiProvider::class);
        $mockGemini->shouldReceive('isAvailable')->andReturn(true);
        $mockGemini->shouldReceive('getName')->andReturn('gemini');
        $mockGemini->shouldReceive('getModel')->andReturn('gemini-1.5-flash');
        $mockGemini->shouldReceive('generateMentorBengaliArticle')
            ->once()
            ->andThrow(new RuntimeException('Resource exhausted: Rate limit reached (HTTP 429)', 429));

        // Mock Groq as fallback
        $mockGroq = Mockery::mock(GroqProvider::class);
        $mockGroq->shouldReceive('isAvailable')->andReturn(true);
        $mockGroq->shouldReceive('getName')->andReturn('groq');
        $mockGroq->shouldReceive('getModel')->andReturn('llama-3.3-70b-versatile');
        $mockGroq->shouldReceive('generateMentorBengaliArticle')
            ->once()
            ->andReturn([
                'bengali_title' => 'গ্রক ফলব্যাক: সফটওয়্যার আর্কিটেকচার',
                'introduction' => 'ভূমিকা...',
                'main_discussion' => 'মূল আলোচনা...',
                'important_concepts' => 'কনসেপ্ট...',
                'examples_applications' => 'উদাহরণ...',
                'takeaways' => 'শিক্ষণীয় বিষয়...',
                'key_terms' => 'শব্দকোষ...',
                'raw_markdown' => '# গ্রক ফলব্যাক: সফটওয়্যার আর্কিটেকচার',
                'reading_time_minutes' => 2,
                'prompt_tokens' => 300,
                'completion_tokens' => 500,
                'latency_ms' => 450,
                'provider' => 'groq',
                'model' => 'llama-3.3-70b-versatile',
            ]);

        $aiManager = new AIServiceManager($mockGemini, $mockGroq);
        $this->app->instance(AIServiceManager::class, $aiManager);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/user/articles', [
                'youtube_url' => 'https://www.youtube.com/shorts/9bZkp7q19f0',
                'manual_transcript' => 'Short video explaining rate limiting strategies.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'bengali_title' => 'গ্রক ফলব্যাক: সফটওয়্যার আর্কিটেকচার',
                    'status' => 'completed',
                    'ai_provider' => 'groq',
                ],
            ]);

        // Check fallback log
        $this->assertDatabaseHas('ai_processing_logs', [
            'user_id' => $user->id,
            'provider' => 'gemini',
            'status' => 'fallback_triggered',
        ]);

        // Check success log for Groq
        $this->assertDatabaseHas('ai_processing_logs', [
            'user_id' => $user->id,
            'provider' => 'groq',
            'status' => 'success',
        ]);
    }

    public function test_user_a_cannot_access_or_modify_user_b_private_article(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $articleB = AggregatedContent::create([
            'user_id' => $userB->id,
            'youtube_video_id' => 'userBvideo123',
            'youtube_url' => 'https://www.youtube.com/watch?v=userBvideo123',
            'title' => 'User B Private Video',
            'bengali_title' => 'ইউজার বি এর ব্যক্তিগত কনটেন্ট',
            'visibility' => 'private',
            'status' => 'completed',
        ]);

        $tokenA = $userA->createToken('testA')->plainTextToken;

        // User A attempts to view User B's article
        $viewResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->getJson("/api/user/articles/{$articleB->id}");
        $viewResponse->assertStatus(403);

        // User A attempts to delete User B's article
        $deleteResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->deleteJson("/api/user/articles/{$articleB->id}");
        $deleteResponse->assertStatus(403);

        // User A attempts to submit manual transcript to User B's article
        $transcriptResponse = $this->withHeader('Authorization', 'Bearer '.$tokenA)
            ->postJson("/api/user/articles/{$articleB->id}/manual-transcript", [
                'transcript' => 'Malicious attempt to alter another users content.',
            ]);
        $transcriptResponse->assertStatus(403);
    }

    public function test_transcript_falls_back_to_innertube_when_watch_page_has_no_captions(): void
    {
        Http::fake([
            'https://www.youtube.com/watch?v=*' => Http::response('<html><body>No captions here</body></html>', 200),
            'https://www.youtube.com/youtubei/v1/player*' => Http::response([
                'captions' => [
                    'playerCaptionsTracklistRenderer' => [
                        'captionTracks' => [
                            [
                                'baseUrl' => 'https://www.youtube.com/api/timedtext?v=abc12345678&lang=en&kind=asr',
                                'languageCode' => 'en',
                                'kind' => 'asr',
                                'name' => ['simpleText' => 'English (auto-generated)'],
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://www.youtube.com/api/timedtext*' => Http::response([
                'events' => [
                    ['segs' => [['utf8' => 'Hello '], ['utf8' => 'world']]],
                    ['segs' => [['utf8' => "\n"]]],
                    ['segs' => [['utf8' => 'from '], ['utf8' => 'InnerTube']]],
                ],
            ], 200),
        ]);

        $result = (new YouTubeTranscriptService)->extractTranscript('abc12345678');

        $this->assertNotNull($result);
        $this->assertSame('Hello world from InnerTube', $result['full_transcript']);
        $this->assertSame('innertube_android_vr', $result['source']);
        $this->assertSame('en', $result['language']);
        $this->assertFalse($result['is_manual']);

        // The Android VR InnerTube client is attempted first.
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'youtubei/v1/player')
                && ($request['context']['client']['clientName'] ?? null) === 'ANDROID_VR';
        });

        // json3 is requested without dropping YouTube's original query params.
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), 'timedtext')
                && str_contains($request->url(), 'fmt=json3')
                && str_contains($request->url(), 'v=abc12345678')
                && str_contains($request->url(), 'lang=en');
        });
    }

    public function test_transcript_parses_format_3_timedtext_xml(): void
    {
        // YouTube now returns the newer format 3 XML (<body><p>) even when
        // json3 is requested, so the parser must handle both shapes.
        $format3Xml = '<?xml version="1.0" encoding="utf-8" ?><timedtext format="3"><body>'
            .'<p t="1360" d="1680">[Music]</p>'
            .'<p t="18640" d="3240">Hello &amp; welcome</p>'
            .'</body></timedtext>';

        Http::fake([
            'https://www.youtube.com/youtubei/v1/player*' => Http::response([
                'captions' => [
                    'playerCaptionsTracklistRenderer' => [
                        'captionTracks' => [
                            [
                                'baseUrl' => 'https://www.youtube.com/api/timedtext?v=abc12345678&lang=en',
                                'languageCode' => 'en',
                            ],
                        ],
                    ],
                ],
            ], 200),
            'https://www.youtube.com/api/timedtext*' => Http::response($format3Xml, 200),
        ]);

        $result = (new YouTubeTranscriptService)->extractTranscript('abc12345678');

        $this->assertNotNull($result);
        $this->assertSame('[Music] Hello & welcome', $result['full_transcript']);
        $this->assertTrue($result['is_manual']);
    }

    public function test_transcript_uses_watch_page_caption_track_when_available(): void
    {
        $html = '<script>var ytInitialPlayerResponse = {"captions":{"playerCaptionsTracklistRenderer":'
            .'{"captionTracks":[{"baseUrl":"https://www.youtube.com/api/timedtext?v=abc12345678&lang=en",'
            .'"languageCode":"en","name":{"simpleText":"English"}}]}}};</script>';

        Http::fake([
            'https://www.youtube.com/watch?v=*' => Http::response($html, 200),
            'https://www.youtube.com/api/timedtext*' => Http::response([
                'events' => [['segs' => [['utf8' => 'Watch page transcript']]]],
            ], 200),
        ]);

        $result = (new YouTubeTranscriptService)->extractTranscript('abc12345678');

        $this->assertNotNull($result);
        $this->assertSame('Watch page transcript', $result['full_transcript']);
        $this->assertSame('watch_page', $result['source']);
        $this->assertTrue($result['is_manual']);
    }

    public function test_transcript_returns_null_when_all_sources_fail(): void
    {
        Http::fake([
            'https://www.youtube.com/watch?v=*' => Http::response('<html>consent wall</html>', 200),
            'https://www.youtube.com/youtubei/v1/player*' => Http::response([
                'playabilityStatus' => ['status' => 'ERROR'],
            ], 200),
        ]);

        $this->assertNull((new YouTubeTranscriptService)->extractTranscript('abc12345678'));
    }

    public function test_secondary_subtitle_engine_extracts_transcript(): void
    {
        $service = new YouTubeTranscriptService;
        $method = new \ReflectionMethod(YouTubeTranscriptService::class, 'fetchViaSecondaryEngine');

        $result = $method->invoke($service, 'jNQXAC9IVRw');
        $this->assertNotNull($result);
        $this->assertNotEmpty($result['full_transcript']);
        $this->assertSame('secondary_subtitle_engine', $result['source']);
        $this->assertSame('en', $result['language']);
    }
}

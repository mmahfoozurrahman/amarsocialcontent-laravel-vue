<?php

namespace Tests\Feature;

use App\Models\AggregatedContent;
use App\Models\Chapter;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LmsKnowledgeOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_course_chapter_and_lesson(): void
    {
        $user = User::factory()->create();

        // 1. Create Course
        $courseResponse = $this->actingAs($user)
            ->postJson('/api/user/courses', [
                'title' => 'Mastering Laravel Architecture',
                'description' => 'Comprehensive course on advanced Laravel concepts.',
            ]);

        $courseResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Mastering Laravel Architecture',
                    'slug' => 'mastering-laravel-architecture',
                ],
            ]);

        $courseId = $courseResponse->json('data.id');

        // 2. Create Chapter
        $chapterResponse = $this->actingAs($user)
            ->postJson("/api/user/courses/{$courseId}/chapters", [
                'title' => 'Chapter 1: Multi-Tenancy & Security',
                'description' => 'Isolating tenant records cleanly.',
            ]);

        $chapterResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Chapter 1: Multi-Tenancy & Security',
                ],
            ]);

        $chapterId = $chapterResponse->json('data.id');

        // 3. Create Lesson with Custom Notes
        $lessonResponse = $this->actingAs($user)
            ->postJson("/api/user/chapters/{$chapterId}/lessons", [
                'title' => 'Lesson 1.1: IDOR Attack Mitigations',
                'content' => 'Always verify user ownership on the server side.',
            ]);

        $lessonResponse->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'title' => 'Lesson 1.1: IDOR Attack Mitigations',
                    'is_completed' => false,
                ],
            ]);
    }

    public function test_user_can_link_aggregated_article_to_lesson(): void
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

        $course = Course::create([
            'user_id' => $user->id,
            'title' => 'Clean Code Course',
        ]);

        $chapter = Chapter::create([
            'course_id' => $course->id,
            'title' => 'SOLID Foundations',
        ]);

        // Link the article to lesson
        $response = $this->actingAs($user)
            ->postJson("/api/user/chapters/{$chapter->id}/lessons", [
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

        $this->assertDatabaseHas('lessons', [
            'chapter_id' => $chapter->id,
            'aggregated_content_id' => $article->id,
        ]);
    }

    public function test_user_a_cannot_link_user_b_article_to_their_lesson(): void
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

        // Course owned by User A
        $courseA = Course::create([
            'user_id' => $userA->id,
            'title' => 'User A Course',
        ]);

        $chapterA = Chapter::create([
            'course_id' => $courseA->id,
            'title' => 'Chapter A',
        ]);

        // User A attempts to steal and attach User B's article
        $response = $this->actingAs($userA)
            ->postJson("/api/user/chapters/{$chapterA->id}/lessons", [
                'title' => 'Unauthorized Linked Lesson',
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

        $courseB = Course::create([
            'user_id' => $userB->id,
            'title' => 'User B Private Course',
            'visibility' => 'private',
        ]);

        $chapterB = Chapter::create([
            'course_id' => $courseB->id,
            'title' => 'User B Chapter',
        ]);

        $lessonB = Lesson::create([
            'chapter_id' => $chapterB->id,
            'title' => 'User B Lesson',
        ]);

        // 1. User A tries to view User B private course
        $this->actingAs($userA)
            ->getJson("/api/user/courses/{$courseB->id}")
            ->assertStatus(403);

        // 2. User A tries to add a chapter to User B course
        $this->actingAs($userA)
            ->postJson("/api/user/courses/{$courseB->id}/chapters", [
                'title' => 'Hacked Chapter',
            ])->assertStatus(403);

        // 3. User A tries to add a lesson to User B chapter
        $this->actingAs($userA)
            ->postJson("/api/user/chapters/{$chapterB->id}/lessons", [
                'title' => 'Hacked Lesson',
            ])->assertStatus(403);

        // 4. User A tries to update or delete User B lesson
        $this->actingAs($userA)
            ->putJson("/api/user/lessons/{$lessonB->id}", [
                'title' => 'Altered Lesson Title',
            ])->assertStatus(403);

        $this->actingAs($userA)
            ->deleteJson("/api/user/lessons/{$lessonB->id}")
            ->assertStatus(403);
    }

    public function test_lesson_completion_and_course_progress_percentage(): void
    {
        $user = User::factory()->create();

        $course = Course::create([
            'user_id' => $user->id,
            'title' => 'Progress Tracked Course',
        ]);

        $chapter = Chapter::create([
            'course_id' => $course->id,
            'title' => 'Chapter 1',
        ]);

        $lesson1 = Lesson::create([
            'chapter_id' => $chapter->id,
            'title' => 'Lesson 1',
            'is_completed' => false,
        ]);

        $lesson2 = Lesson::create([
            'chapter_id' => $chapter->id,
            'title' => 'Lesson 2',
            'is_completed' => false,
        ]);

        $this->assertEquals(0, $course->progressPercentage());

        // Mark lesson 1 as completed
        $this->actingAs($user)
            ->putJson("/api/user/lessons/{$lesson1->id}", [
                'is_completed' => true,
            ])->assertStatus(200);

        $this->assertEquals(50, $course->fresh()->progressPercentage());

        // Mark lesson 2 as completed
        $this->actingAs($user)
            ->putJson("/api/user/lessons/{$lesson2->id}", [
                'is_completed' => true,
            ])->assertStatus(200);

        $this->assertEquals(100, $course->fresh()->progressPercentage());
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

        $course = Course::create(['user_id' => $user->id, 'title' => 'Test Course']);
        $chapter = Chapter::create(['course_id' => $course->id, 'title' => 'Test Chapter']);

        // Attach article 1 to lesson
        Lesson::create([
            'chapter_id' => $chapter->id,
            'title' => 'Lesson 1',
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


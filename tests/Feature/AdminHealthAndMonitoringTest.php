<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AggregatedContent;
use App\Models\AIProcessingLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHealthAndMonitoringTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_platform_stats(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        AggregatedContent::create([
            'user_id' => $user->id,
            'youtube_video_id' => 'testVideo111',
            'youtube_url' => 'https://www.youtube.com/watch?v=testVideo111',
            'title' => 'Sample Video',
            'channel_name' => 'Tech Channel',
            'content_type' => 'video',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/stats');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'users' => [
                        'total' => 2,
                        'admins' => 1,
                    ],
                    'contents' => [
                        'total' => 1,
                        'completed' => 1,
                        'failed' => 0,
                    ],
                ],
            ]);

        // Audit log created for dashboard view
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'admin_view_dashboard',
        ]);
    }

    public function test_admin_can_view_ai_health_and_logs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        // Seed a processing log
        AIProcessingLog::create([
            'user_id' => $admin->id,
            'provider' => 'groq',
            'model' => 'openai/gpt-oss-120b',
            'operation_type' => 'mentor_synthesis',
            'prompt_tokens' => 1200,
            'completion_tokens' => 850,
            'latency_ms' => 4500,
            'status' => 'success',
        ]);

        // 1. Health check
        $healthResponse = $this->actingAs($admin)->getJson('/api/admin/ai-health');
        $healthResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'metrics' => [
                        'total_calls' => 1,
                        'successful_calls' => 1,
                    ],
                ],
            ]);

        // 2. Paginated logs
        $logsResponse = $this->actingAs($admin)->getJson('/api/admin/ai-logs?provider=groq');
        $logsResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'total' => 1,
                ],
            ]);
    }

    public function test_admin_can_view_filtered_activity_logs(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'user_login_otp',
            'ip_address' => '127.0.0.1',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/activity-logs?action=user_login_otp');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'pagination' => [
                    'total' => 1,
                ],
            ]);
    }

    public function test_regular_user_cannot_access_admin_endpoints(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        $routes = [
            '/api/admin/stats',
            '/api/admin/ai-health',
            '/api/admin/ai-logs',
            '/api/admin/activity-logs',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($regularUser)->getJson($route);
            $response->assertStatus(403);
        }
    }

    public function test_unauthenticated_guest_cannot_access_admin_endpoints(): void
    {
        $response = $this->getJson('/api/admin/stats');
        $response->assertStatus(401);
    }
}


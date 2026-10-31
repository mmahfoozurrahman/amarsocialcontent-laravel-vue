<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAiSetting;
use App\Services\AI\AIServiceManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserProfileAndAiSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_profile_and_ai_settings(): void
    {
        $user = User::factory()->create([
            'name' => 'Tanvir Ahmed',
            'email' => 'tanvir@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/user/profile');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user' => [
                        'name' => 'Tanvir Ahmed',
                        'email' => 'tanvir@example.com',
                    ],
                    'ai_settings' => [
                        'primary_provider' => 'groq',
                    ],
                ],
            ]);
    }

    public function test_user_can_update_profile_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($user)->putJson('/api/user/profile', [
            'name' => 'New Name Updated',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'name' => 'New Name Updated',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name Updated',
        ]);
    }

    public function test_user_can_save_encrypted_ai_keys(): void
    {
        $user = User::factory()->create();

        $groqPlainKey = 'gsk_test1234567890abcdefghijklmnopqrstuvwxyz';
        $geminiPlainKey = 'AIzaSyTest1234567890abcdefghijklmnopqrstuvwxyz';

        $response = $this->actingAs($user)->putJson('/api/user/profile/ai-settings', [
            'primary_provider' => 'groq',
            'groq_api_key' => $groqPlainKey,
            'gemini_api_key' => $geminiPlainKey,
            'groq_model' => 'openai/gpt-oss-120b',
            'gemini_model' => 'gemini-1.5-flash',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'groq_configured' => true,
                    'gemini_configured' => true,
                ],
            ]);

        // Verify the database holds AES-encrypted ciphertext, NEVER plaintext
        $rawRow = DB::table('user_ai_settings')->where('user_id', $user->id)->first();
        $this->assertNotNull($rawRow);
        $this->assertNotEquals($groqPlainKey, $rawRow->groq_api_key);
        $this->assertStringNotContainsString($groqPlainKey, $rawRow->groq_api_key);

        // Verify Eloquent model decrypts on read
        $setting = UserAiSetting::where('user_id', $user->id)->first();
        $this->assertEquals($groqPlainKey, $setting->groq_api_key);
        $this->assertEquals($geminiPlainKey, $setting->gemini_api_key);
    }

    public function test_ai_service_manager_uses_user_specific_keys(): void
    {
        $user = User::factory()->create();

        UserAiSetting::create([
            'user_id' => $user->id,
            'primary_provider' => 'groq',
            'groq_api_key' => 'gsk_custom_user_key_99999',
            'groq_model' => 'openai/gpt-oss-120b',
        ]);

        $manager = app(AIServiceManager::class);
        [$primary, $fallback] = $manager->resolveProvidersForUser($user);

        $this->assertEquals('groq', $primary->getName());
        $this->assertEquals('openai/gpt-oss-120b', $primary->getModel());
    }

    public function test_unauthenticated_user_cannot_access_profile(): void
    {
        $response = $this->getJson('/api/user/profile');
        $response->assertStatus(401);
    }
}


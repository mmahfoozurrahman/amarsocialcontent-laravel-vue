<?php

namespace Tests\Feature\Auth;

use App\Models\OtpVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PasswordlessOtpAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        RateLimiter::clear('otp_cooldown:' . md5('learner@example.com'));
        RateLimiter::clear('otp_hourly:' . md5('learner@example.com'));
    }

    public function test_user_can_request_otp_successfully(): void
    {
        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'learner@example.com',
            'name' => 'Tanvir Ahmed',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'cooldown_seconds' => 60,
            ]);

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'learner@example.com',
            'verified_at' => null,
            'attempts' => 0,
        ]);
    }

    public function test_otp_is_stored_as_secure_hash_not_plaintext(): void
    {
        $this->postJson('/api/auth/send-otp', [
            'email' => 'learner@example.com',
        ]);

        $otpRecord = OtpVerification::where('email', 'learner@example.com')->first();
        $this->assertNotNull($otpRecord);
        $this->assertNotEquals(6, strlen($otpRecord->otp_hash));
        $this->assertTrue(str_starts_with($otpRecord->otp_hash, '$2y$') || strlen($otpRecord->otp_hash) >= 60);
    }

    public function test_resend_cooldown_prevents_immediate_subsequent_requests(): void
    {
        // First request
        $this->postJson('/api/auth/send-otp', ['email' => 'learner@example.com'])
            ->assertStatus(200);

        // Immediate second request
        $secondResponse = $this->postJson('/api/auth/send-otp', ['email' => 'learner@example.com']);
        $secondResponse->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_verify_otp_and_authenticate(): void
    {
        $otp = '582910';
        OtpVerification::create([
            'email' => 'learner@example.com',
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'learner@example.com',
            'otp' => $otp,
            'name' => 'Tanvir Ahmed',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'learner@example.com',
            'name' => 'Tanvir Ahmed',
            'role' => 'user',
        ]);

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'learner@example.com',
        ]);

        $record = OtpVerification::where('email', 'learner@example.com')->first();
        $this->assertNotNull($record->verified_at);
    }

    public function test_wrong_otp_increments_attempts_counter(): void
    {
        OtpVerification::create([
            'email' => 'learner@example.com',
            'otp_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'learner@example.com',
            'otp' => '999999',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);

        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'learner@example.com',
            'attempts' => 1,
        ]);
    }

    public function test_exceeding_max_attempts_invalidates_otp(): void
    {
        OtpVerification::create([
            'email' => 'learner@example.com',
            'otp_hash' => Hash::make('123456'),
            'attempts' => 5,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'learner@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(422);
        // Record deleted or invalidated
        $this->assertDatabaseMissing('otp_verifications', [
            'email' => 'learner@example.com',
        ]);
    }

    public function test_expired_otp_fails_verification(): void
    {
        OtpVerification::create([
            'email' => 'learner@example.com',
            'otp_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => Carbon::now()->subMinute(),
        ]);

        $response = $this->postJson('/api/auth/verify-otp', [
            'email' => 'learner@example.com',
            'otp' => '123456',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_authenticated_user_can_access_profile_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'active@example.com',
            'role' => 'user',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $meResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/auth/me');

        $meResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user' => [
                    'email' => 'active@example.com',
                ],
            ]);

        $logoutResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $logoutResponse->assertStatus(200);
    }

    public function test_role_middleware_blocks_regular_user_from_admin_routes(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/ping');

        $response->assertStatus(403);
    }

    public function test_admin_user_can_access_admin_routes(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $token = $admin->createToken('admin_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/admin/ping');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Admin authorized successfully.',
            ]);
    }

    public function test_demo_mode_returns_debug_otp_on_screen(): void
    {
        config(['auth.demo_otp' => true]);

        RateLimiter::clear('otp_cooldown:' . md5('demo@example.com'));
        RateLimiter::clear('otp_hourly:' . md5('demo@example.com'));

        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'demo@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'demo_mode' => true,
            ]);

        $this->assertNotNull($response->json('debug_otp'));
        $this->assertEquals(6, strlen($response->json('debug_otp')));
    }

    public function test_live_mode_does_not_expose_debug_otp(): void
    {
        config(['auth.demo_otp' => false]);

        RateLimiter::clear('otp_cooldown:' . md5('live@example.com'));
        RateLimiter::clear('otp_hourly:' . md5('live@example.com'));

        $response = $this->postJson('/api/auth/send-otp', [
            'email' => 'live@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'demo_mode' => false,
                'debug_otp' => null,
            ]);
    }
}


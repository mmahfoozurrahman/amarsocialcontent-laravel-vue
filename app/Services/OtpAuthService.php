<?php

namespace App\Services;

use App\Mail\OtpMail;
use App\Models\ActivityLog;
use App\Models\OtpVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpAuthService
{
    const EXPIRY_MINUTES = 5;
    const MAX_ATTEMPTS = 5;
    const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Send or resend an OTP code to the provided email.
     */
    public function sendOtp(string $email, ?string $name = null): array
    {
        $normalizedEmail = strtolower(trim($email));
        $isDemoMode = (bool) config('auth.demo_otp', env('AUTH_DEMO_OTP', !app()->isProduction()));

        // Check 60-second cooldown
        $cooldownKey = 'otp_cooldown:' . md5($normalizedEmail);
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $secondsRemaining = RateLimiter::availableIn($cooldownKey);
            throw ValidationException::withMessages([
                'email' => ["অনুগ্রহ করে পুনরায় ওটিপি চাওয়ার আগে {$secondsRemaining} সেকেন্ড অপেক্ষা করুন।"],
            ]);
        }

        // Check hourly rate limit (max 5 requests per hour)
        $hourlyKey = 'otp_hourly:' . md5($normalizedEmail);
        if (RateLimiter::tooManyAttempts($hourlyKey, 5)) {
            $minutesRemaining = ceil(RateLimiter::availableIn($hourlyKey) / 60);
            throw ValidationException::withMessages([
                'email' => ["আপনি প্রতি ঘণ্টায় সর্বোচ্চ ওটিপি কোড অনুরোধের সীমা অতিক্রম করেছেন। {$minutesRemaining} মিনিট পর চেষ্টা করুন।"],
            ]);
        }

        // Generate cryptographically secure 6-digit code
        $otp = sprintf('%06d', random_int(100000, 999999));

        // Invalidate older pending OTPs for this email
        OtpVerification::where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->delete();

        // Create new hashed OTP record
        OtpVerification::create([
            'email' => $normalizedEmail,
            'otp_hash' => Hash::make($otp),
            'attempts' => 0,
            'expires_at' => Carbon::now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        // Hit rate limiters
        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN_SECONDS);
        RateLimiter::hit($hourlyKey, 3600);

        // Send email (always attempted, logged if mail driver is log or fails)
        try {
            Mail::to($normalizedEmail)->send(new OtpMail($otp, self::EXPIRY_MINUTES));
        } catch (\Throwable $e) {
            \Log::error('OTP Email Send Failed: ' . $e->getMessage(), ['email' => $normalizedEmail]);
        }

        return [
            'success' => true,
            'message' => $isDemoMode
                ? 'ডেমো মোড সক্রিয়: আপনার ওটিপি কোডটি স্ক্রিনে নিচে প্রদর্শিত হচ্ছে।'
                : 'আপনার ইমেইলে একটি ৬-সংখ্যার ওটিপি কোড পাঠানো হয়েছে।',
            'cooldown_seconds' => self::RESEND_COOLDOWN_SECONDS,
            'expires_in_minutes' => self::EXPIRY_MINUTES,
            'demo_mode' => $isDemoMode,
            'debug_otp' => $isDemoMode ? $otp : null,
        ];
    }

    /**
     * Verify the provided OTP code and authenticate the user.
     */
    public function verifyOtp(string $email, string $otp, ?string $name = null, ?string $ip = null, ?string $userAgent = null): array
    {
        $normalizedEmail = strtolower(trim($email));

        $otpRecord = OtpVerification::where('email', $normalizedEmail)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (!$otpRecord) {
            throw ValidationException::withMessages([
                'otp' => ['কোনো সক্রিয় ওটিপি কোড পাওয়া যায়নি। অনুগ্রহ করে নতুন কোডের জন্য অনুরোধ করুন।'],
            ]);
        }

        // Check if expired
        if ($otpRecord->isExpired()) {
            throw ValidationException::withMessages([
                'otp' => ['ওটিপি কোডের মেয়াদ শেষ হয়ে গেছে। অনুগ্রহ করে নতুন কোডের অনুরোধ করুন।'],
            ]);
        }

        // Check maximum failed attempts
        if ($otpRecord->attempts >= self::MAX_ATTEMPTS) {
            $otpRecord->delete(); // Invalidate
            throw ValidationException::withMessages([
                'otp' => ['সর্বোচ্চ ভুল চেষ্টার সীমা অতিক্রান্ত হয়েছে। কোডটি বাতিল করা হয়েছে, নতুন কোড নিন।'],
            ]);
        }

        // Check OTP hash
        if (!Hash::check($otp, $otpRecord->otp_hash)) {
            $otpRecord->increment('attempts');
            $remaining = self::MAX_ATTEMPTS - $otpRecord->attempts;
            throw ValidationException::withMessages([
                'otp' => ["ভুল ওটিপি কোড। আপনার আর {$remaining} বার চেষ্টা করার সুযোগ রয়েছে।"],
            ]);
        }

        // Mark OTP as verified
        $otpRecord->update(['verified_at' => Carbon::now()]);

        // Find or create user
        $isNewUser = !User::where('email', $normalizedEmail)->exists();

        $user = User::firstOrCreate(
            ['email' => $normalizedEmail],
            [
                'name' => $name ? trim($name) : explode('@', $normalizedEmail)[0],
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => Carbon::now(),
            ]
        );

        if (!$user->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['আপনার অ্যাকাউন্টটি সাময়িকভাবে নিষ্ক্রিয় রয়েছে। অনুগ্রহ করে সহায়তায় যোগাযোগ করুন।'],
            ]);
        }

        // Generate Sanctum access token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Log activity
        ActivityLog::create([
            'user_id' => $user->id,
            'action' => $isNewUser ? 'auth.registered' : 'auth.login',
            'entity_type' => User::class,
            'entity_id' => $user->id,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'details' => ['method' => 'otp'],
        ]);

        return [
            'success' => true,
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
            ],
            'token' => $token,
            'message' => $isNewUser ? 'অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে।' : 'সফলভাবে লগইন হয়েছে।',
        ];
    }
}


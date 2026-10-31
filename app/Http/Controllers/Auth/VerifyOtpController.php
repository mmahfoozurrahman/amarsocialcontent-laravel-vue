<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Services\OtpAuthService;
use Illuminate\Http\JsonResponse;

class VerifyOtpController extends Controller
{
    public function __construct(
        protected OtpAuthService $otpService
    ) {}

    /**
     * Handle OTP verification and user login/registration.
     */
    public function __invoke(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->otpService->verifyOtp(
            email: $validated['email'],
            otp: $validated['otp'],
            name: $validated['name'] ?? null,
            ip: $request->ip(),
            userAgent: $request->userAgent()
        );

        return response()->json($result);
    }
}


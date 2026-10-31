<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Services\OtpAuthService;
use Illuminate\Http\JsonResponse;

class LoginOtpController extends Controller
{
    public function __construct(
        protected OtpAuthService $otpService
    ) {}

    /**
     * Handle the incoming request to generate and send an OTP.
     */
    public function __invoke(SendOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->otpService->sendOtp(
            email: $validated['email'],
            name: $validated['name'] ?? null
        );

        return response()->json($result);
    }
}


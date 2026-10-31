<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Invalidate the authenticated user's current token or session.
     */
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()?->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'সফলভাবে লগআউট সম্পন্ন হয়েছে।',
        ]);
    }
}


<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request and ensure role access matches.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'অননুমোদিত অ্যাক্সেস। অনুগ্রহ করে লগইন করুন।',
            ], 401);
        }

        if ($user->role !== $role) {
            return response()->json([
                'success' => false,
                'message' => 'এই অ্যাকশনটি সম্পন্ন করার প্রয়োজনীয় অনুমতি আপনার নেই।',
            ], 403);
        }

        return $next($request);
    }
}


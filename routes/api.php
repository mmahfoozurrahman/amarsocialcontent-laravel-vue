<?php

use App\Http\Controllers\Auth\CurrentUserController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\Public\PublicArticleController;
use App\Http\Controllers\User\AggregatedContentController;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\ChapterController;
use App\Http\Controllers\User\CourseController;
use App\Http\Controllers\User\LessonController;
use App\Http\Controllers\User\PersonalNoteController;
use App\Http\Controllers\User\ReadingHistoryController;
use App\Http\Controllers\User\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication Routes (Passwordless OTP)
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('/send-otp', LoginOtpController::class);
    Route::post('/verify-otp', VerifyOtpController::class);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', CurrentUserController::class);
        Route::post('/logout', LogoutController::class);
    });
});

/*
|--------------------------------------------------------------------------
| Authenticated User Routes (Multi-Tenant Scoped)
|--------------------------------------------------------------------------
*/
Route::prefix('user')->middleware(['auth:sanctum'])->group(function () {
    Route::get('/ping', fn () => response()->json(['message' => 'User authenticated successfully.']));

    // YouTube Aggregation & Content Management
    Route::get('/articles', [AggregatedContentController::class, 'index']);
    Route::post('/articles', [AggregatedContentController::class, 'store']);
    Route::get('/articles/{id}', [AggregatedContentController::class, 'show']);
    Route::put('/articles/{id}', [AggregatedContentController::class, 'update']);
    Route::post('/articles/{id}/manual-transcript', [AggregatedContentController::class, 'submitManualTranscript']);
    Route::post('/articles/{id}/retry', [AggregatedContentController::class, 'retry']);
    Route::delete('/articles/{id}', [AggregatedContentController::class, 'destroy']);

    // Tag Management
    Route::get('/tags', [TagController::class, 'index']);
    Route::post('/tags', [TagController::class, 'store']);
    Route::put('/tags/{id}', [TagController::class, 'update']);
    Route::delete('/tags/{id}', [TagController::class, 'destroy']);
    Route::post('/articles/{id}/tags', [TagController::class, 'syncArticleTags']);

    // Personal Notes
    Route::post('/articles/{id}/notes', [PersonalNoteController::class, 'updateOrCreate']);
    Route::delete('/articles/{id}/notes', [PersonalNoteController::class, 'destroy']);

    // Bookmarks
    Route::get('/bookmarks', [BookmarkController::class, 'index']);
    Route::post('/articles/{id}/bookmark', [BookmarkController::class, 'toggle']);

    // Reading & Study Tracker
    Route::post('/articles/{id}/reading-progress', [ReadingHistoryController::class, 'updateProgress']);
    Route::get('/reading-timeline', [ReadingHistoryController::class, 'getTimeline']);

    // LMS Knowledge Organization: Courses
    Route::get('/courses', [CourseController::class, 'index']);
    Route::post('/courses', [CourseController::class, 'store']);
    Route::get('/courses/{id}', [CourseController::class, 'show']);
    Route::put('/courses/{id}', [CourseController::class, 'update']);
    Route::delete('/courses/{id}', [CourseController::class, 'destroy']);
    Route::post('/courses/reorder', [CourseController::class, 'reorder']);

    // LMS Knowledge Organization: Chapters
    Route::post('/courses/{courseId}/chapters', [ChapterController::class, 'store']);
    Route::put('/chapters/{id}', [ChapterController::class, 'update']);
    Route::delete('/chapters/{id}', [ChapterController::class, 'destroy']);
    Route::post('/courses/{courseId}/chapters/reorder', [ChapterController::class, 'reorder']);

    // LMS Knowledge Organization: Lessons
    Route::post('/chapters/{chapterId}/lessons', [LessonController::class, 'store']);
    Route::get('/lessons/{id}', [LessonController::class, 'show']);
    Route::put('/lessons/{id}', [LessonController::class, 'update']);
    Route::delete('/lessons/{id}', [LessonController::class, 'destroy']);
    Route::post('/chapters/{chapterId}/lessons/reorder', [LessonController::class, 'reorder']);
    Route::get('/unassigned-articles', [LessonController::class, 'unassignedArticles']);

    // Semantic & Hybrid Search
    Route::get('/search', [\App\Http\Controllers\User\SearchController::class, 'search']);
    Route::get('/search/history', [\App\Http\Controllers\User\SearchController::class, 'history']);
    Route::delete('/search/history', [\App\Http\Controllers\User\SearchController::class, 'clearHistory']);

    // Google Takeout YouTube Watch History Import & Curation
    Route::get('/watch-history/server-file-status', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'serverFileStatus']);
    Route::post('/watch-history/load-server-file', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'loadServerFile']);
    Route::post('/watch-history/upload', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'upload']);
    Route::get('/watch-history/items', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'items']);
    Route::get('/watch-history/channels', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'channels']);
    Route::post('/watch-history/import', [\App\Http\Controllers\User\WatchHistoryImportController::class, 'import']);

    // User Profile & Personal Encrypted AI Settings
    Route::get('/profile', [\App\Http\Controllers\User\ProfileController::class, 'show']);
    Route::put('/profile', [\App\Http\Controllers\User\ProfileController::class, 'updateProfile']);
    Route::put('/profile/ai-settings', [\App\Http\Controllers\User\ProfileController::class, 'updateAiSettings']);
    Route::post('/profile/test-ai', [\App\Http\Controllers\User\ProfileController::class, 'testAiConnection']);
});

/*
|--------------------------------------------------------------------------
| Public Sharing Routes (Quarantined)
|--------------------------------------------------------------------------
*/
Route::prefix('public')->group(function () {
    Route::get('/ping', fn () => response()->json(['message' => 'Public API online.']));
    Route::get('/articles/{shareToken}', [PublicArticleController::class, 'show']);
});

/*
|--------------------------------------------------------------------------
| Admin Routes (Elevated Role-Protected)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::get('/ping', fn () => response()->json(['message' => 'Admin authorized successfully.']));
    Route::get('/stats', [\App\Http\Controllers\Admin\AdminDashboardController::class, 'stats']);
    Route::get('/ai-health', [\App\Http\Controllers\Admin\AIHealthController::class, 'health']);
    Route::get('/ai-logs', [\App\Http\Controllers\Admin\AIHealthController::class, 'logs']);
    Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index']);
});

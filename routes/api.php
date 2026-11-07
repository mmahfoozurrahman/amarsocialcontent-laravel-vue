<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AIHealthController;
use App\Http\Controllers\Auth\CurrentUserController;
use App\Http\Controllers\Auth\LoginOtpController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\VerifyOtpController;
use App\Http\Controllers\Public\PublicArticleController;
use App\Http\Controllers\User\AggregatedContentController;
use App\Http\Controllers\User\BookmarkController;
use App\Http\Controllers\User\CollectionController;
use App\Http\Controllers\User\NoteController;
use App\Http\Controllers\User\PersonalNoteController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReadingHistoryController;
use App\Http\Controllers\User\SearchController;
use App\Http\Controllers\User\SectionController;
use App\Http\Controllers\User\TagController;
use App\Http\Controllers\User\WatchHistoryImportController;
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

    // Second Brain Knowledge Organization: Collections
    Route::get('/collections', [CollectionController::class, 'index']);
    Route::post('/collections', [CollectionController::class, 'store']);
    Route::get('/collections/{id}', [CollectionController::class, 'show']);
    Route::put('/collections/{id}', [CollectionController::class, 'update']);
    Route::delete('/collections/{id}', [CollectionController::class, 'destroy']);
    Route::post('/collections/reorder', [CollectionController::class, 'reorder']);

    // Second Brain Knowledge Organization: Sections
    Route::post('/collections/{collectionId}/sections', [SectionController::class, 'store']);
    Route::put('/sections/{id}', [SectionController::class, 'update']);
    Route::delete('/sections/{id}', [SectionController::class, 'destroy']);
    Route::post('/collections/{collectionId}/sections/reorder', [SectionController::class, 'reorder']);

    // Second Brain Knowledge Organization: Notes
    Route::post('/sections/{sectionId}/notes', [NoteController::class, 'store']);
    Route::get('/notes/{id}', [NoteController::class, 'show']);
    Route::put('/notes/{id}', [NoteController::class, 'update']);
    Route::delete('/notes/{id}', [NoteController::class, 'destroy']);
    Route::post('/sections/{sectionId}/notes/reorder', [NoteController::class, 'reorder']);
    Route::get('/unassigned-articles', [NoteController::class, 'unassignedArticles']);

    // Semantic & Hybrid Search
    Route::get('/search', [SearchController::class, 'search']);
    Route::get('/search/history', [SearchController::class, 'history']);
    Route::delete('/search/history', [SearchController::class, 'clearHistory']);

    // Google Takeout YouTube Watch History Import & Curation
    Route::get('/watch-history/server-file-status', [WatchHistoryImportController::class, 'serverFileStatus']);
    Route::post('/watch-history/load-server-file', [WatchHistoryImportController::class, 'loadServerFile']);
    Route::post('/watch-history/upload', [WatchHistoryImportController::class, 'upload']);
    Route::get('/watch-history/items', [WatchHistoryImportController::class, 'items']);
    Route::get('/watch-history/channels', [WatchHistoryImportController::class, 'channels']);
    Route::post('/watch-history/import', [WatchHistoryImportController::class, 'import']);

    // User Profile & Personal Encrypted AI Settings
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'updateProfile']);
    Route::put('/profile/ai-settings', [ProfileController::class, 'updateAiSettings']);
    Route::post('/profile/test-ai', [ProfileController::class, 'testAiConnection']);
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
    Route::get('/stats', [AdminDashboardController::class, 'stats']);
    Route::get('/ai-health', [AIHealthController::class, 'health']);
    Route::get('/ai-logs', [AIHealthController::class, 'logs']);
    Route::get('/activity-logs', [ActivityLogController::class, 'index']);
});

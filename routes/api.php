<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\BlogCommentController as AdminBlogCommentController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\CvController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogCommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\SiteController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/admin/login', [AuthController::class, 'login']);
Route::post('/admin/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get("/projects", [SiteController::class, "show_projects"]);
Route::post('/contact', [ContactController::class, 'store']);
Route::get('/cv/active', [ContactController::class, 'activeCv']);

Route::get("/posts", [SiteController::class, "show_posts"]);
Route::get("/posts/{post}", [SiteController::class, "post"]);
Route::get("/posts/{post}/comments", [BlogCommentController::class, "index"]);
Route::post("/posts/{post}/comments", [BlogCommentController::class, "store"]);
Route::get("/projects/{project}", [SiteController::class, "show_project"]);

Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::apiResource("categories", CategoryController::class);
    Route::apiResource("tags", TagController::class);
    Route::apiResource("projects", ProjectController::class);
    Route::apiResource("posts", PostController::class);
    Route::apiResource("cv", CvController::class);

    Route::prefix('comments')->controller(AdminBlogCommentController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::post('/{comment}/approve', 'approve');
        Route::post('/{comment}/pending', 'markPending');
        Route::post('/{comment}/spam', 'markSpam');
        Route::delete('/{comment}', 'destroy');
    });

    Route::prefix('dashboard')->controller(DashboardController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/recent-activities', 'recentActivities');
        Route::get('/popular-posts', 'popularPosts');
        Route::get('/popular-projects', 'popularProjects');
        Route::get('/posts-analytics', 'postsAnalytics');
        Route::get('/page-visits-analytics', 'pageVisitsAnalytics');
        Route::get('/recent-visits', 'recentVisits');
        Route::get('/top-pages', 'topPages');
        Route::get('/post-performance', 'postPerformance');
        Route::get('/contacts-analytics', 'contactsAnalytics');
    });

    Route::prefix('contact-messages')->controller(ContactMessageController::class)->group(function () {
        Route::get('/', 'index');
        Route::get('/stats', 'stats');
        Route::get('/unread-count', 'unreadCount');
        Route::get('/{contactMessage}', 'show');
        Route::post('/{contactMessage}/mark-as-read', 'markAsRead');
        Route::post('/{contactMessage}/mark-as-replied', 'markAsReplied');
        Route::post('/bulk-mark-as-read', 'bulkMarkAsRead');
        Route::post('/bulk-delete', 'bulkDelete');
        Route::delete('/{contactMessage}', 'destroy');
    });
});

<?php

use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/projects', [SiteController::class, 'show_projects_view']);
Route::get("/projects/{slug}", [SiteController::class, "show_project_view"]);
Route::get('/blog', [SiteController::class, 'show_blog_posts_view']);
Route::get("/blog/{slug}", [SiteController::class, "show_blog_post_view"])->name("blog.show");

Route::get('/{any}', function () {
    return view('admin');
})->where('any', '.*');
<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\Seo\RobotsController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/', [SiteController::class, 'show_home_view'])->name('seo.public.home');

Route::get('/projects', [SiteController::class, 'show_projects_view'])->name('seo.public.projects.index');
Route::get('/projects/{slug}', [SiteController::class, 'show_project_view'])->name('seo.public.projects.show');
Route::get('/blog', [SiteController::class, 'show_blog_posts_view'])->name('seo.public.blog.index');
Route::get('/blog/{slug}', [SiteController::class, 'show_blog_post_view'])->name('seo.public.blog.show');
Route::get('/cv/download', [ContactController::class, 'downloadActiveCv'])->name('cv.download.active');

Route::get('/sitemap.xml', SitemapController::class)->name('seo.infrastructure.sitemap');
Route::get('/robots.txt', RobotsController::class)->name('seo.infrastructure.robots');
Route::get('/{indexNowKey}.txt', function (string $indexNowKey) {
    $configuredKey = (string) config('indexnow.key');

    abort_unless(hash_equals($configuredKey, $indexNowKey), 404);

    return response($configuredKey, 200, [
        'Content-Type' => 'text/plain; charset=UTF-8',
        'Cache-Control' => 'public, max-age=3600',
        'X-Content-Type-Options' => 'nosniff',
    ]);
})->where('indexNowKey', '[A-Za-z0-9-]{8,128}')
    ->name('seo.infrastructure.indexnow-key');

Route::view('/admin', 'admin')->name('admin.app');
Route::get('/admin/{any}', function () {
    return view('admin');
})->where('any', '.*')->name('admin.app.path');

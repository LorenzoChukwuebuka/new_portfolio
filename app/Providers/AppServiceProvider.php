<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\Project;
use App\Observers\PostObserver;
use App\Observers\ProjectObserver;
use App\Support\IndexNow\IndexNowOutboxDispatcher;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Post::observe(PostObserver::class);
        Project::observe(ProjectObserver::class);

        // Laravel sends the web response before termination callbacks. This
        // keeps IndexNow network work off the page-rendering path while the
        // database outbox remains available for later recovery.
        if (! $this->app->runningInConsole()
            && $this->app->environment('production')
            && config('indexnow.enabled')
        ) {
            $this->app->terminating(function (): void {
                try {
                    app(IndexNowOutboxDispatcher::class)->dispatchDue(
                        (int) config('indexnow.request_batch_size', 100),
                    );
                } catch (Throwable $exception) {
                    Log::error('IndexNow outbox delivery failed before its result could be recorded.', [
                        'exception' => $exception::class,
                    ]);
                }
            });
        }
    }
}

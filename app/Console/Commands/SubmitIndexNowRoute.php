<?php

namespace App\Console\Commands;

use App\Support\IndexNow\IndexNowOutbox;
use App\Support\IndexNow\IndexNowOutboxDispatcher;
use App\Support\IndexNow\IndexNowService;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Console\Command;

class SubmitIndexNowRoute extends Command
{
    protected $signature = 'indexnow:submit-route {route : Existing named seo.public static route}
        {--execute : Verify the production key URL and queue this route}';

    protected $description = 'Preview or notify IndexNow about one public static route after its source content changes.';

    public function handle(
        SitemapUrlDiscovery $discovery,
        IndexNowService $service,
        IndexNowOutbox $outbox,
        IndexNowOutboxDispatcher $dispatcher,
    ): int {
        try {
            $url = $discovery->publicStaticRouteUrl((string) $this->argument('route'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info($url);

        if (! $this->option('execute')) {
            $this->comment('Dry run only. Use --execute only after this public route content changes.');

            return self::SUCCESS;
        }

        if (! app()->environment('production') || ! $service->canSubmit() || ! $service->verifyKeyFile()) {
            $this->error('Production key verification failed; no URL was queued.');

            return self::FAILURE;
        }

        $outbox->enqueue($url, 'static-route-updated');
        $processed = $dispatcher->dispatchDue(1);
        $this->info("Queued static route update; delivery attempts made: {$processed}.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\IndexNowBulkRun;
use App\Support\IndexNow\IndexNowOutbox;
use App\Support\IndexNow\IndexNowOutboxDispatcher;
use App\Support\IndexNow\IndexNowService;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SubmitExistingIndexNowUrls extends Command
{
    protected $signature = 'indexnow:submit-existing
        {--execute : Verify the live key file and queue the existing public URLs}
        {--verify-key : Verify the configured production key file without submitting URLs}
        {--batch=100 : URLs per request, from 1 through 10000}
        {--max-batches=100 : Maximum requests during this invocation}';

    protected $description = 'Preview or submit the existing eligible sitemap URLs to IndexNow once.';

    private const RUN_NAME = 'existing-public-urls-v1';

    public function handle(
        SitemapUrlDiscovery $discovery,
        IndexNowService $service,
        IndexNowOutbox $outbox,
        IndexNowOutboxDispatcher $dispatcher,
    ): int {
        $urls = array_values(array_unique($discovery->discover()));
        $this->info('Eligible public canonical URLs discovered: '.count($urls));

        foreach ($urls as $url) {
            $this->line('  '.$url);
        }

        $verifyOnly = (bool) $this->option('verify-key');
        $execute = (bool) $this->option('execute');

        if ($verifyOnly && $execute) {
            $this->error('Choose either --verify-key or --execute.');

            return self::FAILURE;
        }

        if (! $execute && ! $verifyOnly) {
            $this->comment('Dry run only. Add --execute after the production key URL has been verified.');

            return self::SUCCESS;
        }

        if (! app()->environment('production') || ! $service->canSubmit()) {
            $this->error('Bulk submissions can run only in production with a valid HTTPS canonical URL and configured IndexNow key.');

            return self::FAILURE;
        }

        $this->line('Configured production key URL: '.$service->keyLocation());

        if (! $service->verifyKeyFile()) {
            $this->error('The production key URL did not return HTTP 200, exact key text, and a plain-text content type. No URLs were queued.');

            return self::FAILURE;
        }

        $this->info('Verified the configured production key file: '.$service->keyLocation());

        if ($verifyOnly) {
            $this->comment('Verification only. No URLs were queued or submitted.');

            return self::SUCCESS;
        }

        $batchSize = (int) $this->option('batch');
        $maxBatches = (int) $this->option('max-batches');

        if ($batchSize < 1 || $batchSize > 10000 || $maxBatches < 1) {
            $this->error('Use --batch from 1 through 10000 and a positive --max-batches value.');

            return self::FAILURE;
        }

        $run = IndexNowBulkRun::query()->where('name', self::RUN_NAME)->first();

        if ($run?->status === 'complete') {
            $this->error('The one-time existing-URLs run is already complete. No repeated bulk submission was made.');

            return self::FAILURE;
        }

        if ($run?->status === 'failed') {
            $this->error('The one-time run has permanently failed URLs. Use indexnow:deliver-pending --retry-failed to resume only those records.');

            return self::FAILURE;
        }

        if (! $run) {
            DB::transaction(function () use ($urls, $outbox, &$run): void {
                $run = IndexNowBulkRun::query()->create([
                    'name' => self::RUN_NAME,
                    'status' => $urls === [] ? 'complete' : 'pending',
                    'total_urls' => count($urls),
                    'started_at' => now(),
                    'completed_at' => $urls === [] ? now() : null,
                ]);

                foreach ($urls as $url) {
                    $outbox->enqueue($url, 'initial-submission', bulkRunId: $run->id);
                }
            });
        }

        if ($run->status === 'complete') {
            $this->info('No eligible URLs were available; the one-time run is complete with zero submissions.');

            return self::SUCCESS;
        }

        $processed = 0;
        for ($batch = 0; $batch < $maxBatches; $batch++) {
            $count = $dispatcher->dispatchDue($batchSize);

            if ($count === 0) {
                break;
            }

            $processed += $count;
        }

        $run->refresh();
        $counts = $run->notifications()
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->info("Run status: {$run->status}; processed now: {$processed}; submitted: ".(int) ($counts['submitted'] ?? 0)
            .'; pending: '.(int) ($counts['pending'] ?? 0)
            .'; failed: '.(int) ($counts['failed'] ?? 0).'.');

        return $run->status === 'complete' ? self::SUCCESS : self::FAILURE;
    }
}

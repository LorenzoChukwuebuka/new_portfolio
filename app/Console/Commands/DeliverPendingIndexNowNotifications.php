<?php

namespace App\Console\Commands;

use App\Support\IndexNow\IndexNowOutboxDispatcher;
use App\Support\IndexNow\IndexNowService;
use Illuminate\Console\Command;

class DeliverPendingIndexNowNotifications extends Command
{
    protected $signature = 'indexnow:deliver-pending
        {--batch=100 : URLs per request, from 1 through 10000}
        {--max-batches=5 : Maximum requests during this invocation}
        {--retry-failed : Requeue permanently failed notifications before delivery}';

    protected $description = 'Deliver or recover durable pending IndexNow URL notifications.';

    public function handle(IndexNowOutboxDispatcher $dispatcher, IndexNowService $service): int
    {
        if (! app()->environment('production') || ! $service->canSubmit()) {
            $this->error('IndexNow delivery is disabled outside production or the canonical URL/key configuration is invalid.');

            return self::FAILURE;
        }

        $batchSize = (int) $this->option('batch');
        $maxBatches = (int) $this->option('max-batches');

        if ($batchSize < 1 || $batchSize > 10000 || $maxBatches < 1) {
            $this->error('Use --batch from 1 through 10000 and a positive --max-batches value.');

            return self::FAILURE;
        }

        if ($this->option('retry-failed')) {
            $this->info('Requeued '.$dispatcher->retryFailed().' failed notification(s).');
        }

        $processed = 0;

        for ($batch = 0; $batch < $maxBatches; $batch++) {
            $count = $dispatcher->dispatchDue($batchSize);

            if ($count === 0) {
                break;
            }

            $processed += $count;
        }

        $this->info("Processed {$processed} URL notification(s). Pending rows remain durable for a later retry.");

        return self::SUCCESS;
    }
}

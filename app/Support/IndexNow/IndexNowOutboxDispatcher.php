<?php

namespace App\Support\IndexNow;

use App\Models\IndexNowBulkRun;
use App\Models\IndexNowNotification;
use App\Models\Post;
use App\Support\Seo\SitemapUrlDiscovery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IndexNowOutboxDispatcher
{
    public function __construct(private readonly IndexNowService $service) {}

    /**
     * Submit one bounded batch. Entries are claimed in a short DB transaction;
     * HTTP happens after the transaction closes.
     */
    public function dispatchDue(?int $limit = null): int
    {
        if (! $this->service->canSubmit()) {
            return 0;
        }

        $limit = min(10000, max(1, $limit ?? (int) config('indexnow.request_batch_size', 100)));
        $now = now();
        $staleClaim = $now->copy()->subMinutes((int) config('indexnow.lease_minutes', 2));
        $maxAttempts = (int) config('indexnow.max_delivery_attempts', 5);

        $claimed = DB::transaction(function () use ($now, $staleClaim, $limit, $maxAttempts): array {
            $items = IndexNowNotification::query()
                ->where(function ($query) use ($now, $staleClaim) {
                    $query->where(function ($pending) use ($now) {
                        $pending->where('status', 'pending')
                            ->where(function ($available) use ($now) {
                                $available->whereNull('available_at')->orWhere('available_at', '<=', $now);
                            });
                    })->orWhere(function ($processing) use ($staleClaim) {
                        $processing->where('status', 'processing')
                            ->where('claimed_at', '<=', $staleClaim);
                    });
                })
                ->orderBy('id')
                ->lockForUpdate()
                ->limit($limit)
                ->get();

            $claimed = [];

            foreach ($items as $item) {
                if ($item->attempts >= $maxAttempts) {
                    $item->update(['status' => 'failed', 'last_error' => 'attempt_limit_reached']);

                    continue;
                }

                if ($item->subject_type === 'scheduled-post' && ! $this->scheduledPostStillPublic($item)) {
                    $item->update(['status' => 'cancelled', 'last_error' => null]);

                    continue;
                }

                $attempt = $item->attempts + 1;
                $item->update([
                    'status' => 'processing',
                    'attempts' => $attempt,
                    'claimed_at' => $now,
                ]);
                $claimed[] = [
                    'id' => $item->id,
                    'url' => $item->url,
                    'attempts' => $attempt,
                    'bulk_run_id' => $item->bulk_run_id,
                ];
            }

            return $claimed;
        });

        if ($claimed === []) {
            return 0;
        }

        $result = $this->service->submit(array_column($claimed, 'url'));
        $ids = array_column($claimed, 'id');
        $runIds = array_values(array_unique(array_filter(array_column($claimed, 'bulk_run_id'))));

        if ($result->accepted) {
            IndexNowNotification::query()->whereIn('id', $ids)->update([
                'status' => 'submitted',
                'claimed_at' => null,
                'last_response_code' => $result->statusCode,
                'last_error' => null,
                'updated_at' => now(),
            ]);

            Log::info('IndexNow accepted URL notifications.', [
                'url_count' => count($ids),
                'http_status' => $result->statusCode,
            ]);
        } else {
            foreach ($claimed as $entry) {
                $willRetry = $result->retryable && $entry['attempts'] < $maxAttempts;
                $delay = $result->statusCode === 429
                    ? max(600, $result->retryAfterSeconds ?? 600)
                    : min(3600, 60 * (2 ** max(0, $entry['attempts'] - 1)));

                IndexNowNotification::query()->whereKey($entry['id'])->update([
                    'status' => $willRetry ? 'pending' : 'failed',
                    'available_at' => $willRetry ? now()->addSeconds($delay) : null,
                    'claimed_at' => null,
                    'last_response_code' => $result->statusCode,
                    'last_error' => $result->errorCode ?: 'submission_failed',
                    'updated_at' => now(),
                ]);
            }

            Log::warning('IndexNow URL notification batch was not accepted.', [
                'url_count' => count($ids),
                'http_status' => $result->statusCode,
                'error_code' => $result->errorCode ?: 'connection_error',
                'retryable' => $result->retryable,
            ]);
        }

        $this->refreshBulkRuns($runIds);

        return count($claimed);
    }

    public function retryFailed(?int $bulkRunId = null): int
    {
        $query = IndexNowNotification::query()->where('status', 'failed');

        if ($bulkRunId !== null) {
            $query->where('bulk_run_id', $bulkRunId);
        }

        return $query->update([
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => now(),
            'claimed_at' => null,
            'last_error' => null,
            'updated_at' => now(),
        ]);
    }

    private function scheduledPostStillPublic(IndexNowNotification $notification): bool
    {
        $post = Post::query()->find($notification->subject_id);

        if (! $post || ! $post->published_at || $post->status !== 'published' || $post->published_at->isFuture()) {
            return false;
        }

        $path = parse_url($notification->url, PHP_URL_PATH) ?: '/';

        if (str_starts_with($path, '/blog/')) {
            try {
                return app(SitemapUrlDiscovery::class)->publicRouteUrl('seo.public.blog.show', ['slug' => $post->slug])
                    === $notification->url;
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    /** @param array<int, int> $runIds */
    private function refreshBulkRuns(array $runIds): void
    {
        foreach ($runIds as $runId) {
            $statuses = IndexNowNotification::query()
                ->where('bulk_run_id', $runId)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status');

            $hasPending = (int) ($statuses['pending'] ?? 0) + (int) ($statuses['processing'] ?? 0) > 0;
            $hasFailed = (int) ($statuses['failed'] ?? 0) > 0;
            $status = $hasPending ? 'pending' : ($hasFailed ? 'failed' : 'complete');

            IndexNowBulkRun::query()->whereKey($runId)->update([
                'status' => $status,
                'completed_at' => $status === 'pending' ? null : now(),
                'updated_at' => now(),
            ]);
        }
    }
}

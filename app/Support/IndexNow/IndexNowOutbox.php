<?php

namespace App\Support\IndexNow;

use App\Models\IndexNowNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class IndexNowOutbox
{
    public function enqueue(
        string $url,
        string $source,
        string $kind = 'change',
        ?Carbon $availableAt = null,
        ?int $bulkRunId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
    ): ?IndexNowNotification {
        $service = app(IndexNowService::class);

        if (! $service->canSubmit()) {
            return null;
        }

        $canonicalUrl = $service->canonicalizeUrl($url);
        $now = now();
        $urlHash = hash('sha256', $canonicalUrl);
        $bucket = $availableAt && $availableAt->gt($now)
            ? 'scheduled-'.$availableAt->format('YmdHi')
            : 'now-'.$now->format('YmdHi');

        return DB::transaction(function () use (
            $canonicalUrl,
            $source,
            $kind,
            $availableAt,
            $bulkRunId,
            $subjectType,
            $subjectId,
            $now,
            $urlHash,
            $bucket,
        ): IndexNowNotification {
            $notification = IndexNowNotification::query()
                ->where('url_hash', $urlHash)
                ->where('dedupe_bucket', $bucket)
                ->lockForUpdate()
                ->first();

            if ($notification) {
                $notification->fill([
                    'url' => $canonicalUrl,
                    'kind' => $kind,
                    'source' => $source,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'bulk_run_id' => $bulkRunId ?? $notification->bulk_run_id,
                    'status' => 'pending',
                    'attempts' => 0,
                    'available_at' => $availableAt ?? $now,
                    'claimed_at' => null,
                    'last_response_code' => null,
                    'last_error' => null,
                ])->save();

                return $notification;
            }

            try {
                return IndexNowNotification::query()->create([
                    'url' => $canonicalUrl,
                    'url_hash' => $urlHash,
                    'dedupe_bucket' => $bucket,
                    'kind' => $kind,
                    'source' => $source,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'bulk_run_id' => $bulkRunId,
                    'status' => 'pending',
                    'attempts' => 0,
                    'available_at' => $availableAt ?? $now,
                ]);
            } catch (QueryException $exception) {
                // A concurrent request may have inserted this same URL in the
                // current minute. Reuse that durable outbox entry.
                $notification = IndexNowNotification::query()
                    ->where('url_hash', $urlHash)
                    ->where('dedupe_bucket', $bucket)
                    ->first();

                if (! $notification) {
                    throw $exception;
                }

                $notification->fill([
                    'kind' => $kind,
                    'source' => $source,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'bulk_run_id' => $bulkRunId ?? $notification->bulk_run_id,
                    'status' => 'pending',
                    'attempts' => 0,
                    'available_at' => $availableAt ?? $now,
                    'claimed_at' => null,
                    'last_response_code' => null,
                    'last_error' => null,
                ])->save();

                return $notification;
            }
        });
    }

    public function cancelScheduledPost(int $postId): void
    {
        IndexNowNotification::query()
            ->where('subject_type', 'scheduled-post')
            ->where('subject_id', $postId)
            ->where('status', 'pending')
            ->update([
                'status' => 'cancelled',
                'updated_at' => now(),
            ]);
    }
}

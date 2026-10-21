<?php

namespace App\Support\IndexNow;

final class IndexNowSubmissionResult
{
    public function __construct(
        public readonly bool $accepted,
        public readonly ?int $statusCode = null,
        public readonly bool $retryable = false,
        public readonly string $errorCode = '',
        public readonly ?int $retryAfterSeconds = null,
    ) {}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndexNowNotification extends Model
{
    protected $table = 'indexnow_notifications';

    protected $fillable = [
        'url',
        'url_hash',
        'dedupe_bucket',
        'kind',
        'source',
        'subject_type',
        'subject_id',
        'bulk_run_id',
        'status',
        'attempts',
        'available_at',
        'claimed_at',
        'last_response_code',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'available_at' => 'datetime',
            'claimed_at' => 'datetime',
            'attempts' => 'integer',
            'last_response_code' => 'integer',
        ];
    }
}

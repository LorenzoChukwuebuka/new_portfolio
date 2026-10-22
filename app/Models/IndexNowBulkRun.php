<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndexNowBulkRun extends Model
{
    protected $table = 'indexnow_bulk_runs';

    protected $fillable = [
        'name',
        'status',
        'total_urls',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_urls' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(IndexNowNotification::class, 'bulk_run_id');
    }
}

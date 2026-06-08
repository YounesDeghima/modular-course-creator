<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiJobSnapshot extends Model
{
    protected $guarded = []; // Quickest way to allow mass assignment

    protected $casts = [
        'image_urls'    => 'array',
        'results'       => 'array',
        'md_created_at' => 'datetime',
    ];

    // Your job also calls a custom helper method called addResult() on line 133/149
    public function addResult(string $model, string $status, ?string $json, ?string $error, int $duration): void
    {
        $currentResults = $this->results ?? [];
        // index is 1-based so callers can use it in URLs (result/1, result/2, …)
        $index = count($currentResults) + 1;

        $currentResults[] = [
            'index'            => $index,
            'model'            => $model,
            'status'           => $status,
            'result_json'      => $json,   // key matches what snapshotResult() expects
            'error'            => $error,
            'duration_seconds' => $duration,
            'timestamp'        => now()->toIso8601String(),
        ];

        $this->update(['results' => $currentResults]);
    }
}

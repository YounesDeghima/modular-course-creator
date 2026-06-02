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
        $currentResults[] = [
            'model' => $model,
            'status' => $status,
            'json' => $json,
            'error' => $error,
            'duration_seconds' => $duration,
            'timestamp' => now()->toIso8601String()
        ];

        $this->update(['results' => $currentResults]);
    }
}

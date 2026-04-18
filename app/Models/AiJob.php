<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiJob extends Model
{
    protected $table = 'ai_jobs';

    protected $fillable = [
        'status',           // queued | processing | done | failed | saved | cancelled
        'pdf_path',
        'original_filename',
        'file_size',
        'year',
        'branch',
        'result_json',
        'error_message',
        'logs',
        'attempt',
        'max_attempts',
        'priority',
        'started_by',
        'started_by_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'note',
    ];

    protected $casts = [
        'logs'        => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    // ── Log helper ────────────────────────────────────────────────────────────
    public function log(string $message, string $level = 'info'): void
    {
        $entries   = $this->logs ?? [];
        $entries[] = [
            'ts'      => now()->format('H:i:s'),
            'level'   => $level,
            'message' => $message,
        ];
        $this->update(['logs' => $entries]);
    }

    // ── Computed helpers ──────────────────────────────────────────────────────
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->attempt < $this->max_attempts;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, ['queued', 'failed']);
    }

    public function fileSizeHuman(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes < 1024)        return $bytes . ' B';
        if ($bytes < 1048576)     return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function progressPercent(): int
    {
        return match ($this->status) {
            'queued'     => 5,
            'processing' => $this->progressFromLogs(),
            'done'       => 100,
            'saved'      => 100,
            'failed'     => 100,
            'cancelled'  => 0,
            default      => 0,
        };
    }

    private function progressFromLogs(): int
    {
        $logs = $this->logs ?? [];
        foreach (array_reverse($logs) as $entry) {
            if (str_contains($entry['message'], 'STEP 2')) return 60;
            if (str_contains($entry['message'], 'STEP 1')) return 30;
        }
        return 10;
    }
}

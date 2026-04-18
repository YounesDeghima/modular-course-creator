<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiJob extends Model
{
    protected $table = 'ai_jobs';

    protected $fillable = [
        'status',
        'pdf_path',
        'year',
        'branch',
        'result_json',
        'error_message',
        'logs',
    ];

    protected $casts = [
        'logs' => 'array',
    ];

    /**
     * Append a log entry and immediately persist it.
     * Level: info | ok | warn | error
     */
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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiJob extends Model
{
    use HasFactory;

    protected $table = 'ai_jobs';

    protected $fillable = [
        'status',        // queued | processing | done | failed | saved
        'pdf_path',      // storage/app/ai_uploads/...
        'year',          // 1 | 2 | 3
        'branch',        // mi | st | none
        'result_json',   // the full JSON string returned by Ollama
        'error_message', // set when status = failed
    ];
}

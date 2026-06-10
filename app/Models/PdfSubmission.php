<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PdfSubmission extends Model
{
    protected $fillable = [
        'teacher_id',
        'original_name',
        'filename',
        'note',
        'status',
        'admin_note',
    ];

    public function teacher()
    {
        return $this->belongsTo(user::class, 'teacher_id');
    }

    public function isPending(): bool  { return $this->status === 'pending'; }
    public function isAccepted(): bool { return $this->status === 'accepted'; }
    public function isDeclined(): bool { return $this->status === 'declined'; }

    public function storagePath(): string
    {
        return storage_path('app/pdfs/' . $this->filename);
    }
}

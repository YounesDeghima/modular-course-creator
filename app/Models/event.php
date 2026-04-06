<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class event extends Model
{
    protected $fillable = [
        'title','description','start_date','end_date','type','visibility','user_id'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function user() { return $this->belongsTo(User::class); }
}

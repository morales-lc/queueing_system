<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'service_type', // cashier | registrar
        'priority',     // pwd_senior_pregnant | student | parent
        'status',       // pending | serving | done | on_hold
        'counter_id',
        'hold_count',
        'called_times',
    ];

    protected $casts = [
        'counter_id' => 'integer',
        'hold_count' => 'integer',
        'called_times' => 'integer',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }
}

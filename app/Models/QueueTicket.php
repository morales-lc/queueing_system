<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueueTicket extends Model
{

    protected $fillable = [
        'code',
        'service_type', // cashier | registrar
        'program',
        'priority',     // pwd_senior_pregnant | student | parent
        'status',       // pending | serving | done | on_hold
        'counter_id',
        'designated_counter_id',
        'hold_count',
        'called_times',
    ];

    protected $casts = [
        'counter_id' => 'integer',
        'designated_counter_id' => 'integer',
        'hold_count' => 'integer',
        'called_times' => 'integer',
    ];

    public function counter()
    {
        return $this->belongsTo(Counter::class);
    }

    public function designatedCounter()
    {
        return $this->belongsTo(Counter::class, 'designated_counter_id');
    }
}

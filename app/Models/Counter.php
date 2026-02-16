<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Counter extends Model
{

    protected $fillable = [
        'name',
        'type', // cashier | registrar
        'claimed', // bool if in-use
    ];

    protected $casts = [
        'claimed' => 'boolean',
    ];

    public function tickets()
    {
        return $this->hasMany(QueueTicket::class);
    }
}

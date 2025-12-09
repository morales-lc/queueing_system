<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorMedia extends Model
{
    use HasFactory;

    protected $table = 'monitor_media';

    protected $fillable = [
        'filename',
        'original_filename',
        'type',
        'path',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

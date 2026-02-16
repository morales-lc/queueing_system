<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonitorSetting extends Model
{
    protected $table = 'monitor_settings';

    protected $fillable = [
        'marquee_text',
    ];
}

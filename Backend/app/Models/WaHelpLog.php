<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaHelpLog extends Model
{
    protected $fillable = [
        'phone',
        'last_help_at',
    ];

    protected $casts = [
        'last_help_at' => 'datetime',
    ];
}

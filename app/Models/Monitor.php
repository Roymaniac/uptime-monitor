<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    protected $fillable = [
        'url',
        'check_interval',
        'threshold',
        'status',
        'consecutive_failures',
        'last_checked_at',
    ];

    protected $casts = [
        'check_interval' => 'integer',
        'threshold' => 'integer',
        'consecutive_failures' => 'integer',
        'last_checked_at' => 'datetime',
    ];

    public function checks(): HasMany
    {
        return $this->hasMany(MonitorCheck::class);
    }
}

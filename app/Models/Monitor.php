<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Monitor extends Model
{
    use HasFactory;

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

    /**
     * Calculate uptime percentage from the complete check history.
     *
     */
    public function getUptimePercentageAttribute(): ?float
    {
        $total = $this->checks()->count();

        if ($total === 0) {
            return null;
        }

        $up = $this->checks()->where('is_up', true)->count();

        return round(($up / $total) * 100, 2);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    public function isDown(): bool
    {
        return $this->status === 'down';
    }
}

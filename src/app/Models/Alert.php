<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    public const STATUS_PENDING = 'Pending';

    public const STATUS_ACKNOWLEDGED = 'Acknowledged';

    public const STATUS_RESOLVED = 'Resolved';

    protected $fillable = [
        'camera_ip',
        'label',
        'maxT',
        'box_id',
        'triggered_at',
        'status',
        'site_id',   // ← new FK
        'acknowledged_by',
        'acknowledged_at',
        'resolved_by',        // ← new
        'resolved_at',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /* ----------------- Relationships ----------------- */
    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function acknowledgedByUser()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /* ----------------- Scopes ----------------- */
    /** Limit results to what a user can see (admin = all, engineer = assigned sites) */
    public function scopeVisibleTo(Builder $q, User $user): Builder
    {
        return $user->role === 'admin'
            ? $q
            : $q->whereIn('site_id', $user->sites()->pluck('sites.id'));
    }

    /* ----------------- Accessors ----------------- */
    /** Tailwind bg class for row */
    public function getRowBgClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-red-100',
            self::STATUS_ACKNOWLEDGED => 'bg-orange-100',
            self::STATUS_RESOLVED => 'bg-green-100',
            default => '',
        };
    }
}

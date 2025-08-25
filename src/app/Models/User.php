<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    /** Mass‑assignable fields */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    /** Hidden from array / JSON */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /** Casting for date/time */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Sites to which this user (engineer) is assigned.
     */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class);
    }

    /**
     * All Expo push tokens this user has registered.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Quick check for admin users.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * (Optional) a scope you could extend to restrict
     * queries based on the user’s role/sites.
     */
    public function scopeCanSeeSites($query)
    {
        // e.g. if engineer: $query->whereHas('sites', ...);
        return $query;
    }
}

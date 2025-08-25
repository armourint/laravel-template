<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Site extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'latitude',
        'longitude',
    ];

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    /**
     * Engineers assigned to this site.
     */
    public function engineers(): BelongsToMany
    {
        return $this->belongsToMany(User::class); // pivot: site_user
    }

    /**
     * Limit sites to what a given user may see.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        return $user->role === 'admin'
            ? $query
            : $query->whereHas('engineers', fn ($q) => $q->whereKey($user->id));
    }
}

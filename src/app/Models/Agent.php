<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Agent extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'name', 'host_label', 'hostname', 'version',
        'token_hash', 'watcher_count', 'last_seen_at', 'last_payload', 'notes',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'last_payload' => 'array',
    ];

    public function cameras()
    {
        return $this->hasMany(Camera::class, 'assigned_agent_uuid', 'uuid');
    }

    public function rotateToken(): string
    {
        $plain = Str::random(64);
        $this->token_hash = Hash::make($plain);
        $this->save();

        return $plain;
    }

    public function tokenMatches(string $plain): bool
    {
        return Hash::check($plain, $this->token_hash);
    }

    public static function byUuid(string $uuid): self
    {
        return static::where('uuid', $uuid)->firstOrFail();
    }
}

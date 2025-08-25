<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Camera extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'ip', 'api_key', 'enabled', 'assigned_agent_uuid', 'status', 'notes',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'api_key' => 'encrypted',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class, 'assigned_agent_uuid', 'uuid');
    }
}

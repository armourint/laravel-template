<?php

// app/Models/DeviceToken.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $fillable = [
        'user_id',
        'expo_token',
        'platform',
    ];

    /**
     * The owning user.
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}

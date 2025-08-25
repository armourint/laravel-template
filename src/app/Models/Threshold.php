<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Threshold extends Model
{
    protected $fillable = ['roi', 'max_temp', 'description'];
}

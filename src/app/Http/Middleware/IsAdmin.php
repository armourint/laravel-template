<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class IsAdmin
{
    public function handle($request, Closure $next)
    {
        // Only allow users with the "admin" role
        if (Auth::check() && Auth::user()->role === 'admin') {
            return $next($request);
        }

        // Otherwise, you can redirect or throw 403
        abort(403, 'Unauthorized');
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckLevel
{
    public function handle(Request $request, Closure $next, ...$levels)
    {
        if (!auth()->check() || !in_array(auth()->user()->level, $levels)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}
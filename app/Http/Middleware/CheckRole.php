<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    // Dùng: ->middleware('role:chu_tro') hoặc 'role:khach'
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        if (!$request->user() || !in_array($request->user()->vai_tro, $roles, true)) {
            abort(403);
        }
        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnsureUserIsAdmin
{
    public function handle($request, \Closure $next)
    {
        if (! $request->user()) {
            return response()->json(['message' => 'Forbidden (no user)'], 403);
        }

        $role = strtolower((string) ($request->user()->user_type ?? 'user'));
        if ($role !== 'admin') {
            return response()->json(['message' => "Forbidden (role={$role})"], 403);
        }

        return $next($request);
    }
}

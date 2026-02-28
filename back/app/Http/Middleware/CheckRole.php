<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user || !$user->roles()->whereIn('name', $roles)->exists()) {
            abort(403, 'Unauthorized.');
        }

        return $next($request);
    }

}

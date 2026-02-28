<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;

class OptionalAuth
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        if($token){
            Auth::guard('sanctum')->setUser(
                Auth::guard('sanctum')->user()
            );
        }
        return $next($request);
    }
}

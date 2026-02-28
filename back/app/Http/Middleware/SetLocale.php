<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->header('lang');
        if ($locale && in_array($locale, ['en', 'ar']))  App::setLocale($locale);
        return $next($request);
    }
}

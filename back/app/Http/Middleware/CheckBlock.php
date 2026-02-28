<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;

class CheckBlock
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $email = $request->input('email');
        $number = $request->input('number');

        if ($user && $user->is_blocked) {
            return response()->json([
                'status' => false,
                'message' => 'Your account is blocked.'
            ], 403);
        }

        if ($email) {
            $user = User::where('email', $email)->first();
            if ($user && $user->is_blocked) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is blocked.'
                ], 403);
            }
        }

        if ($number) {
            $user = User::where('number', $number)->first();
            if ($user && $user->is_blocked) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your account is blocked.'
                ], 403);
            }
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            // Also try getting from 'api_token' input or header if bearer not present
            $token = $request->input('api_token') ?? $request->header('api_token');
        }

        if (!$token) {
            return response()->json(['message' => 'Unauthorized: No token provided'], 401);
        }

        $user = \App\Models\User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized: Invalid token'], 401);
        }

        // Authenticate the user for this request
        \Illuminate\Support\Facades\Auth::login($user);

        return $next($request);
    }
}

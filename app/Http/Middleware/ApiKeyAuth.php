<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class ApiKeyAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Read from header "X-API-Key"
        $apiKey = $request->header('X-API-Key');

        if (! $apiKey) {
            return response()->json([
                'success' => false,
                'error'   => 'API key is missing. Please log in again.',
                'should_refresh_token' => true,
            ], 200);
        }

        $user = User::where('api_key', $apiKey)->first();

        if (! $user) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid API key. Please log in again.',
                'should_refresh_token' => true,
            ], 200);
        }

        // Set the authenticated user for this request
        auth()->setUser($user);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Updates the user's last_seen_at timestamp every 5 minutes.
 * Uses the cache to avoid a database write on every request.
 */
class LastActivityMiddleware
{
    private const UPDATE_INTERVAL = 300; // 5 minutes in seconds

    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $cacheKey = 'user:last_active:'.auth()->id();

            if (! Cache::has($cacheKey)) {
                auth()->user()->update(['last_seen_at' => now()]);
                Cache::put($cacheKey, true, self::UPDATE_INTERVAL);
            }
        }

        return $next($request);
    }
}

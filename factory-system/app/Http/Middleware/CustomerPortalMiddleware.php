<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts customer-role users to the /portal route prefix only.
 * Any attempt to access admin or staff routes is redirected to the portal home.
 */
class CustomerPortalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasRole('customer')) {
            if (! $request->routeIs('portal.*', 'logout')) {
                return redirect()->route('portal.dashboard')
                    ->with('warning', __('auth.portal_only'));
            }
        }

        return $next($request);
    }
}

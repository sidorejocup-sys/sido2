<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LoginRateLimitMiddleware
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected RateLimiter $limiter)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $key = 'login_attempts:' . $request->ip();
        $maxAttempts = 5;
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts, $decayMinutes)) {
            return response()->json([
                'message' => 'Too many login attempts. Please try again in ' . $this->limiter->availableIn($key) . ' seconds.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}

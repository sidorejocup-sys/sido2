<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ApiRateLimitMiddleware
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
        $key = 'api_requests:' . $request->ip();
        $maxRequests = 60;
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxRequests, $decayMinutes)) {
            return response()->json([
                'message' => 'API rate limit exceeded. Maximum 60 requests per minute.',
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Constants\StatusCode;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate extends Middleware
{

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param null    $guards
     */
    public function handle($request, Closure $next, ...$guards): mixed
    {
        if (array_any($guards, fn ($guard) => !Auth::guard($guard)->check())) {
            return ApiResponse::basic(
                'Unauthorized or session expired',
                StatusCode::UNAUTHORIZED
            );
        }

        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): null
    {
        return null;
    }

}

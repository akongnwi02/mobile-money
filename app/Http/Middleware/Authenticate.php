<?php

namespace App\Http\Middleware;

use App\Exceptions\ForbiddenException;
use App\Exceptions\UnAuthorizationException;
use App\Services\Constants\ErrorCodesConstants;
use Closure;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     * @throws ForbiddenException
     */
    public function handle($request, Closure $next)
    {
        if ($request->header('x-api-key') != config('app.api_key')) {
    
            if (config('app.partner_restriction')) {
    
                throw new ForbiddenException(ErrorCodesConstants::INVALID_API_KEY, 'The api key has not been provided or is invalid');
            }
            
        }
        return $next($request);
    }
}

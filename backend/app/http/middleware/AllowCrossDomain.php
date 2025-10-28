<?php

namespace app\http\middleware;

use Closure;

/**
 * 跨域中间件
 */
class AllowCrossDomain
{
    public function handle($request, Closure $next)
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        if ($request->method() == 'OPTIONS') {
            return response('', 200);
        }
        
        return $next($request);
    }
}

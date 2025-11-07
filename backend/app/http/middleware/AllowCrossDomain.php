<?php

namespace app\http\middleware;

use Closure;
use think\Response;

/**
 * 跨域中间件
 */
class AllowCrossDomain
{
    public function handle($request, Closure $next)
    {
        // 获取请求来源
        $origin = $request->header('origin', '*');
        
        // 设置跨域响应头
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400'); // 预检请求缓存时间
        
        // 处理 OPTIONS 预检请求
        if ($request->method(true) == 'OPTIONS') {
            return response('', 200);
        }
        
        // 继续处理请求
        $response = $next($request);
        
        // 如果响应是 Response 对象，添加跨域头
        if ($response instanceof Response) {
            $response->header([
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Credentials' => 'true',
            ]);
        }
        
        return $response;
    }
}

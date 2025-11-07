<?php
// +----------------------------------------------------------------------
// | API 应用入口文件
// +----------------------------------------------------------------------

namespace think;

require __DIR__ . '/../vendor/autoload.php';

// 设置 CORS 跨域头（在应用启动前设置）
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Max-Age: 86400');

// 处理 OPTIONS 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 创建应用实例
$app = new App();

// 执行HTTP应用并响应
$http = $app->http;

// 强制设置为 API 应用
$http->name('api');

// 运行应用
$response = $http->run();

$response->send();

$http->end($response);

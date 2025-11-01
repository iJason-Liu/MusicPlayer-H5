<?php
// +----------------------------------------------------------------------
// | API 应用入口文件
// +----------------------------------------------------------------------

namespace think;

require __DIR__ . '/../vendor/autoload.php';

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

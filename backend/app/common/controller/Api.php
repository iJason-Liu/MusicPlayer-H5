<?php

namespace app\common\controller;

use think\App;
use think\exception\HttpResponseException;
use think\Response;

/**
 * API基础控制器
 */
class Api
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 无需登录的方法
     * @var array
     */
    protected $noNeedLogin = [];

    /**
     * 无需鉴权的方法
     * @var array
     */
    protected $noNeedRight = [];

    /**
     * 构造方法
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        // 跨域处理
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

        if ($this->request->isOptions()) {
            exit();
        }

        // 检查登录
        $this->checkAuth();
    }

    /**
     * 检查认证
     */
    protected function checkAuth()
    {
        $action = $this->request->action();

        // 检查是否需要登录
        if (!in_array('*', $this->noNeedLogin) && !in_array($action, $this->noNeedLogin)) {
            $token = $this->request->header('Authorization', '');
            
            if (empty($token)) {
                $this->error('请先登录', [], 401);
            }

            // 验证 token
            // 这里简化处理，实际应该验证 token 的有效性
            // 可以使用 JWT 或 Redis 存储
        }
    }

    /**
     * 成功返回
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    protected function success($msg = 'success', $data = [], $code = 1)
    {
        $this->result($msg, $data, $code);
    }

    /**
     * 失败返回
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    protected function error($msg = 'error', $data = [], $code = 0)
    {
        $this->result($msg, $data, $code);
    }

    /**
     * 返回封装后的API数据到客户端
     * @param string $msg
     * @param array $data
     * @param int $code
     */
    protected function result($msg, $data = [], $code = 0)
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];

        $response = Response::create($result, 'json')->code(200);
        throw new HttpResponseException($response);
    }

    /**
     * 获取当前登录用户ID
     * @return int
     */
    protected function getUserId()
    {
        $token = $this->request->header('Authorization', '');
        
        if (empty($token)) {
            return 0;
        }

        // 这里简化处理，实际应该从 token 解析用户ID
        // 可以使用 JWT 或从 Redis 中获取
        // 示例：
        // $userId = cache('token:' . $token);
        // return $userId ?: 0;

        return 1; // 临时返回固定用户ID
    }

    /**
     * 获取当前登录用户信息
     * @return array
     */
    protected function getUser()
    {
        $userId = $this->getUserId();
        
        if (!$userId) {
            return [];
        }

        $user = \think\facade\Db::name('user')->where('id', $userId)->find();
        
        return $user ?: [];
    }
}

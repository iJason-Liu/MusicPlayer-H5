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

            // 验证 token 并获取用户ID
            $userId = \app\common\service\TokenService::getUserIdByToken($token);
            
            if (!$userId) {
                $this->error('登录已过期，请重新登录', [], 401);
            }
            
            // 将用户ID存储到请求中，供后续使用
            $this->request->userId = $userId;
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
        // 优先从请求中获取（已在 checkAuth 中验证）
        if (isset($this->request->userId)) {
            return $this->request->userId;
        }
        
        // 从 token 获取
        $token = $this->request->header('Authorization', '');
        
        if (empty($token)) {
            return 0;
        }

        $userId = \app\common\service\TokenService::getUserIdByToken($token);
        
        return $userId ?: 0;
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

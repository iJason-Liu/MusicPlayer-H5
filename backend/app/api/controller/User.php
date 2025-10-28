<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;

/**
 * 用户API接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['*'];
    
    /**
     * 登录
     */
    public function login()
    {
        $username = $this->request->param('username', '');
        $password = $this->request->param('password', '');
        
        if (empty($username) || empty($password)) {
            return json(['code' => 0, 'msg' => '用户名或密码不能为空']);
        }
        
        // 查询用户
        $user = Db::name('user')->where('username', $username)->find();
        
        if (!$user) {
            return json(['code' => 0, 'msg' => '用户不存在']);
        }
        
        // 验证密码
        if (!password_verify($password, $user['password'])) {
            return json(['code' => 0, 'msg' => '密码错误']);
        }
        
        if ($user['status'] != 1) {
            return json(['code' => 0, 'msg' => '账号已被禁用']);
        }
        
        // 生成 token
        $token = $this->generateToken($user['id']);
        
        // 更新登录时间
        Db::name('user')->where('id', $user['id'])->update([
            'update_time' => time()
        ]);
        
        $data = [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'nickname' => $user['nickname'] ?: $user['username'],
                'avatar' => $user['avatar'] ?: ''
            ]
        ];
        
        return json(['code' => 1, 'msg' => '登录成功', 'data' => $data]);
    }
    
    /**
     * 获取用户信息
     */
    public function info()
    {
        $userId = $this->getUserId();
        
        $user = Db::name('user')->where('id', $userId)->find();
        
        if (!$user) {
            return json(['code' => 0, 'msg' => '用户不存在']);
        }
        
        // 统计数据
        $stats = [
            'play_count' => Db::name('play_history')->where('user_id', $userId)->count(),
            'favorite_count' => Db::name('favorite')->where('user_id', $userId)->count(),
            'playlist_count' => Db::name('playlist')->where('user_id', $userId)->count(),
        ];
        
        $data = [
            'id' => $user['id'],
            'username' => $user['username'],
            'nickname' => $user['nickname'] ?: $user['username'],
            'avatar' => $user['avatar'] ?: '',
            'stats' => $stats
        ];
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $data]);
    }
    
    /**
     * 统计信息
     */
    public function statistics()
    {
        $userId = $this->getUserId();
        
        // 音乐总数
        $totalMusic = Db::name('music')->where('status', 1)->count();
        
        // 总时长（秒）
        $totalDuration = Db::name('music')->where('status', 1)->sum('duration');
        
        // 播放历史数量
        $playCount = Db::name('play_history')->where('user_id', $userId)->count();
        
        // 收藏数量
        $favoriteCount = Db::name('favorite')->where('user_id', $userId)->count();
        
        // 播放列表数量
        $playlistCount = Db::name('playlist')->where('user_id', $userId)->count();
        
        // 总播放时长（秒）
        $totalPlayDuration = Db::name('play_history')->where('user_id', $userId)->sum('duration');
        
        $data = [
            'total_music' => $totalMusic,
            'total_duration' => $totalDuration,
            'play_count' => $playCount,
            'favorite_count' => $favoriteCount,
            'playlist_count' => $playlistCount,
            'total_play_duration' => $totalPlayDuration
        ];
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $data]);
    }
    
    /**
     * 更新用户信息
     */
    public function updateProfile()
    {
        $userId = $this->getUserId();
        $nickname = $this->request->param('nickname', '');
        $avatar = $this->request->param('avatar', '');
        
        $data = ['update_time' => time()];
        if (!empty($nickname)) $data['nickname'] = $nickname;
        if (!empty($avatar)) $data['avatar'] = $avatar;
        
        Db::name('user')->where('id', $userId)->update($data);
        
        return json(['code' => 1, 'msg' => '更新成功']);
    }
    
    /**
     * 修改密码
     */
    public function changePassword()
    {
        $userId = $this->getUserId();
        $oldPassword = $this->request->param('old_password', '');
        $newPassword = $this->request->param('new_password', '');
        
        if (empty($oldPassword) || empty($newPassword)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 查询用户
        $user = Db::name('user')->where('id', $userId)->find();
        
        // 验证旧密码
        if (!password_verify($oldPassword, $user['password'])) {
            return json(['code' => 0, 'msg' => '原密码错误']);
        }
        
        // 更新密码
        Db::name('user')->where('id', $userId)->update([
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'update_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '密码修改成功']);
    }
    
    /**
     * 退出登录
     */
    public function logout()
    {
        // 实际应该清除 token
        return json(['code' => 1, 'msg' => '退出成功']);
    }
    
    /**
     * 生成 token
     */
    private function generateToken($userId)
    {
        return md5($userId . time() . uniqid());
    }
    
    /**
     * 获取当前用户ID
     */
    private function getUserId()
    {
        // 从请求头获取 token
        $token = $this->request->header('Authorization', '');
        
        // 这里简化处理，实际应该验证 token
        // 可以使用 JWT 或存储在 Redis 中
        return 1; // 临时返回固定用户ID
    }
}

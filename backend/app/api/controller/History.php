<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;

/**
 * 播放历史API接口
 */
class History extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取播放历史
     */
    public function index()
    {
        $userId = $this->getUserId();
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        
        // 获取最近播放的音乐（去重）
        $list = Db::name('play_history')
            ->alias('h')
            ->join('music m', 'h.music_id = m.id')
            ->where('h.user_id', $userId)
            ->field('m.*, MAX(h.create_time) as last_play_time, COUNT(h.id) as play_count')
            ->group('h.music_id')
            ->order('last_play_time', 'desc')
            ->page($page, $limit)
            ->select()
            ->each(function($item) {
                $item['url'] = 'https://alist.crayon.vip/Music/' . $item['file_path'];
                return $item;
            });
        
        $total = Db::name('play_history')
            ->where('user_id', $userId)
            ->group('music_id')
            ->count();
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list, 'total' => $total]);
    }
    
    /**
     * 添加播放记录
     */
    public function add()
    {
        $userId = $this->getUserId();
        $musicId = $this->request->param('music_id');
        $duration = $this->request->param('duration', 0);
        
        if (empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查音乐是否存在
        $music = Db::name('music')->where('id', $musicId)->find();
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        // 添加播放记录
        Db::name('play_history')->insert([
            'user_id' => $userId,
            'music_id' => $musicId,
            'play_time' => time(),
            'duration' => $duration,
            'create_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '添加成功']);
    }
    
    /**
     * 清空播放历史
     */
    public function clear()
    {
        $userId = $this->getUserId();
        
        Db::name('play_history')->where('user_id', $userId)->delete();
        
        return json(['code' => 1, 'msg' => '清空成功']);
    }
    
    /**
     * 删除单条播放历史
     */
    public function delete()
    {
        $userId = $this->getUserId();
        $musicId = $this->request->param('music_id');
        
        if (empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        Db::name('play_history')
            ->where('user_id', $userId)
            ->where('music_id', $musicId)
            ->delete();
        
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 获取当前用户ID
     */
    protected function getUserId()
    {
        $token = $this->request->header('Authorization', '');
        // 简化处理，实际应该从 token 解析用户ID
        return 1;
    }
}

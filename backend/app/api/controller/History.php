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
     * 获取播放历史（分页）
     */
    public function list()
    {
        try {
            $userId = $this->getUserId();
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            
            // 获取最近播放的音乐（去重）
            $subQuery = Db::name('play_history')
                ->where('user_id', $userId)
                ->field('music_id, MAX(create_time) as last_play_time, COUNT(id) as play_count')
                ->group('music_id')
                ->buildSql();
            
            $list = Db::table($subQuery . ' h')
                ->join('music m', 'h.music_id = m.id')
                ->field('m.*, h.last_play_time, h.play_count')
                ->order('h.last_play_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            // 转换为数组并添加URL
            $list = $list->toArray();
            foreach ($list as &$item) {
                $item['url'] = 'https://alist.crayon.vip/Music/' . $item['file_path'];
            }
            
            // 统计总数（去重后的音乐数量）
            $total = Db::name('play_history')
                ->where('user_id', $userId)
                ->distinct(true)
                ->count('music_id');
            
            return json([
                'code' => 1, 
                'msg' => 'success', 
                'data' => [
                    'list' => $list,
                    'total' => $total,
                    'page' => (int)$page,
                    'limit' => (int)$limit,
                    'pages' => $total > 0 ? ceil($total / $limit) : 0
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 0,
                'msg' => '获取播放历史失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 添加播放记录
     */
    public function add()
    {
        try {
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
            
            // 添加播放记录（字段名是 play_duration）
            Db::name('play_history')->insert([
                'user_id' => $userId,
                'music_id' => $musicId,
                'play_time' => time(),
                'play_duration' => $duration,
                'create_time' => time()
            ]);
            
            return json(['code' => 1, 'msg' => '添加成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '添加失败：' . $e->getMessage()]);
        }
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
}

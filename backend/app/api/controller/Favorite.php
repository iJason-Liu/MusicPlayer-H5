<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;

/**
 * 收藏API接口
 */
class Favorite extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取收藏列表（分页）
     */
    public function list()
    {
        try {
            $userId = $this->getUserId();
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            
            $list = Db::name('favorite')
                ->alias('f')
                ->join('music m', 'f.music_id = m.id')
                ->where('f.user_id', $userId)
                ->field('m.*, f.create_time as favorite_time')
                ->order('f.create_time', 'desc')
                ->page($page, $limit)
                ->select();
            
            // 转换为数组并添加URL
            $list = $list->toArray();
            foreach ($list as &$item) {
                $item['url'] = 'https://alist.crayon.vip/Music/' . $item['file_path'];
                $item['is_favorite'] = true;
            }
            
            $total = Db::name('favorite')->where('user_id', $userId)->count();
            
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
                'msg' => '获取收藏列表失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
    }
    
    /**
     * 添加收藏
     */
    public function add()
    {
        $userId = $this->getUserId();
        $musicId = $this->request->param('music_id');
        
        if (empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查音乐是否存在
        $music = Db::name('music')->where('id', $musicId)->find();
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        // 检查是否已收藏
        $exists = Db::name('favorite')
            ->where('user_id', $userId)
            ->where('music_id', $musicId)
            ->find();
        
        if ($exists) {
            return json(['code' => 0, 'msg' => '已经收藏过了']);
        }
        
        // 添加收藏
        Db::name('favorite')->insert([
            'user_id' => $userId,
            'music_id' => $musicId,
            'create_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '收藏成功']);
    }
    
    /**
     * 取消收藏
     */
    public function remove()
    {
        $userId = $this->getUserId();
        $musicId = $this->request->param('music_id');
        
        if (empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $result = Db::name('favorite')
            ->where('user_id', $userId)
            ->where('music_id', $musicId)
            ->delete();
        
        if ($result) {
            return json(['code' => 1, 'msg' => '取消收藏成功']);
        }
        
        return json(['code' => 0, 'msg' => '取消收藏失败']);
    }
    
    /**
     * 检查是否收藏
     */
    public function check()
    {
        $userId = $this->getUserId();
        $musicId = $this->request->param('music_id');
        
        if (empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $exists = Db::name('favorite')
            ->where('user_id', $userId)
            ->where('music_id', $musicId)
            ->find();
        
        return json(['code' => 1, 'msg' => 'success', 'data' => ['is_favorite' => !empty($exists)]]);
    }
}

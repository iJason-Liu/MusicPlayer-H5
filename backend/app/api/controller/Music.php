<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;

/**
 * 音乐API接口
 */
class Music extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取音乐列表
     */
    public function index()
    {
        $page = $this->request->param('page', 1);
        $limit = $this->request->param('limit', 20);
        $keyword = $this->request->param('keyword', '');
        
        $where = [['status', '=', 1]];
        
        if (!empty($keyword)) {
            $where[] = ['name|artist|album', 'like', '%' . $keyword . '%'];
        }
        
        $list = Db::name('music')
            ->where($where)
            ->page($page, $limit)
            ->order('id', 'desc')
            ->select()
            ->each(function($item) {
                $item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
                return $item;
            });
        
        $total = Db::name('music')->where($where)->count();
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list, 'total' => $total]);
    }
    
    /**
     * 搜索音乐
     */
    public function search()
    {
        $keyword = $this->request->param('keyword', '');
        
        if (empty($keyword)) {
            return json(['code' => 0, 'msg' => '请输入搜索关键词']);
        }
        
        $list = Db::name('music')
            ->where('status', 1)
            ->where('name|artist|album', 'like', '%' . $keyword . '%')
            ->limit(50)
            ->select()
            ->each(function($item) {
                $item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
                return $item;
            });
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 获取音乐详情
     */
    public function detail()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $music = Db::name('music')->where('id', $id)->find();
        
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        $music['url'] = request()->domain() . '/wwwroot/alist/music/' . $music['file_path'];
        
        // 检查是否收藏
        $userId = $this->getUserId();
        if ($userId) {
            $favorite = Db::name('favorite')
                ->where('user_id', $userId)
                ->where('music_id', $id)
                ->find();
            $music['is_favorite'] = !empty($favorite);
        } else {
            $music['is_favorite'] = false;
        }
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $music]);
    }
    
    /**
     * 获取推荐音乐
     */
    public function recommend()
    {
        $limit = $this->request->param('limit', 10);
        
        $list = Db::name('music')
            ->where('status', 1)
            ->orderRaw('RAND()')
            ->limit($limit)
            ->select()
            ->each(function($item) {
                $item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
                return $item;
            });
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 获取热门音乐（根据播放次数）
     */
    public function hot()
    {
        $limit = $this->request->param('limit', 20);
        
        $list = Db::name('music')
            ->alias('m')
            ->leftJoin('play_history h', 'm.id = h.music_id')
            ->where('m.status', 1)
            ->field('m.*, COUNT(h.id) as play_count')
            ->group('m.id')
            ->order('play_count', 'desc')
            ->limit($limit)
            ->select()
            ->each(function($item) {
                $item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
                return $item;
            });
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 获取当前用户ID
     */
    private function getUserId()
    {
        $token = $this->request->header('Authorization', '');
        if (empty($token)) {
            return null;
        }
        // 简化处理，实际应该从 token 解析用户ID
        return 1;
    }

    /**
     * 根据歌曲名获取封面、歌词、专辑等信息
     */
    private function getMusicInfo($keyword)
    {
        try {
            // 网易云第三方 API
            $api = "https://api.vvhan.com/api/wyy?type=song&msg=" . urlencode($keyword);
            $res = Http::timeout(5)->get($api)->json();
            
            if (!$res || empty($res['info'])) {
                return [];
            }

            $info = $res['info'];
            return [
                'title' => $info['title'] ?? '',
                'artist' => $info['author'] ?? '',
                'album' => $info['album'] ?? '',
                'cover' => $info['picurl'] ?? '',
                'lyric' => $info['lrc'] ?? '',
                'duration' => $info['time'] ?? 0
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}

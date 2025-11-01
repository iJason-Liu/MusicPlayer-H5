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
     * 获取音乐文件 URL
     * 音乐文件在独立域名：https://alist.crayon.vip/Music/
     */
    private function getMusicUrl($filePath)
    {
        return 'https://alist.crayon.vip/Music/' . $filePath;
    }
    
    /**
     * 获取音乐列表
     */
    public function list()
    {
        try {
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
                ->select();
            
            // 转换为数组并添加URL
            $list = $list->toArray();
            foreach ($list as &$item) {
                $item['url'] = $this->getMusicUrl($item['file_path']);
            }
            
            $total = Db::name('music')->where($where)->count();
            
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
                'msg' => '获取音乐列表失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
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
                $item['url'] = $this->getMusicUrl($item['file_path']);
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
        
        $music['url'] = $this->getMusicUrl($music['file_path']);
        
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
                $item['url'] = $this->getMusicUrl($item['file_path']);
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
                $item['url'] = $this->getMusicUrl($item['file_path']);
                return $item;
            });
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 获取当前用户ID
     */
    protected function getUserId()
    {
        $token = $this->request->header('Authorization', '');
        if (empty($token)) {
            return null;
        }
        // 简化处理，实际应该从 token 解析用户ID
        return 1;
    }
    
    /**
     * 上传音乐
     */
    public function upload()
    {
        $file = $this->request->file('file');
        $name = $this->request->param('name', '');
        $artist = $this->request->param('artist', '');
        $album = $this->request->param('album', '');
        
        if (!$file) {
            return json(['code' => 0, 'msg' => '请选择文件']);
        }
        
        // 验证文件类型
        $ext = strtolower($file->extension());
        $allowExt = ['mp3', 'flac', 'wav', 'ogg', 'm4a'];
        
        if (!in_array($ext, $allowExt)) {
            return json(['code' => 0, 'msg' => '不支持的文件格式']);
        }
        
        // 保存文件
        $savePath = 'music/' . date('Ym');
        $saveName = \think\facade\Filesystem::disk('public')->putFile($savePath, $file);
        
        if (!$saveName) {
            return json(['code' => 0, 'msg' => '文件上传失败']);
        }
        
        // 获取文件信息
        $fileInfo = $file->getInfo();
        
        // 如果没有提供名称，使用文件名
        if (empty($name)) {
            $name = pathinfo($file->getOriginalName(), PATHINFO_FILENAME);
        }
        
        // 插入数据库
        $id = Db::name('music')->insertGetId([
            'name' => $name,
            'artist' => $artist,
            'album' => $album,
            'file_path' => $saveName,
            'size' => $fileInfo['size'],
            'format' => $ext,
            'status' => 1,
            'create_time' => time(),
            'update_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '上传成功', 'data' => ['id' => $id]]);
    }
    
    /**
     * 更新音乐信息
     */
    public function update()
    {
        $id = $this->request->param('id');
        $name = $this->request->param('name', '');
        $artist = $this->request->param('artist', '');
        $album = $this->request->param('album', '');
        $cover = $this->request->param('cover', '');
        $lyric = $this->request->param('lyric', '');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $music = Db::name('music')->where('id', $id)->find();
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        $data = ['update_time' => time()];
        if (!empty($name)) $data['name'] = $name;
        if (!empty($artist)) $data['artist'] = $artist;
        if (!empty($album)) $data['album'] = $album;
        if (!empty($cover)) $data['cover'] = $cover;
        if (isset($lyric)) $data['lyric'] = $lyric;
        
        Db::name('music')->where('id', $id)->update($data);
        
        return json(['code' => 1, 'msg' => '更新成功']);
    }
    
    /**
     * 删除音乐
     */
    public function delete()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        $music = Db::name('music')->where('id', $id)->find();
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        // 软删除，只更新状态
        Db::name('music')->where('id', $id)->update([
            'status' => 0,
            'update_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 增加播放次数
     */
    public function increasePlayCount()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        Db::name('music')->where('id', $id)->inc('play_count')->update();
        
        return json(['code' => 1, 'msg' => 'success']);
    }
}

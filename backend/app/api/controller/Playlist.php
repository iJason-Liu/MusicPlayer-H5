<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;

/**
 * 播放列表API接口
 */
class Playlist extends Api
{
    protected $noNeedLogin = [];
    protected $noNeedRight = ['*'];
    
    /**
     * 获取播放列表
     */
    public function index()
    {
        $userId = $this->getUserId();
        
        $list = Db::name('playlist')
            ->where('user_id', $userId)
            ->order('create_time', 'desc')
            ->select()
            ->toArray();
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $list]);
    }
    
    /**
     * 创建播放列表
     */
    public function create()
    {
        $userId = $this->getUserId();
        $name = $this->request->param('name', '');
        $cover = $this->request->param('cover', '');
        $description = $this->request->param('description', '');
        
        if (empty($name)) {
            return json(['code' => 0, 'msg' => '请输入列表名称']);
        }
        
        $id = Db::name('playlist')->insertGetId([
            'user_id' => $userId,
            'name' => $name,
            'cover' => $cover,
            'description' => $description,
            'music_count' => 0,
            'create_time' => time(),
            'update_time' => time()
        ]);
        
        return json(['code' => 1, 'msg' => '创建成功', 'data' => ['id' => $id]]);
    }
    
    /**
     * 更新播放列表
     */
    public function update()
    {
        $userId = $this->getUserId();
        $id = $this->request->param('id');
        $name = $this->request->param('name', '');
        $cover = $this->request->param('cover', '');
        $description = $this->request->param('description', '');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查是否是自己的列表
        $playlist = Db::name('playlist')->where('id', $id)->where('user_id', $userId)->find();
        if (!$playlist) {
            return json(['code' => 0, 'msg' => '播放列表不存在']);
        }
        
        $data = ['update_time' => time()];
        if (!empty($name)) $data['name'] = $name;
        if (!empty($cover)) $data['cover'] = $cover;
        if (isset($description)) $data['description'] = $description;
        
        Db::name('playlist')->where('id', $id)->update($data);
        
        return json(['code' => 1, 'msg' => '更新成功']);
    }
    
    /**
     * 删除播放列表
     */
    public function delete()
    {
        $userId = $this->getUserId();
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查是否是自己的列表
        $playlist = Db::name('playlist')->where('id', $id)->where('user_id', $userId)->find();
        if (!$playlist) {
            return json(['code' => 0, 'msg' => '播放列表不存在']);
        }
        
        // 删除列表
        Db::name('playlist')->where('id', $id)->delete();
        
        // 删除列表中的音乐关联
        Db::name('playlist_music')->where('playlist_id', $id)->delete();
        
        return json(['code' => 1, 'msg' => '删除成功']);
    }
    
    /**
     * 获取播放列表详情（包含音乐列表）
     */
    public function detail()
    {
        $userId = $this->getUserId();
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 获取列表信息
        $playlist = Db::name('playlist')->where('id', $id)->where('user_id', $userId)->find();
        if (!$playlist) {
            return json(['code' => 0, 'msg' => '播放列表不存在']);
        }
        
        // 获取列表中的音乐
        $musicList = Db::name('playlist_music')
            ->alias('pm')
            ->join('music m', 'pm.music_id = m.id')
            ->where('pm.playlist_id', $id)
            ->field('m.*, pm.sort')
            ->order('pm.sort', 'asc')
            ->order('pm.create_time', 'desc')
            ->select()
            ->each(function($item) {
                $item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
                return $item;
            });
        
        $playlist['music_list'] = $musicList;
        
        return json(['code' => 1, 'msg' => 'success', 'data' => $playlist]);
    }
    
    /**
     * 添加音乐到播放列表
     */
    public function addMusic()
    {
        $userId = $this->getUserId();
        $playlistId = $this->request->param('playlist_id');
        $musicId = $this->request->param('music_id');
        
        if (empty($playlistId) || empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查列表是否存在
        $playlist = Db::name('playlist')->where('id', $playlistId)->where('user_id', $userId)->find();
        if (!$playlist) {
            return json(['code' => 0, 'msg' => '播放列表不存在']);
        }
        
        // 检查音乐是否存在
        $music = Db::name('music')->where('id', $musicId)->find();
        if (!$music) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        // 检查是否已添加
        $exists = Db::name('playlist_music')
            ->where('playlist_id', $playlistId)
            ->where('music_id', $musicId)
            ->find();
        
        if ($exists) {
            return json(['code' => 0, 'msg' => '该音乐已在列表中']);
        }
        
        // 添加音乐
        Db::name('playlist_music')->insert([
            'playlist_id' => $playlistId,
            'music_id' => $musicId,
            'sort' => 0,
            'create_time' => time()
        ]);
        
        // 更新列表音乐数量
        Db::name('playlist')->where('id', $playlistId)->inc('music_count')->update();
        
        return json(['code' => 1, 'msg' => '添加成功']);
    }
    
    /**
     * 从播放列表移除音乐
     */
    public function removeMusic()
    {
        $userId = $this->getUserId();
        $playlistId = $this->request->param('playlist_id');
        $musicId = $this->request->param('music_id');
        
        if (empty($playlistId) || empty($musicId)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 检查列表是否存在
        $playlist = Db::name('playlist')->where('id', $playlistId)->where('user_id', $userId)->find();
        if (!$playlist) {
            return json(['code' => 0, 'msg' => '播放列表不存在']);
        }
        
        // 删除音乐
        $result = Db::name('playlist_music')
            ->where('playlist_id', $playlistId)
            ->where('music_id', $musicId)
            ->delete();
        
        if ($result) {
            // 更新列表音乐数量
            Db::name('playlist')->where('id', $playlistId)->dec('music_count')->update();
            return json(['code' => 1, 'msg' => '移除成功']);
        }
        
        return json(['code' => 0, 'msg' => '移除失败']);
    }
    
    /**
     * 获取当前用户ID
     */
    private function getUserId()
    {
        $token = $this->request->header('Authorization', '');
        // 简化处理，实际应该从 token 解析用户ID
        return 1;
    }
}

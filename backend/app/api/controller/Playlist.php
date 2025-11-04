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
     * 获取播放列表（分页）
     */
    public function list()
    {
        try {
            $userId = $this->getUserId();
            $page = $this->request->param('page', 1);
            $limit = $this->request->param('limit', 20);
            
            $list = Db::name('playlist')
                ->where('user_id', $userId)
                ->order('create_time', 'desc')
                ->page($page, $limit)
                ->select()
                ->toArray();
            
            $total = Db::name('playlist')->where('user_id', $userId)->count();
            
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
                'msg' => '获取播放列表失败：' . $e->getMessage(),
                'data' => []
            ]);
        }
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
                $item['url'] = 'https://diary.crayon.vip/Music/' . $item['file_path'];
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
     * 获取当前播放队列
     */
    public function getQueue()
    {
        try {
            $userId = $this->getUserId();
            
            // 从用户配置表或缓存中获取播放队列
            $queue = Db::name('user_config')
                ->where('user_id', $userId)
                ->where('config_key', 'play_queue')
                ->value('config_value');
            
            if ($queue) {
                $musicIds = json_decode($queue, true);
                if (!empty($musicIds)) {
                    // 根据音乐ID获取详细信息
                    $musicList = Db::name('music')
                        ->whereIn('id', $musicIds)
                        ->select()
                        ->each(function($item) {
                            $item['url'] = 'https://diary.crayon.vip/Music/' . $item['file_path'];
                            return $item;
                        });
                    
                    // 按照原始顺序排序
                    $sortedList = [];
                    foreach ($musicIds as $id) {
                        foreach ($musicList as $music) {
                            if ($music['id'] == $id) {
                                $sortedList[] = $music;
                                break;
                            }
                        }
                    }
                    
                    return json(['code' => 1, 'msg' => 'success', 'data' => $sortedList]);
                }
            }
            
            return json(['code' => 1, 'msg' => 'success', 'data' => []]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '获取播放队列失败：' . $e->getMessage(), 'data' => []]);
        }
    }
    
    /**
     * 保存当前播放队列
     */
    public function saveQueue()
    {
        try {
            $userId = $this->getUserId();
            $musicIds = $this->request->param('music_ids', []);
            
            if (!is_array($musicIds)) {
                return json(['code' => 0, 'msg' => '参数格式错误']);
            }
            
            // 保存到用户配置表
            $exists = Db::name('user_config')
                ->where('user_id', $userId)
                ->where('config_key', 'play_queue')
                ->find();
            
            if ($exists) {
                Db::name('user_config')
                    ->where('user_id', $userId)
                    ->where('config_key', 'play_queue')
                    ->update([
                        'config_value' => json_encode($musicIds),
                        'update_time' => time()
                    ]);
            } else {
                Db::name('user_config')->insert([
                    'user_id' => $userId,
                    'config_key' => 'play_queue',
                    'config_value' => json_encode($musicIds),
                    'create_time' => time(),
                    'update_time' => time()
                ]);
            }
            
            return json(['code' => 1, 'msg' => '保存成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '保存播放队列失败：' . $e->getMessage()]);
        }
    }
}

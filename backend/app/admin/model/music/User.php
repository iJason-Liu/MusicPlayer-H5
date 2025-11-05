<?php

namespace app\admin\model\music;

use app\common\model\TimeModel;

class User extends TimeModel
{
    protected $name = 'user';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = ['status_text', 'play_count', 'favorite_count', 'playlist_count'];
    
    // 隐藏字段
    protected $hidden = ['password'];
    
    // 状态
    public function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '正常',
        ];
    }
    
    // 状态文本
    public function getStatusTextAttr($value, $data)
    {
        $list = $this->getStatusList();
        return $list[$data['status']] ?? '';
    }
    
    // 头像完整URL
    public function getAvatarAttr($value)
    {
        if (empty($value)) {
            return '';
        }
        
        // 如果已经是完整URL，直接返回
        if (strpos($value, 'http') === 0) {
            return $value;
        }
        
        return request()->domain() . $value;
    }
    
    // 播放次数（去重后的音乐数量）
    public function getPlayCountAttr($value, $data)
    {
        return \think\facade\Db::name('play_history')
            ->where('user_id', $data['id'])
            ->group('music_id')
            ->count();
    }
    
    // 收藏数量
    public function getFavoriteCountAttr($value, $data)
    {
        return \think\facade\Db::name('favorite')
            ->where('user_id', $data['id'])
            ->count();
    }
    
    // 播放列表数量
    public function getPlaylistCountAttr($value, $data)
    {
        return \think\facade\Db::name('playlist')
            ->where('user_id', $data['id'])
            ->count();
    }
}

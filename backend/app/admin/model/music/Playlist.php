<?php

namespace app\admin\model\music;

use app\common\model\TimeModel;

class Playlist extends TimeModel
{
    protected $name = 'playlist';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = ['is_public_text'];
    
    // 是否公开
    public function getIsPublicList()
    {
        return [
            0 => '私密',
            1 => '公开',
        ];
    }
    
    // 是否公开文本
    public function getIsPublicTextAttr($value, $data)
    {
        $list = $this->getIsPublicList();
        return $list[$data['is_public']] ?? '';
    }
    
    // 关联用户
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->bind([
            'username' => 'username',
            'nickname' => 'nickname',
        ]);
    }
}

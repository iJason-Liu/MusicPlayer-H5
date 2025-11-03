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
    protected $append = ['status_text'];
    
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
}

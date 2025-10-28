<?php

namespace app\admin\model\music;

use think\Model;

class Music extends Model
{
    protected $name = 'music';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = ['url', 'status_text'];
    
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
    
    // 音乐URL
    public function getUrlAttr($value, $data)
    {
        if (empty($data['file_path'])) {
            return '';
        }
        
        return request()->domain() . '/wwwroot/alist/music/' . $data['file_path'];
    }
    
    // 文件大小格式化
    public function getFileSizeAttr($value)
    {
        if (empty($value)) {
            return '0 B';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        
        while ($value >= 1024 && $i < 3) {
            $value /= 1024;
            $i++;
        }
        
        return round($value, 2) . ' ' . $units[$i];
    }
    
    // 时长格式化
    public function getDurationAttr($value)
    {
        if (empty($value)) {
            return '00:00';
        }
        
        $minutes = floor($value / 60);
        $seconds = $value % 60;
        
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

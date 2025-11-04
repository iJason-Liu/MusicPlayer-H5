<?php

namespace app\admin\model\music;

use app\common\model\TimeModel;

class Music extends TimeModel
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
        
        return request()-> domain() . '/Music/' . $data['file_path'];
    }
    
    // 文件大小格式化为MB（用于显示）
    public function getSizeAttr($value, $data)
    {
        // 如果是读取操作，格式化显示
        if (empty($value)) {
            return '0.00 MB';
        }
        
        // 转换为MB
        $sizeMB = $value / (1024 * 1024);
        
        return round($sizeMB, 2) . ' MB';
    }
    
    // 文件大小设置器（保存时使用原始字节数）
    public function setSizeAttr($value)
    {
        // 如果传入的是数字，直接返回（字节数）
        return $value;
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

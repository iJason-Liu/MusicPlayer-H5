<?php

/**
 * 格式化文件大小
 */
function format_bytes($bytes)
{
    if ($bytes == 0) {
        return '0 B';
    }
    
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    
    while ($bytes >= 1024 && $i < 4) {
        $bytes /= 1024;
        $i++;
    }
    
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * 格式化时长
 */
function format_duration($seconds)
{
    if ($seconds == 0) {
        return '0分钟';
    }
    
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . '小时' . $minutes . '分钟';
    }
    
    return $minutes . '分钟';
}

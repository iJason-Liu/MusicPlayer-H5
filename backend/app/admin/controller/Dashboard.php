<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use think\facade\Db;

/**
 * 控制台
 */
class Dashboard extends Backend
{
    /**
     * 首页统计
     */
    public function index()
    {
        // 音乐统计
        $musicStats = [
            'total' => Db::name('music')->count(),
            'matched' => Db::name('music')->where('artist', '<>', '')->count(),
            'total_size' => Db::name('music')->sum('file_size'),
            'total_duration' => Db::name('music')->sum('duration'),
        ];
        
        // 最近添加
        $recentMusic = Db::name('music')
            ->order('create_time', 'desc')
            ->limit(10)
            ->select();
        
        $this->view->assign([
            'musicStats' => $musicStats,
            'recentMusic' => $recentMusic,
        ]);
        
        return $this->fetch();
    }
}

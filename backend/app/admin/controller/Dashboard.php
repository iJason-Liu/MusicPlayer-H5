<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\facade\Db;

#[ControllerAnnotation(title: '控制台')]
class Dashboard extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    #[NodeAnnotation(title: '首页统计')]
    public function index(Request $request): string
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
        
        $this->assign([
            'musicStats' => $musicStats,
            'recentMusic' => $recentMusic,
        ]);
        
        return $this->fetch();
    }
}

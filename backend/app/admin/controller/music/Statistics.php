<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\facade\Db;
use think\response\Json;

#[ControllerAnnotation(title: '统计分析')]
class Statistics extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    #[NodeAnnotation(title: '统计首页')]
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            $type = $request->param('type', 'overview');
            
            switch ($type) {
                case 'overview':
                    $data = $this->getOverview();
                    break;
                case 'music_rank':
                    $data = $this->getMusicRank();
                    break;
                case 'user_rank':
                    $data = $this->getUserRank();
                    break;
                case 'daily_stats':
                    $data = $this->getDailyStats();
                    break;
                default:
                    $data = [];
            }
            
            return json(['code' => 1, 'data' => $data]);
        }
        
        return $this->fetch();
    }

    /**
     * 概览统计
     */
    private function getOverview()
    {
        $totalUsers = Db::name('user')->count();
        $totalMusic = Db::name('music')->count();
        $totalPlayCount = Db::name('play_history')->count();
        $totalFavoriteCount = Db::name('favorite')->count();
        $totalPlaylistCount = Db::name('playlist')->count();
        
        // 今日新增
        $todayStart = strtotime(date('Y-m-d'));
        $todayUsers = Db::name('user')->where('create_time', '>=', $todayStart)->count();
        $todayPlayCount = Db::name('play_history')->where('play_time', '>=', $todayStart)->count();
        
        // 活跃用户（最近7天有播放记录）
        $weekStart = strtotime('-7 days');
        $activeUsers = Db::name('play_history')
            ->where('play_time', '>=', $weekStart)
            ->group('user_id')
            ->count();
        
        return [
            'total_users' => $totalUsers,
            'total_music' => $totalMusic,
            'total_play_count' => $totalPlayCount,
            'total_favorite_count' => $totalFavoriteCount,
            'total_playlist_count' => $totalPlaylistCount,
            'today_users' => $todayUsers,
            'today_play_count' => $todayPlayCount,
            'active_users' => $activeUsers,
        ];
    }

    /**
     * 音乐排行榜
     */
    private function getMusicRank()
    {
        $limit = input('limit', 20);
        
        // 播放次数排行
        $playRank = Db::name('play_history')
            ->alias('h')
            ->join('music m', 'h.music_id = m.id')
            ->field('m.id, m.name, m.artist, COUNT(*) as play_count')
            ->group('h.music_id')
            ->order('play_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        
        // 收藏次数排行
        $favoriteRank = Db::name('favorite')
            ->alias('f')
            ->join('music m', 'f.music_id = m.id')
            ->field('m.id, m.name, m.artist, COUNT(*) as favorite_count')
            ->group('f.music_id')
            ->order('favorite_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        
        return [
            'play_rank' => $playRank,
            'favorite_rank' => $favoriteRank,
        ];
    }

    /**
     * 用户排行榜
     */
    private function getUserRank()
    {
        $limit = input('limit', 20);
        
        // 播放次数排行
        $playRank = Db::name('play_history')
            ->alias('h')
            ->join('user u', 'h.user_id = u.id')
            ->field('u.id, u.username, u.nickname, COUNT(*) as play_count')
            ->group('h.user_id')
            ->order('play_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        
        // 收藏数量排行
        $favoriteRank = Db::name('favorite')
            ->alias('f')
            ->join('user u', 'f.user_id = u.id')
            ->field('u.id, u.username, u.nickname, COUNT(*) as favorite_count')
            ->group('f.user_id')
            ->order('favorite_count', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
        
        return [
            'play_rank' => $playRank,
            'favorite_rank' => $favoriteRank,
        ];
    }

    /**
     * 每日统计
     */
    private function getDailyStats()
    {
        $days = input('days', 7);
        
        $stats = [];
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date);
            $dayEnd = $dayStart + 86400;
            
            $playCount = Db::name('play_history')
                ->where('play_time', '>=', $dayStart)
                ->where('play_time', '<', $dayEnd)
                ->count();
            
            $activeUsers = Db::name('play_history')
                ->where('play_time', '>=', $dayStart)
                ->where('play_time', '<', $dayEnd)
                ->group('user_id')
                ->count();
            
            $stats[] = [
                'date' => $date,
                'play_count' => $playCount,
                'active_users' => $activeUsers,
            ];
        }
        
        return array_reverse($stats);
    }
}

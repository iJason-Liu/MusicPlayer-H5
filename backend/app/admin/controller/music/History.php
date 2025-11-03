<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\facade\Db;
use think\response\Json;

#[ControllerAnnotation(title: '播放历史')]
class History extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    public function __construct(App $app)
    {
        parent::__construct($app);
    }

    #[NodeAnnotation(title: '列表')]
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 20);
            $userId = $request->param('user_id', '');
            $musicId = $request->param('music_id', '');
            
            $where = [];
            if ($userId) {
                $where[] = ['user_id', '=', $userId];
            }
            if ($musicId) {
                $where[] = ['music_id', '=', $musicId];
            }
            
            $count = Db::name('play_history')->where($where)->count();
            $list = Db::name('play_history')->alias('h')
                ->join('user u', 'h.user_id = u.id')
                ->join('music m', 'h.music_id = m.id')
                ->where($where)
                ->field('h.*, u.username, u.nickname, m.name as music_name, m.artist')
                ->page($page, $limit)
                ->order('h.play_time', 'desc')
                ->select()
                ->toArray();
            
            // 格式化数据
            foreach ($list as &$item) {
                $item['play_time'] = date('Y-m-d H:i:s', $item['play_time']);
                $item['play_duration_text'] = $this->formatDuration($item['play_duration']);
            }
            
            $data = [
                'code' => 0,
                'msg' => '',
                'count' => $count,
                'data' => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    #[NodeAnnotation(title: '删除')]
    public function delete(Request $request): void
    {
        $this->checkPostRequest();
        $id = $request->param('id');
        
        try {
            Db::name('play_history')->whereIn('id', $id)->delete();
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 格式化时长
     */
    private function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%02d:%02d', $minutes, $secs);
    }
}

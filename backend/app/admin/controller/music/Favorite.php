<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use app\Request;
use think\App;
use think\facade\Db;
use think\response\Json;

#[ControllerAnnotation(title: '收藏管理')]
class Favorite extends AdminController
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
            
            $count = Db::name('favorite')->where($where)->count();
            $list = Db::name('favorite')->alias('f')
                ->join('user u', 'f.user_id = u.id')
                ->join('music m', 'f.music_id = m.id')
                ->where($where)
                ->field('f.*, u.username, u.nickname, m.name as music_name, m.artist, m.album')
                ->page($page, $limit)
                ->order('f.create_time', 'desc')
                ->select()
                ->toArray();
            
            // 格式化数据
            foreach ($list as &$item) {
                $item['create_time'] = date('Y-m-d H:i:s', $item['create_time']);
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
            Db::name('favorite')->whereIn('id', $id)->delete();
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败：' . $e->getMessage());
        }
    }
}

<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use app\Request;
use think\response\Json;

#[ControllerAnnotation(title: '播放列表管理')]
class Playlist extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    protected array $sort = [
        'id' => 'desc',
    ];

    protected bool $relationSearch = true;

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = \app\admin\model\music\Playlist::class;
    }

    #[NodeAnnotation(title: '列表', auth: true)]
    public function index(Request $request): Json|string
    {
        if ($request->isAjax()) {
            if (input('selectFields')) {
                return $this->selectList();
            }
            list($page, $limit, $where) = $this->buildTableParams();
            $count = self::$model::where($where)->count();
            $list  = self::$model::with(['user'])
                ->where($where)
                ->page($page, $limit)
                ->order($this->sort)
                ->select()
                ->toArray();
            $data  = [
                'code'  => 0,
                'msg'   => '',
                'count' => $count,
                'data'  => $list,
            ];
            return json($data);
        }
        return $this->fetch();
    }

    #[NodeAnnotation(title: '详情', auth: true)]
    public function detail(Request $request, $id = 0): string
    {
        $row = self::$model::with(['user'])->find($id);
        empty($row) && $this->error('数据不存在');
        
        // 获取播放列表中的音乐
        $musicList = \think\facade\Db::name('playlist_music')
            ->alias('pm')
            ->leftJoin('music m', 'pm.music_id = m.id')
            ->where('pm.playlist_id', $id)
            ->field('m.*, pm.create_time as add_time')
            ->order('pm.sort', 'asc')
            ->order('pm.create_time', 'desc')
            ->select()
            ->toArray();
        
        $this->assign('row', $row);
        $this->assign('musicList', $musicList);
        return $this->fetch();
    }
}

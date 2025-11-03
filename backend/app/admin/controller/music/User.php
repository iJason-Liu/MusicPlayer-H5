<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use app\Request;
use think\facade\Db;

#[ControllerAnnotation(title: '用户管理')]
class User extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    protected array $sort = [
        'id' => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = \app\admin\model\music\User::class;
    }

    #[NodeAnnotation(title: '添加', auth: true)]
    public function add(Request $request): string
    {
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [
                'row.username|用户名' => 'require',
                'row.password|密码' => 'require',
            ];
            $this->validate($post, $rule);
            
            // 加密密码
            if (!empty($post['row']['password'])) {
                $post['row']['password'] = password_hash($post['row']['password'], PASSWORD_DEFAULT);
            }
            
            try {
                Db::transaction(function() use ($post, &$save) {
                    $save = self::$model::create($post['row']);
                });
            } catch (\Exception $e) {
                $this->error('新增失败:' . $e->getMessage());
            }
            $save ? $this->success('新增成功') : $this->error('新增失败');
        }
        return $this->fetch();
    }

    #[NodeAnnotation(title: '编辑', auth: true)]
    public function edit(Request $request, $id = 0): string
    {
        $row = self::$model::find($id);
        empty($row) && $this->error('数据不存在');
        
        if ($request->isPost()) {
            $post = $request->post();
            $rule = [];
            $this->validate($post, $rule);
            
            // 如果密码为空，则不更新密码
            if (empty($post['row']['password'])) {
                unset($post['row']['password']);
            } else {
                // 加密密码
                $post['row']['password'] = password_hash($post['row']['password'], PASSWORD_DEFAULT);
            }
            
            try {
                Db::transaction(function() use ($post, $row, &$save) {
                    $save = $row->save($post['row']);
                });
            } catch (\Exception $e) {
                $this->error('保存失败:' . $e->getMessage());
            }
            $save ? $this->success('保存成功') : $this->error('保存失败');
        }
        $this->assign('row', $row);
        return $this->fetch();
    }

    #[NodeAnnotation(title: '重置密码', auth: true)]
    public function resetPassword(Request $request)
    {
        $this->checkPostRequest();
        
        $ids = $request->param('ids', '');
        $password = $request->param('password', '');
        
        if (empty($ids)) {
            $this->error('请选择要操作的用户');
        }
        
        if (empty($password)) {
            $this->error('请输入新密码');
        }
        
        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            self::$model::whereIn('id', $idArray)->update(['password' => $hashedPassword]);
            $this->success('密码重置成功');
        } catch (\Exception $e) {
            $this->error('密码重置失败：' . $e->getMessage());
        }
    }

    #[NodeAnnotation(title: '状态切换', auth: true)]
    public function changeStatus(Request $request)
    {
        $this->checkPostRequest();
        
        $ids = $request->param('ids', '');
        $status = $request->param('status', 1);
        
        if (empty($ids)) {
            $this->error('请选择要操作的数据');
        }
        
        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        
        try {
            self::$model::whereIn('id', $idArray)->update(['status' => $status]);
            $this->success('操作成功');
        } catch (\Exception $e) {
            $this->error('操作失败：' . $e->getMessage());
        }
    }
}

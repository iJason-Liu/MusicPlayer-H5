<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePlaylistTable extends Migrator
{
    /**
     * 播放列表表
     */
    public function change()
    {
        $table = $this->table('playlist', ['engine' => 'InnoDB', 'comment' => '播放列表表']);
        $table->addColumn('user_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '用户ID'])
              ->addColumn('name', 'string', ['limit' => 100, 'default' => '', 'comment' => '列表名称'])
              ->addColumn('cover', 'string', ['limit' => 255, 'default' => '', 'comment' => '封面'])
              ->addColumn('description', 'string', ['limit' => 500, 'default' => '', 'comment' => '描述'])
              ->addColumn('music_count', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '音乐数量'])
              ->addColumn('create_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
              ->addColumn('update_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '更新时间'])
              ->addIndex(['user_id'])
              ->create();
    }
}

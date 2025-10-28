<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateFavoriteTable extends Migrator
{
    /**
     * 收藏表
     */
    public function change()
    {
        $table = $this->table('favorite', ['engine' => 'InnoDB', 'comment' => '收藏表']);
        $table->addColumn('user_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '用户ID'])
              ->addColumn('music_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '音乐ID'])
              ->addColumn('create_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
              ->addIndex(['user_id'])
              ->addIndex(['music_id'])
              ->addIndex(['user_id', 'music_id'], ['unique' => true])
              ->create();
    }
}

<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePlayHistoryTable extends Migrator
{
    /**
     * 播放历史表
     */
    public function change()
    {
        $table = $this->table('play_history', ['engine' => 'InnoDB', 'comment' => '播放历史表']);
        $table->addColumn('user_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '用户ID'])
              ->addColumn('music_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '音乐ID'])
              ->addColumn('play_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '播放时间'])
              ->addColumn('duration', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '播放时长(秒)'])
              ->addColumn('create_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
              ->addIndex(['user_id'])
              ->addIndex(['music_id'])
              ->addIndex(['create_time'])
              ->create();
    }
}

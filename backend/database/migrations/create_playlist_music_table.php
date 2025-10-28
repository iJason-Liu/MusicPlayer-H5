<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreatePlaylistMusicTable extends Migrator
{
    /**
     * 播放列表音乐关联表
     */
    public function change()
    {
        $table = $this->table('playlist_music', ['engine' => 'InnoDB', 'comment' => '播放列表音乐关联表']);
        $table->addColumn('playlist_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '播放列表ID'])
              ->addColumn('music_id', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '音乐ID'])
              ->addColumn('sort', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '排序'])
              ->addColumn('create_time', 'integer', ['limit' => 11, 'default' => 0, 'comment' => '创建时间'])
              ->addIndex(['playlist_id'])
              ->addIndex(['music_id'])
              ->addIndex(['playlist_id', 'music_id'], ['unique' => true])
              ->create();
    }
}

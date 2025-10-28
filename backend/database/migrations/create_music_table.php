<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateMusicTable extends Migrator
{
    public function change()
    {
        $table = $this->table('music', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '音乐表']);
        
        $table->addColumn('name', 'string', ['limit' => 255, 'default' => '', 'comment' => '歌曲名称'])
            ->addColumn('artist', 'string', ['limit' => 255, 'default' => '', 'comment' => '歌手'])
            ->addColumn('album', 'string', ['limit' => 255, 'default' => '', 'comment' => '专辑'])
            ->addColumn('cover', 'string', ['limit' => 500, 'default' => '', 'comment' => '封面图'])
            ->addColumn('lyric', 'text', ['null' => true, 'comment' => '歌词'])
            ->addColumn('duration', 'integer', ['default' => 0, 'comment' => '时长(秒)'])
            ->addColumn('file_path', 'string', ['limit' => 500, 'default' => '', 'comment' => '文件路径'])
            ->addColumn('file_size', 'biginteger', ['default' => 0, 'comment' => '文件大小(字节)'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'comment' => '状态:0=禁用,1=正常'])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间'])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间'])
            ->addIndex(['name'], ['name' => 'idx_name'])
            ->addIndex(['artist'], ['name' => 'idx_artist'])
            ->addIndex(['status'], ['name' => 'idx_status'])
            ->create();
    }
}

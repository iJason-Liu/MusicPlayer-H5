<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateUserTable extends Migrator
{
    public function change()
    {
        // 用户表
        $table = $this->table('user', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '用户表']);
        
        $table->addColumn('username', 'string', ['limit' => 50, 'default' => '', 'comment' => '用户名'])
            ->addColumn('password', 'string', ['limit' => 255, 'default' => '', 'comment' => '密码'])
            ->addColumn('nickname', 'string', ['limit' => 50, 'default' => '', 'comment' => '昵称'])
            ->addColumn('avatar', 'string', ['limit' => 500, 'default' => '', 'comment' => '头像'])
            ->addColumn('status', 'integer', ['limit' => 1, 'default' => 1, 'comment' => '状态:0=禁用,1=正常'])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间'])
            ->addColumn('update_time', 'integer', ['default' => 0, 'comment' => '更新时间'])
            ->addIndex(['username'], ['unique' => true, 'name' => 'idx_username'])
            ->create();
        
        // 播放历史表
        $table = $this->table('play_history', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '播放历史表']);
        
        $table->addColumn('user_id', 'integer', ['default' => 0, 'comment' => '用户ID'])
            ->addColumn('music_id', 'integer', ['default' => 0, 'comment' => '音乐ID'])
            ->addColumn('play_time', 'integer', ['default' => 0, 'comment' => '播放时间'])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间'])
            ->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['music_id'], ['name' => 'idx_music_id'])
            ->addIndex(['play_time'], ['name' => 'idx_play_time'])
            ->create();
        
        // 收藏表
        $table = $this->table('favorite', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '收藏表']);
        
        $table->addColumn('user_id', 'integer', ['default' => 0, 'comment' => '用户ID'])
            ->addColumn('music_id', 'integer', ['default' => 0, 'comment' => '音乐ID'])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间'])
            ->addIndex(['user_id', 'music_id'], ['unique' => true, 'name' => 'idx_user_music'])
            ->create();
        
        // 播放列表表
        $table = $this->table('playlist', ['engine' => 'InnoDB', 'collation' => 'utf8mb4_unicode_ci', 'comment' => '播放列表表']);
        
        $table->addColumn('user_id', 'integer', ['default' => 0, 'comment' => '用户ID'])
            ->addColumn('music_id', 'integer', ['default' => 0, 'comment' => '音乐ID'])
            ->addColumn('sort', 'integer', ['default' => 0, 'comment' => '排序'])
            ->addColumn('create_time', 'integer', ['default' => 0, 'comment' => '创建时间'])
            ->addIndex(['user_id'], ['name' => 'idx_user_id'])
            ->addIndex(['sort'], ['name' => 'idx_sort'])
            ->create();
    }
}

<?php

use think\migration\Seeder;

class UserSeeder extends Seeder
{
    /**
     * 用户数据填充
     */
    public function run(): void
    {
        $data = [
            [
                'username' => 'admin',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'nickname' => '管理员',
                'avatar' => '',
                'status' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ],
            [
                'username' => 'test',
                'password' => password_hash('123456', PASSWORD_DEFAULT),
                'nickname' => '测试用户',
                'avatar' => '',
                'status' => 1,
                'create_time' => time(),
                'update_time' => time(),
            ],
        ];

        $this->table('user')->insert($data)->save();
    }
}

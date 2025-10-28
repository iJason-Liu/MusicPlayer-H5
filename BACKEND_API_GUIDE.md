# 后端 API 开发指南

## 目录结构

```
backend/
├── app/
│   ├── api/
│   │   └── controller/          # API 控制器
│   │       ├── User.php         # 用户接口
│   │       ├── Music.php        # 音乐接口
│   │       ├── History.php      # 播放历史接口
│   │       ├── Favorite.php     # 收藏接口
│   │       └── Playlist.php     # 播放列表接口
│   └── common/
│       └── controller/
│           └── Api.php          # API 基类
├── database/
│   ├── migrations/              # 数据库迁移文件
│   │   ├── create_user_table.php
│   │   ├── create_music_table.php
│   │   ├── create_favorite_table.php
│   │   ├── create_play_history_table.php
│   │   ├── create_playlist_table.php
│   │   └── create_playlist_music_table.php
│   └── seeds/                   # 数据填充
│       └── UserSeeder.php
└── route/
    └── api.php                  # API 路由配置
```

## 数据库表结构

### 1. user - 用户表
- id: 主键
- username: 用户名
- password: 密码（加密）
- nickname: 昵称
- avatar: 头像
- status: 状态（1:正常 0:禁用）
- create_time: 创建时间
- update_time: 更新时间

### 2. music - 音乐表
- id: 主键
- name: 歌曲名
- artist: 歌手
- album: 专辑
- cover: 封面
- file_path: 文件路径
- duration: 时长（秒）
- lyric: 歌词
- status: 状态（1:正常 0:禁用）
- create_time: 创建时间
- update_time: 更新时间

### 3. favorite - 收藏表
- id: 主键
- user_id: 用户ID
- music_id: 音乐ID
- create_time: 创建时间

### 4. play_history - 播放历史表
- id: 主键
- user_id: 用户ID
- music_id: 音乐ID
- play_time: 播放时间
- duration: 播放时长（秒）
- create_time: 创建时间

### 5. playlist - 播放列表表
- id: 主键
- user_id: 用户ID
- name: 列表名称
- cover: 封面
- description: 描述
- music_count: 音乐数量
- create_time: 创建时间
- update_time: 更新时间

### 6. playlist_music - 播放列表音乐关联表
- id: 主键
- playlist_id: 播放列表ID
- music_id: 音乐ID
- sort: 排序
- create_time: 创建时间

## 数据库初始化

### 1. 运行迁移

```bash
cd backend
php think migrate:run
```

### 2. 填充测试数据

```bash
php think seed:run
```

这将创建两个测试用户：
- 用户名: admin, 密码: 123456
- 用户名: test, 密码: 123456

## API 接口说明

### 认证机制

所有需要登录的接口都需要在请求头中携带 token：

```
Authorization: {token}
```

### 接口分类

1. **用户接口** (`/api/user/*`)
   - 登录、获取用户信息、统计数据、修改密码等

2. **音乐接口** (`/api/music/*`)
   - 音乐列表、搜索、详情、推荐、热门等

3. **播放历史接口** (`/api/history/*`)
   - 获取历史、添加记录、删除记录、清空历史

4. **收藏接口** (`/api/favorite/*`)
   - 收藏列表、添加收藏、取消收藏、检查收藏状态

5. **播放列表接口** (`/api/playlist/*`)
   - 列表管理、添加/移除音乐等

详细接口文档请查看 `API_DOCUMENTATION.md`

## 开发规范

### 1. 控制器规范

所有 API 控制器都继承自 `app\common\controller\Api` 基类：

```php
<?php
namespace app\api\controller;

use app\common\controller\Api;

class Example extends Api
{
    // 不需要登录的方法
    protected $noNeedLogin = ['index'];
    
    // 不需要鉴权的方法
    protected $noNeedRight = ['*'];
    
    public function index()
    {
        // 获取当前用户ID
        $userId = $this->getUserId();
        
        // 返回成功
        return json(['code' => 1, 'msg' => 'success', 'data' => []]);
        
        // 返回失败
        return json(['code' => 0, 'msg' => 'error']);
    }
}
```

### 2. 返回格式规范

统一使用 JSON 格式返回：

```json
{
  "code": 1,        // 1:成功 0:失败
  "msg": "success", // 提示信息
  "data": {}        // 返回数据
}
```

### 3. 数据库操作规范

使用 ThinkPHP 的查询构造器：

```php
use think\facade\Db;

// 查询
$list = Db::name('music')->where('status', 1)->select();

// 插入
Db::name('music')->insert($data);

// 更新
Db::name('music')->where('id', $id)->update($data);

// 删除
Db::name('music')->where('id', $id)->delete();
```

### 4. 跨域处理

已在 `Api` 基类中处理跨域问题，无需额外配置。

## 测试接口

### 使用 curl 测试

```bash
# 登录
curl -X POST http://localhost/api/user/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"123456"}'

# 获取音乐列表
curl http://localhost/api/music/list

# 获取用户信息（需要 token）
curl http://localhost/api/user/info \
  -H "Authorization: your_token_here"
```

### 使用 Postman 测试

1. 导入接口文档
2. 设置环境变量 `base_url` 为你的后端地址
3. 登录后将返回的 token 保存到环境变量
4. 在需要认证的接口中使用 `{{token}}`

## 注意事项

1. **Token 管理**: 当前使用简化的 token 机制，生产环境建议使用 JWT 或 Redis 存储
2. **密码加密**: 使用 PHP 的 `password_hash()` 和 `password_verify()` 函数
3. **SQL 注入防护**: 使用参数绑定，避免直接拼接 SQL
4. **XSS 防护**: 对用户输入进行过滤和转义
5. **文件上传**: 需要验证文件类型和大小
6. **日志记录**: 建议记录关键操作日志

## 扩展功能

### 1. 添加 JWT 认证

```bash
composer require firebase/php-jwt
```

### 2. 添加 Redis 缓存

```bash
composer require predis/predis
```

### 3. 添加图片上传

在 `Music` 控制器中添加上传方法：

```php
public function upload()
{
    $file = request()->file('file');
    $savename = \think\facade\Filesystem::disk('public')->putFile('music', $file);
    return json(['code' => 1, 'url' => '/storage/' . $savename]);
}
```

## 常见问题

### 1. 跨域问题
已在 `Api` 基类中处理，如果仍有问题，检查 Nginx 配置。

### 2. 路由不生效
确保 `route/api.php` 已被加载，检查 `config/route.php` 配置。

### 3. 数据库连接失败
检查 `.env` 文件中的数据库配置。

### 4. Token 验证失败
检查请求头中的 `Authorization` 字段是否正确。

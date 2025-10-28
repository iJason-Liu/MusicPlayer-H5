# 后端 API 快速设置指南

## 已完成的工作

✅ 创建了完整的 API 接口系统
✅ 实现了 6 个数据库表的迁移文件
✅ 创建了 5 个 API 控制器
✅ 配置了 API 路由
✅ 实现了用户认证基类
✅ 准备了测试数据填充脚本

## 接口列表

### 1. 用户接口 (User.php)
- ✅ 登录 `POST /api/user/login`
- ✅ 获取用户信息 `GET /api/user/info`
- ✅ 统计信息 `GET /api/user/statistics`
- ✅ 更新用户信息 `POST /api/user/update`
- ✅ 修改密码 `POST /api/user/password`
- ✅ 退出登录 `POST /api/user/logout`

### 2. 音乐接口 (Music.php)
- ✅ 音乐列表 `GET /api/music/list`
- ✅ 搜索音乐 `GET /api/music/search`
- ✅ 音乐详情 `GET /api/music/detail`
- ✅ 推荐音乐 `GET /api/music/recommend`
- ✅ 热门音乐 `GET /api/music/hot`

### 3. 播放历史接口 (History.php)
- ✅ 播放历史列表 `GET /api/history/list`
- ✅ 添加播放记录 `POST /api/history/add`
- ✅ 删除播放记录 `POST /api/history/delete`
- ✅ 清空播放历史 `POST /api/history/clear`

### 4. 收藏接口 (Favorite.php)
- ✅ 收藏列表 `GET /api/favorite/list`
- ✅ 添加收藏 `POST /api/favorite/add`
- ✅ 取消收藏 `POST /api/favorite/remove`
- ✅ 检查收藏状态 `GET /api/favorite/check`

### 5. 播放列表接口 (Playlist.php)
- ✅ 播放列表 `GET /api/playlist/list`
- ✅ 创建播放列表 `POST /api/playlist/create`
- ✅ 更新播放列表 `POST /api/playlist/update`
- ✅ 删除播放列表 `POST /api/playlist/delete`
- ✅ 播放列表详情 `GET /api/playlist/detail`
- ✅ 添加音乐到列表 `POST /api/playlist/add-music`
- ✅ 从列表移除音乐 `POST /api/playlist/remove-music`

## 快速开始

### 1. 初始化数据库

```bash
cd backend
chmod +x init-database.sh
./init-database.sh
```

或手动执行：

```bash
cd backend
php think migrate:run
php think seed:run
```

### 2. 启动开发服务器

```bash
cd backend
php think run
```

默认地址: http://localhost:8000

### 3. 测试接口

使用提供的测试文件 `backend/api-test.http`，可以用以下工具测试：

- VS Code: 安装 REST Client 插件
- IntelliJ IDEA: 内置支持
- Postman: 导入接口文档
- curl: 命令行测试

#### 测试登录接口

```bash
curl -X POST http://localhost:8000/api/user/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"123456"}'
```

返回示例：
```json
{
  "code": 1,
  "msg": "登录成功",
  "data": {
    "token": "abc123...",
    "user": {
      "id": 1,
      "username": "admin",
      "nickname": "管理员",
      "avatar": ""
    }
  }
}
```

#### 测试音乐列表接口

```bash
curl http://localhost:8000/api/music/list
```

## 数据库表结构

### user - 用户表
```sql
- id (主键)
- username (用户名)
- password (密码)
- nickname (昵称)
- avatar (头像)
- status (状态)
- create_time (创建时间)
- update_time (更新时间)
```

### music - 音乐表
```sql
- id (主键)
- name (歌曲名)
- artist (歌手)
- album (专辑)
- cover (封面)
- file_path (文件路径)
- duration (时长)
- lyric (歌词)
- status (状态)
- create_time (创建时间)
- update_time (更新时间)
```

### favorite - 收藏表
```sql
- id (主键)
- user_id (用户ID)
- music_id (音乐ID)
- create_time (创建时间)
```

### play_history - 播放历史表
```sql
- id (主键)
- user_id (用户ID)
- music_id (音乐ID)
- play_time (播放时间)
- duration (播放时长)
- create_time (创建时间)
```

### playlist - 播放列表表
```sql
- id (主键)
- user_id (用户ID)
- name (列表名称)
- cover (封面)
- description (描述)
- music_count (音乐数量)
- create_time (创建时间)
- update_time (更新时间)
```

### playlist_music - 播放列表音乐关联表
```sql
- id (主键)
- playlist_id (播放列表ID)
- music_id (音乐ID)
- sort (排序)
- create_time (创建时间)
```

## 文件说明

### 控制器文件
- `backend/app/api/controller/User.php` - 用户接口
- `backend/app/api/controller/Music.php` - 音乐接口
- `backend/app/api/controller/History.php` - 播放历史接口
- `backend/app/api/controller/Favorite.php` - 收藏接口
- `backend/app/api/controller/Playlist.php` - 播放列表接口
- `backend/app/common/controller/Api.php` - API 基类

### 数据库文件
- `backend/database/migrations/create_user_table.php` - 用户表迁移
- `backend/database/migrations/create_music_table.php` - 音乐表迁移
- `backend/database/migrations/create_favorite_table.php` - 收藏表迁移
- `backend/database/migrations/create_play_history_table.php` - 播放历史表迁移
- `backend/database/migrations/create_playlist_table.php` - 播放列表表迁移
- `backend/database/migrations/create_playlist_music_table.php` - 播放列表音乐关联表迁移
- `backend/database/seeds/UserSeeder.php` - 用户数据填充

### 配置文件
- `backend/route/api.php` - API 路由配置

### 文档文件
- `API_DOCUMENTATION.md` - 完整的 API 接口文档
- `BACKEND_API_GUIDE.md` - 后端开发指南
- `backend/api-test.http` - API 测试文件

## 注意事项

### 1. 跨域配置
已在 `Api.php` 基类中处理跨域问题，支持：
- GET, POST, PUT, DELETE, OPTIONS 方法
- Content-Type, Authorization, X-Requested-With 请求头

### 2. 认证机制
当前使用简化的 token 机制，生产环境建议：
- 使用 JWT (JSON Web Token)
- 使用 Redis 存储 token
- 设置 token 过期时间
- 实现 token 刷新机制

### 3. 密码安全
- 使用 `password_hash()` 加密密码
- 使用 `password_verify()` 验证密码
- 不要在日志中记录密码

### 4. SQL 安全
- 使用参数绑定，避免 SQL 注入
- 不要直接拼接 SQL 语句
- 对用户输入进行验证和过滤

### 5. 文件路径
音乐文件路径配置在控制器中：
```php
$item['url'] = request()->domain() . '/wwwroot/alist/music/' . $item['file_path'];
```

根据实际情况修改路径。

## 下一步

1. ✅ 数据库已初始化
2. ✅ API 接口已实现
3. 🔄 前端对接 API
4. 🔄 测试所有接口
5. 🔄 优化性能和安全性

## 测试账号

- 用户名: `admin` / 密码: `123456`
- 用户名: `test` / 密码: `123456`

## 相关文档

- [API 接口文档](API_DOCUMENTATION.md)
- [后端开发指南](BACKEND_API_GUIDE.md)
- [项目结构](PROJECT_STRUCTURE.md)

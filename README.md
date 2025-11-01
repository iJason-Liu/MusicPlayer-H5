# 音乐播放器项目

一个基于 Vue 3 + ThinkPHP 6 的在线音乐播放器。

## 项目架构

```
前端：Vue 3 + Vite + Vant UI
后端：ThinkPHP 6 + MySQL
音乐：独立域名存储
```

## 域名配置

- **前端 + API**：https://diary.crayon.vip/
- **音乐文件**：https://alist.crayon.vip/Music/

## 功能特性

- ✅ 音乐播放（支持 mp3, flac, wav, ogg, m4a）
- ✅ 音乐搜索
- ✅ 播放历史
- ✅ 收藏功能
- ✅ 播放列表管理
- ✅ 响应式设计
- ✅ 断点续传
- ✅ 跨域支持

## 快速开始

### 1. 初始化数据库

```bash
cd backend/database
mysql -h127.0.0.1 -umusic -pKzic52W5Jc7LwhSz music < music.sql
```

### 2. 配置 Nginx CORS

编辑 `alist.crayon.vip` 配置，添加：

```nginx
location /Music {
    add_header Access-Control-Allow-Origin *;
    add_header Access-Control-Allow-Methods 'GET, OPTIONS';
    add_header Access-Control-Allow-Headers 'Range, Content-Type';
    add_header Accept-Ranges bytes;
    expires 30d;
}
```

重启 Nginx：
```bash
nginx -s reload
```

### 3. 导入音乐

```bash
cd backend
php import_music.php
```

### 4. 部署前端

上传 `frontend/h5/` 目录到服务器：
```bash
scp -r frontend/h5/* root@your-server:/www/wwwroot/diary.crayon.vip/
```

### 5. 测试

```bash
# 测试 API
curl https://diary.crayon.vip/api/music/list

# 测试音乐文件
curl -I https://alist.crayon.vip/Music/xxx.mp3

# 访问前端
# 浏览器打开：https://diary.crayon.vip/
```

## 文档索引

### 快速指南
- **立即部署**：[DEPLOY_NOW.md](DEPLOY_NOW.md) - 5 步快速部署
- **使用指南**：[HOW_TO_USE.md](HOW_TO_USE.md) - 导入音乐工具使用

### 详细文档
- **前后端整合**：[FRONTEND_BACKEND_INTEGRATION.md](FRONTEND_BACKEND_INTEGRATION.md)
- **API 文档**：[backend/API.md](backend/API.md)
- **CORS 配置**：[backend/CORS_CONFIG.md](backend/CORS_CONFIG.md)
- **部署文档**：[backend/DEPLOY.md](backend/DEPLOY.md)
- **快速开始**：[backend/QUICK_START.md](backend/QUICK_START.md)
- **导入指南**：[backend/IMPORT_MUSIC_GUIDE.md](backend/IMPORT_MUSIC_GUIDE.md)

### 检查清单
- **部署检查**：[DEPLOYMENT_CHECKLIST.md](DEPLOYMENT_CHECKLIST.md)
- **配置总结**：[FINAL_CONFIG_SUMMARY.md](FINAL_CONFIG_SUMMARY.md)
- **完成总结**：[BACKEND_COMPLETE.md](BACKEND_COMPLETE.md)

## 目录结构

```
.
├── frontend/                 # 前端项目
│   ├── src/
│   │   ├── api/             # API 接口
│   │   ├── components/      # 组件
│   │   ├── stores/          # 状态管理
│   │   ├── views/           # 页面
│   │   └── router/          # 路由
│   ├── h5/                  # 构建输出
│   └── vite.config.js       # Vite 配置
│
├── backend/                 # 后端项目
│   ├── app/
│   │   └── api/
│   │       └── controller/  # 控制器
│   ├── database/
│   │   └── music.sql        # 数据库表结构
│   ├── route/
│   │   └── api.php          # 路由配置
│   └── import_music.php     # 音乐导入工具
│
└── docs/                    # 文档
```

## 技术栈

### 前端
- Vue 3
- Vite
- Vant UI
- Pinia
- Vue Router
- Axios

### 后端
- ThinkPHP 6
- MySQL 5.7+
- PHP 7.2+

### 服务器
- Nginx
- PHP-FPM

## 数据库表

- `mu_user` - 用户表
- `mu_music` - 音乐表
- `mu_play_history` - 播放历史表
- `mu_favorite` - 收藏表
- `mu_playlist` - 播放列表表
- `mu_playlist_music` - 播放列表音乐关联表

## API 接口

### 音乐相关
- `GET /api/music/list` - 获取音乐列表
- `GET /api/music/search` - 搜索音乐
- `GET /api/music/detail` - 获取音乐详情
- `GET /api/music/recommend` - 推荐音乐
- `GET /api/music/hot` - 热门音乐

### 用户相关
- `POST /api/user/login` - 用户登录
- `GET /api/user/info` - 获取用户信息
- `GET /api/user/statistics` - 统计信息

### 播放历史
- `GET /api/history/list` - 播放历史列表
- `POST /api/history/add` - 添加播放记录
- `POST /api/history/clear` - 清空播放历史

### 收藏相关
- `GET /api/favorite/list` - 收藏列表
- `POST /api/favorite/add` - 添加收藏
- `POST /api/favorite/remove` - 取消收藏

### 播放列表
- `GET /api/playlist/list` - 播放列表
- `POST /api/playlist/create` - 创建播放列表
- `GET /api/playlist/detail` - 播放列表详情
- `POST /api/playlist/add-music` - 添加音乐到列表

详细 API 文档：[backend/API.md](backend/API.md)

## 配置说明

### 前端配置

**环境变量**（`.env.production`）：
```env
VITE_API_BASE_URL=https://diary.crayon.vip/api
```

### 后端配置

**数据库配置**（`.env`）：
```env
DB_HOST=127.0.0.1
DB_NAME=music
DB_USER=music
DB_PASS=********
DB_PORT=3306
DB_PREFIX=mu_
```

### 音乐导入配置

编辑 `backend/import_music.php`：
```php
$musicDir = '/www/wwwroot/alist/Music/';  // 音乐目录
$recursive = false;                        // 是否递归扫描
$autoParseFilename = true;                 // 自动解析文件名
$overwriteExisting = false;                // 是否覆盖已存在
```

## 常见问题

### 1. 跨域错误

**解决**：在 `alist.crayon.vip` 配置 CORS，参考 [backend/CORS_CONFIG.md](backend/CORS_CONFIG.md)

### 2. 音乐列表为空

**解决**：运行 `php import_music.php` 导入音乐

### 3. API 返回 404

**解决**：检查 Nginx 配置和后端路由

### 4. 前端页面空白

**解决**：检查前端文件是否上传，查看浏览器控制台错误

详细问题排查：[FRONTEND_BACKEND_INTEGRATION.md](FRONTEND_BACKEND_INTEGRATION.md)

## 性能优化

- ✅ 启用 Gzip 压缩
- ✅ 浏览器缓存
- ✅ 静态资源 CDN
- ✅ 数据库索引优化
- ✅ Redis 缓存（可选）

## 安全建议

- ✅ 修改默认管理员密码
- ✅ 使用 HTTPS
- ✅ 实现 JWT token 验证
- ✅ 限制 API 访问频率
- ✅ 定期备份数据库
- ✅ 关闭生产环境调试模式

## 开发计划

- [ ] 用户注册功能
- [ ] 音乐上传功能
- [ ] 歌词显示
- [ ] 评论功能
- [ ] 分享功能
- [ ] 移动端优化
- [ ] PWA 支持

## 许可证

MIT License

## 作者
Jason Liu
开发时间：2025-11-01

## 致谢

- Vue.js
- ThinkPHP
- Vant UI
- Font Awesome

---

**快速开始**：查看 [DEPLOY_NOW.md](DEPLOY_NOW.md)  
**遇到问题**：查看 [FRONTEND_BACKEND_INTEGRATION.md](FRONTEND_BACKEND_INTEGRATION.md)  
**导入音乐**：查看 [HOW_TO_USE.md](HOW_TO_USE.md)

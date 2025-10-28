# 🎵 H5 音乐播放器

一个基于 **ThinkPHP 6 + EasyAdmin 8.1 + Vue 3** 的现代化音乐播放器，具有精美的液态玻璃 UI 设计和流畅的操作体验。

## ✨ 功能特性

### 后台管理功能

- 📊 **统计面板**：音乐总数、匹配率、存储空间、总时长统计
- 🎵 **音乐管理**：增删改查、批量操作
- � **扫描功能**：自动扫描服务器音乐目录
- 🎯 **信息匹配**：自动从网易云获取封面、歌词、专辑信息
- ⬆️ **上传功能**：支持音乐文件上传（MP3、FLAC、WAV、M4A）
- � **搜批量操作**：批量匹配信息、批量删除
- 💾 **缓存机制**：音乐信息缓存 7 天，提升性能

### 前端播放功能

- 🎧 播放服务器音乐文件（支持 MP3、FLAC、WAV、M4A）
- 🎵 自动显示歌曲信息（封面、歌词、专辑）
- ⏯️ 完整的播放控制（播放/暂停/上一曲/下一曲）
- 💾 播放列表管理（添加、移除、排序）
- 🕒 歌词同步滚动显示
- 💖 收藏喜欢的歌曲
- 📜 播放历史记录
- 🔍 音乐搜索功能
- 🎨 **液态玻璃悬浮 UI 设计**（苹果风格）
- 📱 完美适配移动端
- 🎭 三种播放模式（列表循环/随机播放/单曲循环）

## 🛠️ 技术栈

### 后端

- ThinkPHP 8.1+
- EasyAdmin 8.1
- PHP 8.1+
- MySQL 5.7+
- 网易云音乐 API

### 前端

- Vue 3.4+
- Vite 5.0+
- Pinia 2.1+（状态管理）
- Vue Router 4.2+
- Axios 1.6+
- Vant 4.8+（UI 组件库）
- SCSS
- Font Awesome 6.5+

## 📦 安装部署

### 环境要求

- PHP >= 8.1
- MySQL >= 5.7
- Node.js >= 16.0
- Composer
- 已安装 ThinkPHP 6 + EasyAdmin 8.1

### 后端部署

#### 1. 复制文件到项目

```bash
# 复制控制器
cp backend/app/admin/controller/music/Music.php your-project/app/admin/controller/music/
cp backend/app/admin/controller/Dashboard.php your-project/app/admin/controller/
cp backend/app/api/controller/Music.php your-project/app/api/controller/

# 复制模型
cp backend/app/admin/model/music/Music.php your-project/app/admin/model/music/

# 复制视图
cp backend/app/admin/view/music/music/index.html your-project/app/admin/view/music/music/
cp backend/app/admin/view/dashboard/index.html your-project/app/admin/view/dashboard/

# 复制路由
cp backend/route/api.php your-project/route/
```

#### 2. 创建数据库表

```bash
# 进入项目目录
cd your-project

# 复制迁移文件
cp backend/database/migrations/create_music_table.php database/migrations/

# 执行迁移
php think migrate:run
```

或者直接执行 SQL：

```sql
CREATE TABLE `music` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT '' COMMENT '歌曲名称',
  `artist` varchar(255) DEFAULT '' COMMENT '歌手',
  `album` varchar(255) DEFAULT '' COMMENT '专辑',
  `cover` varchar(500) DEFAULT '' COMMENT '封面图',
  `lyric` text COMMENT '歌词',
  `duration` int(11) DEFAULT 0 COMMENT '时长(秒)',
  `file_path` varchar(500) DEFAULT '' COMMENT '文件路径',
  `file_size` bigint(20) DEFAULT 0 COMMENT '文件大小(字节)',
  `status` tinyint(1) DEFAULT 1 COMMENT '状态:0=禁用,1=正常',
  `create_time` int(11) DEFAULT 0 COMMENT '创建时间',
  `update_time` int(11) DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_artist` (`artist`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='音乐表';
```

#### 3. 创建音乐目录

```bash
# 创建音乐存储目录
mkdir -p public/wwwroot/alist/music
chmod -R 755 public/wwwroot

# 上传你的音乐文件到该目录
```

#### 4. 配置后台菜单

在 EasyAdmin 后台添加菜单：

```
菜单名称：音乐管理
路由地址：music/music/index
图标：fa fa-music
父级菜单：根据需要选择
```

#### 5. 配置跨域（如需要）

编辑 `app/middleware.php`：

```php
return [
    \app\http\middleware\AllowCrossDomain::class,
];
```

### 前端部署

#### 1. 安装依赖

```bash
cd frontend
npm install
# 或使用 yarn
yarn install
```

#### 2. 配置 API 地址

编辑 `frontend/vite.config.js`：

```javascript
export default defineConfig({
  // ...
  server: {
    port: 3000,
    proxy: {
      '/api': {
        target: 'http://your-backend-domain.com', // 修改为你的后端地址
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, '')
      }
    }
  }
})
```

#### 3. 开发模式运行

```bash
npm run dev
```

访问：http://localhost:3000

#### 4. 生产环境构建

```bash
npm run build
```

构建完成后，将 `dist` 目录部署到服务器：

```bash
# 方式1：部署到后端项目的 public 目录
cp -r dist/* your-project/public/h5/

# 方式2：使用 Nginx 单独部署
# 将 dist 目录内容复制到 Nginx 网站根目录
```

#### 5. Nginx 配置示例

```nginx
server {
    listen 80;
    server_name music.yourdomain.com;
    root /var/www/music-player/dist;
    index index.html;

    # 前端路由
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 代理
    location /api/ {
        proxy_pass http://your-backend-domain.com/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }

    # 音乐文件
    location /wwwroot/ {
        alias /var/www/your-project/public/wwwroot/;
        add_header Access-Control-Allow-Origin *;
    }
}
```

#### 6. Apache 配置示例

在 `public/.htaccess` 添加：

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /
    RewriteRule ^index\.html$ - [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule . /index.html [L]
</IfModule>

# 允许跨域访问音乐文件
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
</IfModule>
```

## 📱 页面说明

### 主要页面

1. **音乐库（/home）**
   - 展示所有音乐列表
   - 支持搜索功能
   - 点击歌曲即可播放

2. **播放器（/player）**
   - 全屏播放界面
   - 旋转封面动画
   - 歌词显示
   - 进度条控制
   - 播放模式切换（列表循环/随机/单曲循环）

3. **我的（/mine）**
   - 个人信息展示
   - 播放历史
   - 播放列表
   - 我的喜欢
   - 统计信息

### 组件说明

- **MiniPlayer**: 底部迷你播放器，显示当前播放状态
- **MusicItem**: 音乐列表项组件
- **GlassTabbar**: 液态玻璃效果的底部导航栏

## 🎨 UI 设计特点

- **液态玻璃效果**：使用 `backdrop-filter` 实现毛玻璃背景
- **渐变背景**：紫色系渐变，营造音乐氛围
- **流畅动画**：旋转封面、过渡动画
- **响应式设计**：完美适配各种屏幕尺寸
- **触觉反馈**：点击缩放效果

## 🔧 配置说明

### 后端配置

修改音乐目录路径（`app/admin/controller/music/Music.php`）：

```php
$this->musicDir = root_path() . 'public/wwwroot/alist/music/';
```

### 音乐信息 API

默认使用网易云音乐第三方 API，可替换为其他音乐 API：

```php
$api = "https://api.vvhan.com/api/wyy?type=song&msg=" . urlencode($keyword);
```

### 文件上传限制

修改上传大小限制（`app/admin/controller/music/Music.php`）：

```php
validate(['file' => [
    'fileSize' => 50 * 1024 * 1024, // 50MB，可根据需要调整
    'fileExt' => 'mp3,flac,wav,m4a',
]])->check(['file' => $file]);
```

## 📝 使用说明

### 后台管理

1. 登录 EasyAdmin 后台
2. 进入"音乐管理"菜单
3. 点击"扫描音乐文件"按钮，自动扫描服务器目录
4. 选择需要匹配信息的歌曲，点击"匹配信息"
5. 也可以手动上传音乐文件
6. 支持批量删除、批量匹配等操作

### 前端使用

1. 访问前端页面（如：<http://localhost:3000>）
2. 在音乐库中浏览和搜索歌曲
3. 点击歌曲开始播放
4. 使用底部迷你播放器快速控制
5. 收藏喜欢的歌曲到"我的喜欢"
6. 查看播放历史和播放列表
7. 切换播放模式（循环/随机/单曲）

## 🚀 性能优化

- ✅ 音乐信息缓存（7天）
- ✅ 懒加载图片
- ✅ 组件按需加载
- ✅ 防抖搜索
- ✅ 数据库索引优化
- ✅ 文件路径相对化
- ✅ API 请求超时控制
- 🔄 虚拟滚动（可扩展）
- 🔄 CDN 加速（可扩展）

## 🐛 常见问题

### 1. 音乐文件无法播放

- 检查文件路径是否正确
- 确认 Nginx/Apache 配置允许访问音乐目录
- 检查跨域配置是否正确

### 2. 无法匹配音乐信息

- 检查网络连接
- 确认第三方 API 可用
- 尝试手动编辑音乐信息

### 3. 上传失败

- 检查 PHP 上传大小限制（`upload_max_filesize`、`post_max_size`）
- 确认目录有写入权限
- 检查磁盘空间是否充足

### 4. 前端无法连接后端

- 检查 API 代理配置
- 确认后端服务正常运行
- 检查跨域中间件是否启用

## 📄 License

MIT License

## 👨‍💻 开发者

如有问题或建议，欢迎提交 Issue 或 PR。

## 📚 相关文档

- 📖 [快速开始](QUICKSTART.md) - 5 分钟快速体验
- 🚀 [部署指南](DEPLOY.md) - 详细的生产环境部署步骤
- 📁 [项目结构](PROJECT_STRUCTURE.md) - 了解项目组织方式
- 📝 [更新日志](CHANGELOG.md) - 查看版本更新记录
- 🤝 [贡献指南](CONTRIBUTING.md) - 参与项目开发
- 📊 [项目总结](PROJECT_SUMMARY.md) - 完整的项目总结
- 🎨 [UI 优化说明](OPTIMIZATION.md) - UI 优化详细说明
- 🐛 [UI 修复总结](UI_FIX_SUMMARY.md) - 底部导航和页面高度修复

## 🙏 致谢

感谢以下开源项目：

- [ThinkPHP](https://www.thinkphp.cn/) - 强大的 PHP 框架
- [EasyAdmin](https://gitee.com/zhongshaofa/easyadmin) - 优秀的后台管理系统
- [Vue.js](https://vuejs.org/) - 渐进式 JavaScript 框架
- [Vant](https://vant-ui.github.io/) - 轻量、可靠的移动端组件库
- [Vite](https://vitejs.dev/) - 下一代前端构建工具
- [Font Awesome](https://fontawesome.com/) - 图标库
- [网易云音乐 API](https://api.vvhan.com/) - 音乐信息接口

## 📄 开源协议

本项目基于 [MIT License](LICENSE) 开源。

## 🌟 Star History

如果这个项目对你有帮助，请给个 Star ⭐

## 📞 联系我们

- 💬 提交 Issue：[GitHub Issues](https://github.com/your-repo/issues)
- 📧 邮件联系：your-email@example.com
- 💬 加入讨论：[GitHub Discussions](https://github.com/your-repo/discussions)

---

**享受音乐，享受生活** 🎵

**Made with ❤️ by Music Player Team**

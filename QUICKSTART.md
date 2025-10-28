# 🚀 快速开始

5 分钟快速体验音乐播放器！

## 📋 前置要求

- ✅ PHP 8.1+
- ✅ MySQL 5.7+
- ✅ Node.js 16.0+
- ✅ 已安装 ThinkPHP 6 + EasyAdmin 8.1

## 🎯 快速部署（开发环境）

### 步骤 1：克隆项目

```bash
git clone https://github.com/your-repo/music-player.git
cd music-player
```

### 步骤 2：后端配置

#### 2.1 复制文件到你的 ThinkPHP 项目

```bash
# 假设你的项目在 /var/www/your-project
cp -r backend/* /var/www/your-project/
```

#### 2.2 创建数据库

```bash
mysql -u root -p
```

```sql
CREATE DATABASE music_player CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE music_player;

-- 创建音乐表
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

EXIT;
```

#### 2.3 配置数据库连接

编辑 `.env` 文件：

```ini
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = music_player
USERNAME = root
PASSWORD = your_password
HOSTPORT = 3306
CHARSET = utf8mb4
```

#### 2.4 创建音乐目录

```bash
cd /var/www/your-project
mkdir -p public/wwwroot/alist/music
chmod -R 755 public/wwwroot
```

#### 2.5 添加测试音乐

```bash
# 复制一些 MP3 文件到音乐目录
cp ~/Music/*.mp3 public/wwwroot/alist/music/
```

#### 2.6 配置后台菜单

登录 EasyAdmin 后台，添加菜单：

- 菜单名称：`音乐管理`
- 路由地址：`music/music/index`
- 图标：`fa fa-music`

### 步骤 3：前端配置

#### 3.1 安装依赖

```bash
cd frontend
npm install
```

#### 3.2 配置 API 地址

编辑 `vite.config.js`，修改后端地址：

```javascript
server: {
  port: 3000,
  proxy: {
    '/api': {
      target: 'http://localhost:8080', // 改为你的后端地址
      changeOrigin: true
    }
  }
}
```

#### 3.3 启动开发服务器

```bash
npm run dev
```

或使用快速启动脚本：

```bash
cd ..
./start.sh
```

### 步骤 4：开始使用

1. **后台管理**：访问 <http://localhost:8080/admin>
   - 进入"音乐管理"
   - 点击"扫描音乐文件"
   - 选择歌曲，点击"匹配信息"

2. **前端播放**：访问 <http://localhost:3000>
   - 浏览音乐库
   - 点击歌曲播放
   - 享受音乐！

## 🎉 完成！

现在你可以：

- ✅ 在后台管理音乐
- ✅ 在前端播放音乐
- ✅ 收藏喜欢的歌曲
- ✅ 查看播放历史

## 📚 下一步

- 📖 阅读 [README.md](README.md) 了解完整功能
- 🚀 查看 [DEPLOY.md](DEPLOY.md) 学习生产环境部署
- 📁 参考 [PROJECT_STRUCTURE.md](PROJECT_STRUCTURE.md) 了解项目结构

## ❓ 常见问题

### Q1: 音乐无法播放？

**A:** 检查以下几点：

1. 音乐文件路径是否正确
2. Nginx/Apache 是否允许访问音乐目录
3. 浏览器控制台是否有错误

### Q2: 无法匹配音乐信息？

**A:** 可能原因：

1. 网络连接问题
2. 第三方 API 限流
3. 歌曲名称不准确

解决方法：手动编辑音乐信息

### Q3: 前端无法连接后端？

**A:** 检查：

1. 后端服务是否运行
2. API 代理配置是否正确
3. 跨域中间件是否启用

### Q4: 上传失败？

**A:** 检查：

1. PHP 上传大小限制（`php.ini`）
2. 目录写入权限
3. 磁盘空间

## 💡 提示

### 开发技巧

1. **热重载**：修改代码后自动刷新
2. **Vue DevTools**：安装浏览器扩展调试
3. **日志查看**：`tail -f runtime/log/*.log`

### 性能优化

1. 启用 OPcache（PHP）
2. 使用 Redis 缓存
3. 开启 Gzip 压缩
4. 配置 CDN

### 安全建议

1. 修改默认密码
2. 限制后台访问 IP
3. 启用 HTTPS
4. 定期备份数据

## 🆘 获取帮助

遇到问题？

- 📖 查看文档：[README.md](README.md)
- 🐛 提交 Issue：[GitHub Issues](https://github.com/your-repo/issues)
- 💬 加入讨论：[Discussions](https://github.com/your-repo/discussions)

---

**祝你使用愉快！** 🎵

# 🚀 部署指南

本文档提供详细的生产环境部署步骤。

## 📋 部署前准备

### 服务器要求

- 操作系统：Linux（推荐 Ubuntu 20.04+ 或 CentOS 7+）
- PHP：7.4+ 或 8.0+
- MySQL：5.7+ 或 8.0+
- Nginx：1.18+ 或 Apache 2.4+
- Node.js：16.0+
- 内存：至少 2GB
- 磁盘：根据音乐文件数量，建议至少 20GB

### 安装必要软件

#### Ubuntu/Debian

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装 PHP 和扩展
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring \
    php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-redis

# 安装 MySQL
sudo apt install -y mysql-server

# 安装 Nginx
sudo apt install -y nginx

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

#### CentOS/RHEL

```bash
# 安装 PHP
sudo yum install -y epel-release
sudo yum install -y php php-fpm php-mysql php-mbstring \
    php-xml php-curl php-zip php-gd php-redis

# 安装 MySQL
sudo yum install -y mysql-server

# 安装 Nginx
sudo yum install -y nginx

# 安装 Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 安装 Node.js
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs
```

## 🔧 后端部署

### 1. 上传代码

```bash
# 创建项目目录
sudo mkdir -p /var/www/music-player
cd /var/www/music-player

# 上传你的 ThinkPHP + EasyAdmin 项目
# 可以使用 git clone、scp 或 FTP 等方式
```

### 2. 安装依赖

```bash
# 安装 Composer 依赖
composer install --no-dev --optimize-autoloader

# 设置权限
sudo chown -R www-data:www-data /var/www/music-player
sudo chmod -R 755 /var/www/music-player
sudo chmod -R 777 /var/www/music-player/runtime
```

### 3. 配置数据库

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE music_player CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 创建用户并授权
CREATE USER 'music_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON music_player.* TO 'music_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

编辑 `.env` 文件：

```ini
[DATABASE]
TYPE = mysql
HOSTNAME = 127.0.0.1
DATABASE = music_player
USERNAME = music_user
PASSWORD = your_password
HOSTPORT = 3306
CHARSET = utf8mb4
```

### 4. 导入数据库

```bash
# 执行迁移
php think migrate:run

# 或直接导入 SQL
mysql -u music_user -p music_player < database/music.sql
```

### 5. 创建音乐目录

```bash
# 创建音乐存储目录
sudo mkdir -p /var/www/music-player/public/wwwroot/alist/music
sudo chown -R www-data:www-data /var/www/music-player/public/wwwroot
sudo chmod -R 755 /var/www/music-player/public/wwwroot
```

### 6. 配置 Nginx

创建 Nginx 配置文件 `/etc/nginx/sites-available/music-player`：

```nginx
server {
    listen 80;
    server_name api.yourdomain.com;
    root /var/www/music-player/public;
    index index.php index.html;

    # 日志
    access_log /var/log/nginx/music-player-access.log;
    error_log /var/log/nginx/music-player-error.log;

    # 主配置
    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
            break;
        }
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 音乐文件
    location /wwwroot/ {
        alias /var/www/music-player/public/wwwroot/;
        add_header Access-Control-Allow-Origin *;
        add_header Cache-Control "public, max-age=31536000";
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }
}
```

启用站点：

```bash
sudo ln -s /etc/nginx/sites-available/music-player /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

## 🎨 前端部署

### 1. 构建前端

```bash
cd frontend

# 安装依赖
npm install

# 修改 API 地址（vite.config.js）
# 将 target 改为你的后端域名

# 构建生产版本
npm run build
```

### 2. 部署前端文件

#### 方式1：部署到独立域名

创建 Nginx 配置 `/etc/nginx/sites-available/music-player-frontend`：

```nginx
server {
    listen 80;
    server_name music.yourdomain.com;
    root /var/www/music-player-frontend;
    index index.html;

    # 日志
    access_log /var/log/nginx/music-frontend-access.log;
    error_log /var/log/nginx/music-frontend-error.log;

    # 前端路由
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 代理
    location /api/ {
        proxy_pass http://api.yourdomain.com/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 音乐文件代理
    location /wwwroot/ {
        proxy_pass http://api.yourdomain.com/wwwroot/;
        add_header Access-Control-Allow-Origin *;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, immutable";
    }

    # Gzip 压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
}
```

部署文件：

```bash
sudo mkdir -p /var/www/music-player-frontend
sudo cp -r dist/* /var/www/music-player-frontend/
sudo chown -R www-data:www-data /var/www/music-player-frontend
```

#### 方式2：部署到后端项目

```bash
sudo cp -r dist/* /var/www/music-player/public/h5/
```

### 3. 启用 HTTPS（推荐）

使用 Let's Encrypt 免费证书：

```bash
# 安装 Certbot
sudo apt install -y certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d music.yourdomain.com -d api.yourdomain.com

# 自动续期
sudo certbot renew --dry-run
```

## 🔒 安全加固

### 1. 配置防火墙

```bash
# 安装 UFW
sudo apt install -y ufw

# 允许必要端口
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# 启用防火墙
sudo ufw enable
```

### 2. 配置 PHP 安全

编辑 `/etc/php/8.1/fpm/php.ini`：

```ini
expose_php = Off
display_errors = Off
log_errors = On
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
```

### 3. 数据库安全

```bash
# 运行 MySQL 安全脚本
sudo mysql_secure_installation
```

### 4. 定期备份

创建备份脚本 `/root/backup-music.sh`：

```bash
#!/bin/bash

# 配置
BACKUP_DIR="/backup/music-player"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="music_player"
DB_USER="music_user"
DB_PASS="your_password"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# 备份音乐文件（可选）
tar -czf $BACKUP_DIR/music_$DATE.tar.gz /var/www/music-player/public/wwwroot/alist/music/

# 删除 7 天前的备份
find $BACKUP_DIR -name "*.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

设置定时任务：

```bash
chmod +x /root/backup-music.sh
crontab -e

# 每天凌晨 2 点备份
0 2 * * * /root/backup-music.sh >> /var/log/backup-music.log 2>&1
```

## 📊 监控和维护

### 1. 日志监控

```bash
# 查看 Nginx 日志
tail -f /var/log/nginx/music-player-access.log
tail -f /var/log/nginx/music-player-error.log

# 查看 PHP 日志
tail -f /var/log/php8.1-fpm.log

# 查看应用日志
tail -f /var/www/music-player/runtime/log/*.log
```

### 2. 性能优化

```bash
# 启用 OPcache
# 编辑 /etc/php/8.1/fpm/php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.revalidate_freq=60

# 重启 PHP-FPM
sudo systemctl restart php8.1-fpm
```

### 3. 清理缓存

```bash
# 清理应用缓存
cd /var/www/music-player
php think clear

# 清理 Nginx 缓存（如果配置了）
sudo rm -rf /var/cache/nginx/*
sudo systemctl reload nginx
```

## ✅ 部署检查清单

- [ ] 服务器环境配置完成
- [ ] 后端代码上传并安装依赖
- [ ] 数据库创建并导入
- [ ] 音乐目录创建并设置权限
- [ ] Nginx 配置正确
- [ ] 前端构建并部署
- [ ] HTTPS 证书配置（推荐）
- [ ] 防火墙规则设置
- [ ] 备份脚本配置
- [ ] 测试所有功能正常

## 🎉 完成

部署完成后，访问你的域名即可使用音乐播放器！

如有问题，请查看日志文件或提交 Issue。

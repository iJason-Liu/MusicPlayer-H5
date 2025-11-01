#!/bin/bash

# 音乐播放器数据库初始化脚本

# 数据库配置（从 .env 文件读取）
DB_HOST="127.0.0.1"
DB_PORT="3306"
DB_NAME="music"
DB_USER="music"
DB_PASS="Kzic52W5Jc7LwhSz"

echo "=========================================="
echo "音乐播放器数据库初始化"
echo "=========================================="
echo ""

# 检查 MySQL 是否可用
if ! command -v mysql &> /dev/null; then
    echo "错误: 未找到 MySQL 命令"
    exit 1
fi

echo "正在连接数据库..."

# 导入数据库结构
mysql -h${DB_HOST} -P${DB_PORT} -u${DB_USER} -p${DB_PASS} ${DB_NAME} < music.sql

if [ $? -eq 0 ]; then
    echo "✓ 数据库表创建成功"
else
    echo "✗ 数据库表创建失败"
    exit 1
fi

echo ""
echo "=========================================="
echo "初始化完成！"
echo "=========================================="
echo ""
echo "默认用户信息："
echo "  用户名: admin"
echo "  密码: password"
echo ""
echo "数据库信息："
echo "  数据库: ${DB_NAME}"
echo "  主机: ${DB_HOST}:${DB_PORT}"
echo ""

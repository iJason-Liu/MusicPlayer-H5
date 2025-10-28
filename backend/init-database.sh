#!/bin/bash

# 数据库初始化脚本

echo "=========================================="
echo "  H5 音乐播放器 - 数据库初始化"
echo "=========================================="
echo ""

# 检查是否在 backend 目录
if [ ! -f "think" ]; then
    echo "错误: 请在 backend 目录下运行此脚本"
    exit 1
fi

# 1. 运行数据库迁移
echo "1. 运行数据库迁移..."
php think migrate:run

if [ $? -ne 0 ]; then
    echo "错误: 数据库迁移失败"
    exit 1
fi

echo "✓ 数据库迁移完成"
echo ""

# 2. 填充测试数据
echo "2. 填充测试数据..."
php think seed:run

if [ $? -ne 0 ]; then
    echo "错误: 数据填充失败"
    exit 1
fi

echo "✓ 测试数据填充完成"
echo ""

# 3. 显示测试账号信息
echo "=========================================="
echo "  初始化完成！"
echo "=========================================="
echo ""
echo "测试账号："
echo "  用户名: admin"
echo "  密码: 123456"
echo ""
echo "  用户名: test"
echo "  密码: 123456"
echo ""
echo "API 文档: ../API_DOCUMENTATION.md"
echo "开发指南: ../BACKEND_API_GUIDE.md"
echo ""
echo "启动开发服务器："
echo "  php think run"
echo ""

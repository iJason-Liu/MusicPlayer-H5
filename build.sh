#!/bin/bash

# 音乐播放器构建脚本

echo "🎵 音乐播放器构建脚本"
echo "======================="

# 检查是否在项目根目录
if [ ! -f "README.md" ]; then
    echo "❌ 错误：请在项目根目录运行此脚本"
    exit 1
fi

echo ""
echo "📦 安装前端依赖..."
cd frontend

if [ ! -d "node_modules" ]; then
    npm install
    if [ $? -ne 0 ]; then
        echo "❌ 依赖安装失败"
        exit 1
    fi
fi

echo ""
echo "🔨 构建生产版本..."
npm run build

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ 构建成功！"
    echo "📁 构建文件位于: frontend/dist/"
    echo ""
    echo "📝 部署说明："
    echo "1. 将 dist 目录内容复制到服务器"
    echo "2. 配置 Nginx 或 Apache"
    echo "3. 详细步骤请查看 DEPLOY.md"
else
    echo "❌ 构建失败"
    exit 1
fi

#!/bin/bash

# 音乐播放器快速启动脚本

echo "🎵 音乐播放器启动脚本"
echo "======================="

# 检查是否在项目根目录
if [ ! -f "README.md" ]; then
    echo "❌ 错误：请在项目根目录运行此脚本"
    exit 1
fi

# 检查 Node.js
if ! command -v node &> /dev/null; then
    echo "❌ 错误：未安装 Node.js"
    echo "请访问 https://nodejs.org/ 安装 Node.js"
    exit 1
fi

# 检查 npm
if ! command -v npm &> /dev/null; then
    echo "❌ 错误：未安装 npm"
    exit 1
fi

echo ""
echo "📦 检查前端依赖..."
cd frontend

if [ ! -d "node_modules" ]; then
    echo "📥 安装前端依赖..."
    npm install
    if [ $? -ne 0 ]; then
        echo "❌ 依赖安装失败"
        exit 1
    fi
else
    echo "✅ 依赖已安装"
fi

echo ""
echo "🚀 启动开发服务器..."
echo "前端地址: http://localhost:3000"
echo "按 Ctrl+C 停止服务"
echo ""

npm run dev

#!/bin/bash

echo "=========================================="
echo "  重新构建前端项目"
echo "=========================================="
echo ""

# 检查是否在 frontend 目录
if [ ! -f "package.json" ]; then
    echo "错误: 请在 frontend 目录下运行此脚本"
    exit 1
fi

# 1. 清理旧的构建文件
echo "1. 清理旧的构建文件..."
rm -rf h5
echo "✓ 清理完成"
echo ""

# 2. 构建生产版本
echo "2. 构建生产版本..."
npm run build

if [ $? -ne 0 ]; then
    echo "错误: 构建失败"
    exit 1
fi

echo "✓ 构建完成"
echo ""

# 3. 检查构建输出
echo "3. 检查构建输出..."
if [ -d "h5" ]; then
    echo "✓ h5 目录已创建"
    echo ""
    echo "构建文件列表:"
    ls -lh h5/
    echo ""
    echo "=========================================="
    echo "  构建成功！"
    echo "=========================================="
    echo ""
    echo "下一步："
    echo "1. 将 h5 目录上传到服务器"
    echo "2. 配置 Nginx/Apache"
    echo "3. 访问网站测试"
    echo ""
    echo "调试指南: ../DEPLOYMENT_DEBUG.md"
else
    echo "错误: h5 目录未创建"
    exit 1
fi

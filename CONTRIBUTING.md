# 🤝 贡献指南

感谢你考虑为音乐播放器项目做出贡献！

## 📋 目录

- [行为准则](#行为准则)
- [如何贡献](#如何贡献)
- [开发流程](#开发流程)
- [代码规范](#代码规范)
- [提交规范](#提交规范)
- [问题反馈](#问题反馈)

## 🌟 行为准则

### 我们的承诺

为了营造一个开放和友好的环境，我们承诺：

- 使用友好和包容的语言
- 尊重不同的观点和经验
- 优雅地接受建设性批评
- 关注对社区最有利的事情
- 对其他社区成员表示同理心

## 🎯 如何贡献

### 报告 Bug

发现 Bug？请通过以下步骤报告：

1. 检查 [Issues](https://github.com/your-repo/issues) 确认问题未被报告
2. 创建新 Issue，包含：
   - 清晰的标题
   - 详细的问题描述
   - 复现步骤
   - 预期行为
   - 实际行为
   - 截图（如适用）
   - 环境信息（浏览器、操作系统等）

### 建议新功能

有好的想法？我们很乐意听到！

1. 检查 [Issues](https://github.com/your-repo/issues) 确认功能未被建议
2. 创建新 Issue，标记为 `enhancement`
3. 描述功能的用途和价值
4. 提供使用场景示例

### 提交代码

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'feat: Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 🔧 开发流程

### 1. 环境准备

```bash
# 克隆你的 Fork
git clone https://github.com/your-username/music-player.git
cd music-player

# 添加上游仓库
git remote add upstream https://github.com/original-repo/music-player.git

# 安装前端依赖
cd frontend
npm install
```

### 2. 创建分支

```bash
# 更新主分支
git checkout main
git pull upstream main

# 创建功能分支
git checkout -b feature/your-feature-name
```

### 3. 开发

```bash
# 启动开发服务器
npm run dev

# 在另一个终端运行测试（如果有）
npm run test
```

### 4. 提交

```bash
# 添加更改
git add .

# 提交（遵循提交规范）
git commit -m "feat: your feature description"

# 推送到你的 Fork
git push origin feature/your-feature-name
```

### 5. 创建 Pull Request

1. 访问你的 Fork 页面
2. 点击 "New Pull Request"
3. 填写 PR 模板
4. 等待审核

## 📝 代码规范

### JavaScript/Vue

- 使用 ES6+ 语法
- 使用 2 空格缩进
- 使用单引号
- 组件名使用 PascalCase
- 变量名使用 camelCase
- 常量名使用 UPPER_SNAKE_CASE

**示例：**

```javascript
// ✅ 好的
const musicList = ref([])
const MAX_ITEMS = 100

// ❌ 不好的
const MusicList = ref([])
const maxItems = 100
```

### PHP

- 遵循 PSR-12 编码规范
- 使用 4 空格缩进
- 类名使用 PascalCase
- 方法名使用 camelCase
- 添加必要的注释

**示例：**

```php
// ✅ 好的
class MusicController
{
    public function getMusicList()
    {
        // 实现代码
    }
}

// ❌ 不好的
class music_controller
{
    public function get_music_list()
    {
        // 实现代码
    }
}
```

### CSS/SCSS

- 使用 kebab-case 命名类
- 使用 2 空格缩进
- 避免使用 ID 选择器
- 使用 BEM 命名规范（可选）

**示例：**

```scss
// ✅ 好的
.music-item {
  &__title {
    font-size: 16px;
  }
  
  &--active {
    color: blue;
  }
}

// ❌ 不好的
#musicItem {
  .Title {
    font-size: 16px;
  }
}
```

## 📋 提交规范

使用 [Conventional Commits](https://www.conventionalcommits.org/) 规范：

### 格式

```text
<type>(<scope>): <subject>

<body>

<footer>
```

### Type 类型

- `feat`: 新功能
- `fix`: Bug 修复
- `docs`: 文档更新
- `style`: 代码格式调整（不影响功能）
- `refactor`: 重构（既不是新功能也不是修复）
- `perf`: 性能优化
- `test`: 测试相关
- `chore`: 构建/工具相关
- `revert`: 回滚提交

### 示例

```bash
# 新功能
git commit -m "feat(player): add shuffle mode"

# Bug 修复
git commit -m "fix(api): resolve music loading issue"

# 文档
git commit -m "docs: update deployment guide"

# 重构
git commit -m "refactor(store): simplify music state management"
```

## 🐛 问题反馈

### Bug 报告模板

```markdown
**描述问题**
简要描述遇到的问题。

**复现步骤**
1. 进入 '...'
2. 点击 '...'
3. 滚动到 '...'
4. 看到错误

**预期行为**
描述你期望发生什么。

**实际行为**
描述实际发生了什么。

**截图**
如果适用，添加截图帮助解释问题。

**环境信息**
- 操作系统: [如 macOS 12.0]
- 浏览器: [如 Chrome 95]
- 版本: [如 1.0.0]

**附加信息**
其他相关信息。
```

### 功能请求模板

```markdown
**功能描述**
简要描述你想要的功能。

**问题背景**
这个功能解决什么问题？

**建议方案**
你希望如何实现这个功能？

**替代方案**
你考虑过的其他方案。

**附加信息**
其他相关信息或截图。
```

## ✅ Pull Request 检查清单

提交 PR 前，请确认：

- [ ] 代码遵循项目的代码规范
- [ ] 提交信息遵循提交规范
- [ ] 已添加必要的注释
- [ ] 已更新相关文档
- [ ] 已测试所有更改
- [ ] 没有引入新的警告或错误
- [ ] 已解决所有冲突
- [ ] PR 描述清晰完整

## 🎓 学习资源

### Vue 3

- [Vue 3 官方文档](https://cn.vuejs.org/)
- [Vue Router 文档](https://router.vuejs.org/zh/)
- [Pinia 文档](https://pinia.vuejs.org/zh/)

### ThinkPHP

- [ThinkPHP 6 文档](https://www.kancloud.cn/manual/thinkphp6_0/)
- [EasyAdmin 文档](https://gitee.com/zhongshaofa/easyadmin)

### 其他

- [Vant 4 文档](https://vant-ui.github.io/vant/)
- [Vite 文档](https://cn.vitejs.dev/)

## 💬 交流讨论

- GitHub Discussions: [链接]
- QQ 群: [群号]
- 微信群: [二维码]

## 🙏 致谢

感谢所有为项目做出贡献的开发者！

你的贡献让这个项目变得更好！ ❤️

---

**Happy Coding!** 🎉

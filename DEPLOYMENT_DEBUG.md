# 部署调试指南

## 问题：页面无内容

### 可能的原因

1. **路由配置问题**
   - 生产环境的 base 路径配置不正确
   - 路由模式不匹配服务器配置

2. **API 请求失败**
   - 生产环境 API 地址配置错误
   - 跨域问题
   - 后端接口未正常运行

3. **资源加载失败**
   - 静态资源路径错误
   - CDN 资源加载失败

## 已修复的问题

### 1. 路由配置
✅ 修改 `vite.config.js` 的 base 为相对路径 `./`
✅ 修改路由使用 `createWebHistory(import.meta.env.BASE_URL)`

### 2. API 配置
✅ 创建环境变量文件 `.env.production` 和 `.env.development`
✅ 修改 API 请求使用环境变量配置的 baseURL
✅ 添加 console.log 输出 API 地址便于调试

### 3. 开发模式
✅ 用户 store 中已配置开发模式自动跳过登录验证

## 调试步骤

### 1. 检查浏览器控制台

打开浏览器开发者工具（F12），查看：

#### Console 标签
- 是否有 JavaScript 错误
- API Base URL 是否正确输出
- 是否有网络请求错误

#### Network 标签
- 检查 API 请求是否发送
- 检查请求的 URL 是否正确
- 检查响应状态码和内容
- 检查是否有跨域错误

#### Elements 标签
- 检查 `#app` 元素是否存在
- 检查是否有渲染的 DOM 元素

### 2. 检查后端 API

#### 测试后端接口是否正常

```bash
# 测试音乐列表接口
curl https://diary.crayon.vip/api/music/list

# 或使用浏览器直接访问
https://diary.crayon.vip/api/music/list
```

预期返回：
```json
{
  "code": 1,
  "msg": "success",
  "data": [...],
  "total": 100
}
```

#### 检查跨域配置

后端需要设置 CORS 头：
```
Access-Control-Allow-Origin: *
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
```

已在 `backend/app/common/controller/Api.php` 中配置。

### 3. 检查服务器配置

#### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name your-domain.com;
    
    # 前端静态文件
    location /h5 {
        alias /path/to/frontend/h5;
        try_files $uri $uri/ /h5/index.html;
        index index.html;
    }
    
    # 后端 API
    location /api {
        proxy_pass http://127.0.0.1:8000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    }
}
```

#### Apache 配置示例

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/frontend/h5
    
    <Directory /path/to/frontend/h5>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # 支持 HTML5 History 模式
        RewriteEngine On
        RewriteBase /h5/
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /h5/index.html [L]
    </Directory>
    
    # API 代理
    ProxyPass /api http://127.0.0.1:8000/api
    ProxyPassReverse /api http://127.0.0.1:8000/api
</VirtualHost>
```

### 4. 常见问题排查

#### 问题 1: 白屏，控制台无错误
**原因**: 路由配置问题或 Vue 未正确挂载

**解决**:
1. 检查 `index.html` 中是否有 `<div id="app"></div>`
2. 检查 `main.js` 中是否正确执行 `app.mount('#app')`
3. 在 `main.js` 开头添加 `console.log('App starting...')` 确认代码执行

#### 问题 2: 404 错误
**原因**: 路由路径或资源路径配置错误

**解决**:
1. 检查 `vite.config.js` 的 `base` 配置
2. 检查服务器的 `try_files` 配置
3. 确保使用相对路径 `./` 而不是绝对路径

#### 问题 3: API 请求失败
**原因**: API 地址错误或跨域问题

**解决**:
1. 检查 `.env.production` 中的 API 地址
2. 在浏览器控制台查看实际请求的 URL
3. 检查后端 CORS 配置
4. 测试后端接口是否可直接访问

#### 问题 4: 资源加载失败
**原因**: CDN 资源无法访问或路径错误

**解决**:
1. 检查 Font Awesome CDN 是否可访问
2. 考虑使用本地资源替代 CDN
3. 检查网络连接

### 5. 临时调试代码

在 `frontend/src/main.js` 开头添加：

```javascript
console.log('=== App Starting ===')
console.log('Environment:', import.meta.env.MODE)
console.log('Base URL:', import.meta.env.BASE_URL)
console.log('API URL:', import.meta.env.VITE_API_BASE_URL)
```

在 `frontend/src/views/Home.vue` 的 `loadMusicList` 方法中添加：

```javascript
console.log('Loading music list...')
console.log('Response:', res)
```

### 6. 重新构建和部署

```bash
# 1. 清理旧的构建文件
cd frontend
rm -rf h5

# 2. 重新安装依赖（可选）
npm install

# 3. 构建生产版本
npm run build

# 4. 检查构建输出
ls -la h5/

# 5. 上传到服务器
# 使用 FTP/SFTP 或其他方式上传 h5 目录

# 6. 重启后端服务（如果需要）
cd ../backend
php think run
```

## 快速检查清单

- [ ] 浏览器控制台是否有错误
- [ ] Network 标签中 API 请求是否发送
- [ ] API 请求的 URL 是否正确
- [ ] 后端接口是否可以直接访问
- [ ] 是否有跨域错误
- [ ] 静态资源是否正确加载
- [ ] 路由是否正确配置
- [ ] 环境变量是否正确设置

## 联系信息

如果问题仍未解决，请提供：
1. 浏览器控制台的完整错误信息
2. Network 标签中的请求详情
3. 服务器配置文件
4. 后端日志

## 相关文件

- `frontend/vite.config.js` - Vite 配置
- `frontend/src/router/index.js` - 路由配置
- `frontend/src/api/music.js` - API 配置
- `frontend/.env.production` - 生产环境变量
- `backend/app/common/controller/Api.php` - API 基类（CORS 配置）

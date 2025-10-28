# API 接口文档

## 基础信息

- 基础路径: `/api`
- 返回格式: JSON
- 字符编码: UTF-8

### 通用返回格式

```json
{
  "code": 1,        // 1:成功 0:失败
  "msg": "success", // 提示信息
  "data": {}        // 返回数据
}
```

### 请求头

需要登录的接口需要在请求头中携带 token：

```
Authorization: {token}
```

---

## 1. 用户接口

### 1.1 用户登录

**接口地址**: `POST /api/user/login`

**请求参数**:
```json
{
  "username": "admin",
  "password": "123456"
}
```

**返回示例**:
```json
{
  "code": 1,
  "msg": "登录成功",
  "data": {
    "token": "abc123...",
    "user": {
      "id": 1,
      "username": "admin",
      "nickname": "管理员",
      "avatar": ""
    }
  }
}
```

### 1.2 获取用户信息

**接口地址**: `GET /api/user/info`

**需要登录**: 是

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "username": "admin",
    "nickname": "管理员",
    "avatar": "",
    "stats": {
      "play_count": 100,
      "favorite_count": 20,
      "playlist_count": 5
    }
  }
}
```

### 1.3 统计信息

**接口地址**: `GET /api/user/statistics`

**需要登录**: 是

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "total_music": 500,
    "total_duration": 90000,
    "play_count": 100,
    "favorite_count": 20,
    "playlist_count": 5,
    "total_play_duration": 36000
  }
}
```

### 1.4 更新用户信息

**接口地址**: `POST /api/user/update`

**需要登录**: 是

**请求参数**:
```json
{
  "nickname": "新昵称",
  "avatar": "头像URL"
}
```

### 1.5 修改密码

**接口地址**: `POST /api/user/password`

**需要登录**: 是

**请求参数**:
```json
{
  "old_password": "旧密码",
  "new_password": "新密码"
}
```

### 1.6 退出登录

**接口地址**: `POST /api/user/logout`

**需要登录**: 是

---

## 2. 音乐接口

### 2.1 音乐列表

**接口地址**: `GET /api/music/list`

**请求参数**:
- `page`: 页码，默认 1
- `limit`: 每页数量，默认 20
- `keyword`: 搜索关键词（可选）

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "歌曲名",
      "artist": "歌手",
      "album": "专辑",
      "cover": "封面URL",
      "duration": 240,
      "url": "音乐文件URL",
      "lyric": "歌词"
    }
  ],
  "total": 100
}
```

### 2.2 搜索音乐

**接口地址**: `GET /api/music/search`

**请求参数**:
- `keyword`: 搜索关键词（必填）

**返回示例**: 同音乐列表

### 2.3 音乐详情

**接口地址**: `GET /api/music/detail`

**请求参数**:
- `id`: 音乐ID（必填）

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "name": "歌曲名",
    "artist": "歌手",
    "album": "专辑",
    "cover": "封面URL",
    "duration": 240,
    "url": "音乐文件URL",
    "lyric": "歌词",
    "is_favorite": true
  }
}
```

### 2.4 推荐音乐

**接口地址**: `GET /api/music/recommend`

**请求参数**:
- `limit`: 数量，默认 10

**返回示例**: 同音乐列表

### 2.5 热门音乐

**接口地址**: `GET /api/music/hot`

**请求参数**:
- `limit`: 数量，默认 20

**返回示例**: 同音乐列表，额外包含 `play_count` 字段

---

## 3. 播放历史接口

### 3.1 播放历史列表

**接口地址**: `GET /api/history/list`

**需要登录**: 是

**请求参数**:
- `page`: 页码，默认 1
- `limit`: 每页数量，默认 20

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "歌曲名",
      "artist": "歌手",
      "last_play_time": 1634567890,
      "play_count": 5
    }
  ],
  "total": 50
}
```

### 3.2 添加播放记录

**接口地址**: `POST /api/history/add`

**需要登录**: 是

**请求参数**:
```json
{
  "music_id": 1,
  "duration": 240
}
```

### 3.3 删除播放记录

**接口地址**: `POST /api/history/delete`

**需要登录**: 是

**请求参数**:
```json
{
  "music_id": 1
}
```

### 3.4 清空播放历史

**接口地址**: `POST /api/history/clear`

**需要登录**: 是

---

## 4. 收藏接口

### 4.1 收藏列表

**接口地址**: `GET /api/favorite/list`

**需要登录**: 是

**请求参数**:
- `page`: 页码，默认 1
- `limit`: 每页数量，默认 20

**返回示例**: 同音乐列表，额外包含 `favorite_time` 字段

### 4.2 添加收藏

**接口地址**: `POST /api/favorite/add`

**需要登录**: 是

**请求参数**:
```json
{
  "music_id": 1
}
```

### 4.3 取消收藏

**接口地址**: `POST /api/favorite/remove`

**需要登录**: 是

**请求参数**:
```json
{
  "music_id": 1
}
```

### 4.4 检查是否收藏

**接口地址**: `GET /api/favorite/check`

**需要登录**: 是

**请求参数**:
- `music_id`: 音乐ID

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "is_favorite": true
  }
}
```

---

## 5. 播放列表接口

### 5.1 播放列表

**接口地址**: `GET /api/playlist/list`

**需要登录**: 是

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": [
    {
      "id": 1,
      "name": "我的歌单",
      "cover": "封面URL",
      "description": "描述",
      "music_count": 10,
      "create_time": 1634567890
    }
  ]
}
```

### 5.2 创建播放列表

**接口地址**: `POST /api/playlist/create`

**需要登录**: 是

**请求参数**:
```json
{
  "name": "我的歌单",
  "cover": "封面URL",
  "description": "描述"
}
```

### 5.3 更新播放列表

**接口地址**: `POST /api/playlist/update`

**需要登录**: 是

**请求参数**:
```json
{
  "id": 1,
  "name": "新名称",
  "cover": "新封面",
  "description": "新描述"
}
```

### 5.4 删除播放列表

**接口地址**: `POST /api/playlist/delete`

**需要登录**: 是

**请求参数**:
```json
{
  "id": 1
}
```

### 5.5 播放列表详情

**接口地址**: `GET /api/playlist/detail`

**需要登录**: 是

**请求参数**:
- `id`: 播放列表ID

**返回示例**:
```json
{
  "code": 1,
  "msg": "success",
  "data": {
    "id": 1,
    "name": "我的歌单",
    "cover": "封面URL",
    "description": "描述",
    "music_count": 10,
    "music_list": [
      {
        "id": 1,
        "name": "歌曲名",
        "artist": "歌手",
        "url": "音乐URL"
      }
    ]
  }
}
```

### 5.6 添加音乐到播放列表

**接口地址**: `POST /api/playlist/add-music`

**需要登录**: 是

**请求参数**:
```json
{
  "playlist_id": 1,
  "music_id": 1
}
```

### 5.7 从播放列表移除音乐

**接口地址**: `POST /api/playlist/remove-music`

**需要登录**: 是

**请求参数**:
```json
{
  "playlist_id": 1,
  "music_id": 1
}
```

---

## 错误码说明

- `code = 1`: 成功
- `code = 0`: 失败

常见错误信息：
- "参数错误"
- "用户不存在"
- "密码错误"
- "账号已被禁用"
- "音乐不存在"
- "播放列表不存在"
- "已经收藏过了"
- "该音乐已在列表中"

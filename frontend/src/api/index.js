import axios from 'axios'
import { showToast } from 'vant'
import router from '@/router'

// 根据环境设置 API 基础路径
const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

console.log('API Base URL:', baseURL)

const request = axios.create({
  baseURL,
  timeout: 10000
})

// 请求拦截器 - 添加 token
request.interceptors.request.use(
  config => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers['Authorization'] = token
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

// 响应拦截器
request.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code === 401) {
      showToast(res.msg || 'Error')
      localStorage.clear() // 清除本地存储
      router.replace('/login') // 重定向到登录页面
    } else if (res.code !== 1) {
      console.error(res.msg || 'Error')
      return Promise.reject(new Error(res.msg || 'Error'))
    }
    return res
  },
  error => {
    console.error('请求错误：' + error.message)
    return Promise.reject(error)
  }
)

// ==================== 用户相关 ====================
// 登录
export const login = (username, password) => {
  return request.post('/user/login', { username, password })
}

// 获取用户信息
export const getUserInfo = () => {
  return request.get('/user/info')
}

// 获取用户ID
export const getUserId = () => {
  return request.get('/user/id')
}

// 获取统计信息
export const getStatistics = () => {
  return request.get('/user/statistics')
}

// 更新用户信息
export const updateUserInfo = (data) => {
  return request.post('/user/update', data)
}

// 修改密码
export const changePassword = (data) => {
  return request.post('/user/password', data)
}

// 退出登录
export const logout = () => {
  return request.post('/user/logout')
}

// ==================== 音乐相关 ====================

// 获取音乐列表（不分页）
export const getMusicList = (params = {}) => {
  return request.get('/music/list', { params })
}

// 获取音乐列表（分页）
export const getMusicListPage = (params = {}) => {
  return request.get('/music/index', { params })
}

// 搜索音乐
export const searchMusic = (keyword) => {
  return request.get('/music/search', { params: { keyword } })
}

// 获取音乐详情
export const getMusicDetail = (id) => {
  return request.get('/music/detail', { params: { id } })
}

// 获取推荐音乐
export const getRecommendMusic = (limit = 10) => {
  return request.get('/music/recommend', { params: { limit } })
}

// 获取热门音乐
export const getHotMusic = (limit = 20) => {
  return request.get('/music/hot', { params: { limit } })
}

// ==================== 播放历史相关 ====================

// 获取播放历史
export const getPlayHistory = (params = {}) => {
  return request.get('/history/list', { params })
}

// 添加播放记录
export const addPlayHistory = (musicId, duration = 0) => {
  return request.post('/history/add', { music_id: musicId, duration })
}

// 删除播放历史
export const deletePlayHistory = (musicId) => {
  return request.post('/history/delete', { music_id: musicId })
}

// 清空播放历史
export const clearPlayHistory = () => {
  return request.post('/history/clear')
}

// ==================== 收藏相关 ====================

// 获取收藏列表
export const getFavoriteList = (params = {}) => {
  return request.get('/favorite/list', { params })
}

// 添加收藏
export const addFavorite = (musicId) => {
  return request.post('/favorite/add', { music_id: musicId })
}

// 取消收藏
export const removeFavorite = (musicId) => {
  return request.post('/favorite/remove', { music_id: musicId })
}

// 检查是否收藏
export const checkFavorite = (musicId) => {
  return request.get('/favorite/check', { params: { music_id: musicId } })
}

// ==================== 播放列表相关 ====================

// 获取播放列表
export const getPlaylistList = () => {
  return request.get('/playlist/list')
}

// 创建播放列表
export const createPlaylist = (data) => {
  return request.post('/playlist/create', data)
}

// 更新播放列表
export const updatePlaylist = (data) => {
  return request.post('/playlist/update', data)
}

// 删除播放列表
export const deletePlaylist = (id) => {
  return request.post('/playlist/delete', { id })
}

// 获取播放列表详情
export const getPlaylistDetail = (id) => {
  return request.get('/playlist/detail', { params: { id } })
}

// 添加音乐到播放列表
export const addMusicToPlaylist = (playlistId, musicId) => {
  return request.post('/playlist/addMusic', { playlist_id: playlistId, music_id: musicId })
}

// 从播放列表移除音乐
export const removeMusicFromPlaylist = (playlistId, musicId) => {
  return request.post('/playlist/removeMusic', { playlist_id: playlistId, music_id: musicId })
}

// 获取当前播放队列
export const getPlayQueue = () => {
  return request.get('/playlist/getQueue')
}

// 保存当前播放队列
export const savePlayQueue = (musicIds) => {
  return request.post('/playlist/saveQueue', { music_ids: musicIds })
}

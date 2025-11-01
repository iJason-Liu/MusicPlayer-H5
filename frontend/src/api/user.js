/**
 * 用户相关接口
 */
import axios from 'axios'

// 根据环境设置 API 基础路径
const baseURL = import.meta.env.VITE_API_BASE_URL || '/api'

console.log('API Base URL:', baseURL)

const request = axios.create({
  baseURL,
  timeout: 10000
})

request.interceptors.response.use(
  response => {
    const res = response.data
    if (res.code !== 1) {
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

// 获取用户统计信息
export const getUserStatistics = () => {
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
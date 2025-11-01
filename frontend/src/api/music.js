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

// 获取音乐列表
export const getMusicList = (params = {}) => {
  return request.get('/music/list', { params })
}

export const searchMusic = (keyword) => {
  return request.get('/music/search', { params: { keyword } })
}

export const getMusicDetail = (id) => {
  return request.get('/music/detail', { params: { id } })
}

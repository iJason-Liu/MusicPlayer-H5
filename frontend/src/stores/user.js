import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useUserStore = defineStore('user', () => {
  // 开发模式标志 - 设置为 true 可跳过登录验证
  const DEV_MODE = import.meta.env.DEV // 自动检测开发环境
  
  // 用户信息
  const userInfo = ref(null)
  const token = ref(localStorage.getItem('token') || '')
  
  // 是否已登录
  const isLoggedIn = ref(!!token.value)
  
  // 登录
  const login = (username, password) => {
    return new Promise((resolve, reject) => {
      // 模拟登录请求
      setTimeout(() => {
        if (username && password) {
          const mockToken = 'mock_token_' + Date.now()
          const mockUser = {
            id: 1,
            username: username,
            nickname: username,
            avatar: 'https://via.placeholder.com/100'
          }
          
          token.value = mockToken
          userInfo.value = mockUser
          isLoggedIn.value = true
          
          // 保存到本地存储
          localStorage.setItem('token', mockToken)
          localStorage.setItem('userInfo', JSON.stringify(mockUser))
          
          resolve({ code: 1, msg: '登录成功', data: mockUser })
        } else {
          reject({ code: 0, msg: '用户名或密码不能为空' })
        }
      }, 500)
    })
  }
  
  // 退出登录
  const logout = () => {
    token.value = ''
    userInfo.value = null
    isLoggedIn.value = false
    
    localStorage.removeItem('token')
    localStorage.removeItem('userInfo')
  }
  
  // 初始化用户信息
  const initUserInfo = () => {
    const savedUserInfo = localStorage.getItem('userInfo')
    if (savedUserInfo && token.value) {
      try {
        userInfo.value = JSON.parse(savedUserInfo)
        isLoggedIn.value = true
      } catch (e) {
        console.error('解析用户信息失败', e)
        logout()
      }
    }
  }
  
  // 检查是否需要登录
  const needLogin = () => {
    // 开发模式下可以跳过登录
    if (DEV_MODE) {
      console.log('开发模式：跳过登录验证')
      return false
    }
    return !isLoggedIn.value
  }
  
  // 初始化
  initUserInfo()
  
  return {
    DEV_MODE,
    userInfo,
    token,
    isLoggedIn,
    login,
    logout,
    needLogin
  }
})

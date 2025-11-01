import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login } from '@/api/user'

export const useUserStore = defineStore('user', () => {
  // 开发模式标志 - 设置为 true 可跳过登录验证
  const DEV_MODE = import.meta.env.DEV // 自动检测开发环境
  
  // 用户信息
  const userInfo = ref(null)
  const token = ref(localStorage.getItem('token') || '')
  
  // 是否已登录
  const isLoggedIn = ref(!!token.value)
  
  // 登录
  const loginUser = (username, password) => {
    return new Promise((resolve, reject) => {
      // 请求接口
      login(username, password).then(response => {
        resolve(response);
        if (response.code === 1) {
          token.value = response.data.token;
          userInfo.value = response.data.user;
          isLoggedIn.value = true;
        } else {
          reject(response.msg);
        }
      }).catch(error => {
        reject(error)
      })
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
    // console.log(isLoggedIn.value);
    return !isLoggedIn.value // 不允许未登录访问，如需强制登录改为 true
  }
  
  // 初始化
  initUserInfo()
  
  return {
    DEV_MODE,
    userInfo,
    token,
    isLoggedIn,
    loginUser,
    logout,
    needLogin
  }
})

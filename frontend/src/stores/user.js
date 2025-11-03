import { defineStore } from 'pinia'
import { ref } from 'vue'
import { login, logout, getUserInfo, getStatistics } from '@/api'
import router from '@/router'
import { showToast } from 'vant'

export const useUserStore = defineStore('user', () => {
  // 状态
  const token = ref(localStorage.getItem('token') || '')
  const userInfo = ref(null)
  const statistics = ref({
    total_music: 0,
    total_duration: 0,
    play_count: 0,
    favorite_count: 0,
    playlist_count: 0,
    total_play_duration: 0
  })
  const isLoggedIn = ref(!!token.value)
  
  // 登录
  const userLogin = async (username, password) => {
    try {
      const res = await login(username, password)
      if (res.code === 1) {
        showToast(res.msg || '登录成功')
        token.value = res.data.token
        userInfo.value = res.data.user
        isLoggedIn.value = true
        
        // 保存到本地存储
        localStorage.setItem('token', res.data.token)
        localStorage.setItem('userInfo', JSON.stringify(res.data.user))

        // 登录成功后加载用户数据
        await Promise.all([
          loadUserInfo(),
          loadStatistics()
        ])
        
        // 通知其他 store 重新加载数据
        // 使用动态导入避免循环依赖
        import('@/stores/music').then(({ useMusicStore }) => {
          const musicStore = useMusicStore()
          musicStore.reloadUserData()
        })
        
        import('@/stores/playlist').then(({ usePlaylistStore }) => {
          const playlistStore = usePlaylistStore()
          playlistStore.loadPlaylists()
        })

        router.push("/home"); // 跳转到首页

        return true
      } else {
        showToast(res.msg || 'Error')
        return false
      }
    } catch (error) {
      // console.error('登录失败:', error)
      showToast(error.message || 'Error')
      return false
    }
  }
  
  // 退出登录
  const userLogout = async () => {
    try {
      await logout()
    } catch (error) {
      console.error('退出登录失败:', error)
    } finally {
      token.value = ''
      userInfo.value = null
      isLoggedIn.value = false
      statistics.value = {
        total_music: 0,
        total_duration: 0,
        play_count: 0,
        favorite_count: 0,
        playlist_count: 0,
        total_play_duration: 0
      }
      
      // 清除本地存储
      localStorage.clear()
    }
  }
  
  // 获取用户信息
  const loadUserInfo = async () => {
    try {
      const res = await getUserInfo()
      if (res.data) {
        userInfo.value = res.data
        localStorage.setItem('userInfo', JSON.stringify(res.data))
      }
    } catch (error) {
      console.error('获取用户信息失败:', error)
      // 如果获取失败，可能是 token 过期，清除登录状态
      if (error.response?.status === 401) {
        userLogout()
      }
    }
  }
  
  // 获取统计信息
  const loadStatistics = async () => {
    try {
      const res = await getStatistics()
      if (res.data) {
        statistics.value = res.data
      }
    } catch (error) {
      console.error('获取统计信息失败:', error)
    }
  }
  
  // 初始化
  const initStore = async () => {
    // 从本地存储恢复用户信息
    const savedUserInfo = localStorage.getItem('userInfo')
    if (savedUserInfo) {
      try {
        userInfo.value = JSON.parse(savedUserInfo)
      } catch (e) {
        console.error('解析用户信息失败:', e)
      }
    }
    
    // 如果已登录，加载最新数据
    if (isLoggedIn.value) {
      await Promise.all([
        loadUserInfo(),
        loadStatistics()
      ])
    }
  }
  
  // 初始化
  initStore()
  
  return {
    token,
    userInfo,
    statistics,
    isLoggedIn,
    userLogin,
    userLogout,
    loadUserInfo,
    loadStatistics
  }
})

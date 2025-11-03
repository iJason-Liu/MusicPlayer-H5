import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  getPlaylistList,
  createPlaylist,
  updatePlaylist,
  deletePlaylist,
  getPlaylistDetail,
  addMusicToPlaylist,
  removeMusicFromPlaylist
} from '@/api'

export const usePlaylistStore = defineStore('playlist', () => {
  // 状态
  const playlists = ref([])
  const currentPlaylist = ref(null)
  const loading = ref(false)

  // 从缓存中获取当前播放列表数量
  const playlistCount = computed(() => {
    const playlist = JSON.parse(localStorage.getItem('playlist') || '[]')
    return playlist.length
  })
  
  // 加载播放列表
  const loadPlaylists = async () => {
    loading.value = true
    try {
      const res = await getPlaylistList()
      if (res.data && res.data.list) {
        playlists.value = res.data.list
      } else if (res.data && Array.isArray(res.data)) {
        // 兼容旧格式
        playlists.value = res.data
      }
    } catch (error) {
      console.error('加载播放列表失败:', error)
    } finally {
      loading.value = false
    }
  }
  
  // 创建播放列表
  const create = async (name, cover = '', description = '') => {
    try {
      const res = await createPlaylist({ name, cover, description })
      if (res.data) {
        // 重新加载列表
        await loadPlaylists()
        return res.data.id
      }
      return null
    } catch (error) {
      console.error('创建播放列表失败:', error)
      return null
    }
  }
  
  // 更新播放列表
  const update = async (id, data) => {
    try {
      await updatePlaylist({ id, ...data })
      // 重新加载列表
      await loadPlaylists()
      return true
    } catch (error) {
      console.error('更新播放列表失败:', error)
      return false
    }
  }
  
  // 删除播放列表
  const remove = async (id) => {
    try {
      await deletePlaylist(id)
      // 从本地状态中移除
      playlists.value = playlists.value.filter(p => p.id !== id)
      if (currentPlaylist.value?.id === id) {
        currentPlaylist.value = null
      }
      return true
    } catch (error) {
      console.error('删除播放列表失败:', error)
      return false
    }
  }
  
  // 获取播放列表详情
  const loadPlaylistDetail = async (id) => {
    loading.value = true
    try {
      const res = await getPlaylistDetail(id)
      if (res.data) {
        currentPlaylist.value = res.data
        return res.data
      }
      return null
    } catch (error) {
      console.error('获取播放列表详情失败:', error)
      return null
    } finally {
      loading.value = false
    }
  }
  
  // 添加音乐到播放列表
  const addMusic = async (playlistId, musicId) => {
    try {
      await addMusicToPlaylist(playlistId, musicId)
      // 如果当前正在查看这个列表，重新加载详情
      if (currentPlaylist.value?.id === playlistId) {
        await loadPlaylistDetail(playlistId)
      }
      // 重新加载列表以更新音乐数量
      await loadPlaylists()
      return true
    } catch (error) {
      console.error('添加音乐到播放列表失败:', error)
      return false
    }
  }
  
  // 从播放列表移除音乐
  const removeMusic = async (playlistId, musicId) => {
    try {
      await removeMusicFromPlaylist(playlistId, musicId)
      // 如果当前正在查看这个列表，重新加载详情
      if (currentPlaylist.value?.id === playlistId) {
        await loadPlaylistDetail(playlistId)
      }
      // 重新加载列表以更新音乐数量
      await loadPlaylists()
      return true
    } catch (error) {
      console.error('从播放列表移除音乐失败:', error)
      return false
    }
  }
  
  // 初始化
  const initStore = async () => {
    // 只在已登录时加载播放列表
    const token = localStorage.getItem('token')
    if (token) {
      await loadPlaylists()
    }
  }
  
  return {
    playlists,
    currentPlaylist,
    loading,
    loadPlaylists,
    create,
    update,
    remove,
    loadPlaylistDetail,
    addMusic,
    removeMusic,
    initStore,
    playlistCount
  }
})

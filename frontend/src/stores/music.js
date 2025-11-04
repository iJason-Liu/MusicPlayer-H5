import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  getPlayHistory,
  addPlayHistory,
  deletePlayHistory,
  clearPlayHistory,
  getFavoriteList,
  addFavorite,
  removeFavorite,
  checkFavorite,
  savePlayQueue
} from '@/api'

export const useMusicStore = defineStore('music', () => {
  // 状态
  const musicList = ref([])
  const currentMusic = ref(null)
  const currentIndex = ref(-1)
  const isPlaying = ref(false)
  const currentTime = ref(0)
  const duration = ref(0)
  const playlist = ref([])
  const playHistory = ref([])
  const favorites = ref([])
  const playMode = ref('loop') // loop, random, single
  
  // 音频对象
  const audio = ref(null)
  
  // 播放时长统计
  const playStartTime = ref(0) // 本次播放开始时间
  const accumulatedDuration = ref(0) // 累计播放时长（秒）
  const lastUpdateTime = ref(0) // 上次更新时间
  const updateTimer = ref(null) // 定时更新定时器
  
  // 计算属性
  const progress = computed(() => {
    return duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0
  })
  
  const hasNext = computed(() => {
    return currentIndex.value < playlist.value.length - 1
  })
  
  const hasPrev = computed(() => {
    return currentIndex.value > 0
  })
  
  // 初始化音频
  const initAudio = () => {
    if (!audio.value) {
      audio.value = new Audio()
      
      // 优化预加载策略：metadata 只加载元数据，减少初始带宽消耗
      audio.value.preload = 'metadata'
      
      // 设置跨域属性，允许从其他域加载音频
      audio.value.crossOrigin = 'anonymous'
      
      audio.value.addEventListener('timeupdate', () => {
        currentTime.value = audio.value.currentTime
      })
      
      audio.value.addEventListener('loadedmetadata', () => {
        duration.value = audio.value.duration
      })
      
      audio.value.addEventListener('ended', () => {
        handleEnded()
      })
      
      audio.value.addEventListener('error', (e) => {
        console.error('音频加载失败', e)
        isPlaying.value = false
        
        // 错误处理：尝试重新加载
        if (currentMusic.value && audio.value.error) {
          const errorCode = audio.value.error.code
          console.error('音频错误代码:', errorCode)
          
          // 如果是网络错误，可以尝试重新加载
          if (errorCode === 2 || errorCode === 4) {
            console.log('网络错误，尝试重新加载...')
            setTimeout(() => {
              if (audio.value && currentMusic.value) {
                audio.value.load()
              }
            }, 2000)
          }
        }
      })
      
      // 监听缓冲进度
      audio.value.addEventListener('progress', () => {
        if (audio.value.buffered.length > 0) {
          const bufferedEnd = audio.value.buffered.end(audio.value.buffered.length - 1)
          const duration = audio.value.duration
          if (duration > 0) {
            const bufferedPercent = (bufferedEnd / duration) * 100
            console.log('缓冲进度:', bufferedPercent.toFixed(2) + '%')
          }
        }
      })
      
      // 监听等待事件（缓冲不足）
      audio.value.addEventListener('waiting', () => {
        console.log('缓冲中...')
      })
      
      // 监听可以播放事件
      audio.value.addEventListener('canplay', () => {
        console.log('可以播放')
      })
      
      // 监听可以流畅播放事件
      audio.value.addEventListener('canplaythrough', () => {
        console.log('可以流畅播放')
      })
      
      audio.value.addEventListener('play', () => {
        isPlaying.value = true
        startPlayDurationTracking()
      })
      
      audio.value.addEventListener('pause', () => {
        isPlaying.value = false
        pausePlayDurationTracking()
      })
      
      // 启用媒体会话 API 支持后台播放和锁屏控制
      if ('mediaSession' in navigator) {
        navigator.mediaSession.setActionHandler('play', () => {
          audio.value?.play()
        })
        
        navigator.mediaSession.setActionHandler('pause', () => {
          audio.value?.pause()
        })
        
        navigator.mediaSession.setActionHandler('previoustrack', () => {
          playPrev()
        })
        
        navigator.mediaSession.setActionHandler('nexttrack', () => {
          playNext()
        })
        
        navigator.mediaSession.setActionHandler('seekto', (details) => {
          if (details.seekTime) {
            seek(details.seekTime)
          }
        })
      }
    }
  }
  
  // 更新媒体会话信息
  const updateMediaSession = (music) => {
    if ('mediaSession' in navigator && music) {
      navigator.mediaSession.metadata = new MediaMetadata({
        title: music.name || '未知歌曲',
        artist: music.artist || '未知艺术家',
        album: music.album || '未知专辑',
        artwork: music.cover ? [
          { src: music.cover, sizes: '96x96', type: 'image/png' },
          { src: music.cover, sizes: '128x128', type: 'image/png' },
          { src: music.cover, sizes: '192x192', type: 'image/png' },
          { src: music.cover, sizes: '256x256', type: 'image/png' },
          { src: music.cover, sizes: '384x384', type: 'image/png' },
          { src: music.cover, sizes: '512x512', type: 'image/png' }
        ] : []
      })
    }
  }
  
  // 设置音乐列表
  const setMusicList = (list) => {
    musicList.value = list
  }
  
  // 开始播放时长追踪
  const startPlayDurationTracking = () => {
    playStartTime.value = Date.now()
    lastUpdateTime.value = Date.now()
    
    // 每30秒更新一次播放时长到服务器
    if (updateTimer.value) {
      clearInterval(updateTimer.value)
    }
    updateTimer.value = setInterval(() => {
      updatePlayDuration()
    }, 30000) // 30秒
  }
  
  // 暂停播放时长追踪
  const pausePlayDurationTracking = () => {
    if (updateTimer.value) {
      clearInterval(updateTimer.value)
      updateTimer.value = null
    }
    // 暂停时立即更新一次
    updatePlayDuration()
  }
  
  // 更新播放时长
  const updatePlayDuration = async () => {
    if (!currentMusic.value || !playStartTime.value) return
    
    const now = Date.now()
    const duration = Math.floor((now - lastUpdateTime.value) / 1000)
    
    if (duration > 0) {
      accumulatedDuration.value += duration
      lastUpdateTime.value = now
      
      // 只有累计时长超过5秒才上报（避免频繁请求）
      if (accumulatedDuration.value >= 5) {
        try {
          await addPlayHistory(currentMusic.value.id, accumulatedDuration.value)
          accumulatedDuration.value = 0 // 重置累计时长
        } catch (error) {
          console.error('更新播放时长失败:', error)
        }
      }
    }
  }
  
  // 重置播放时长追踪
  const resetPlayDurationTracking = () => {
    if (updateTimer.value) {
      clearInterval(updateTimer.value)
      updateTimer.value = null
    }
    
    // 切歌前先更新当前歌曲的播放时长
    if (accumulatedDuration.value > 0 && currentMusic.value) {
      addPlayHistory(currentMusic.value.id, accumulatedDuration.value).catch(err => {
        console.error('保存播放时长失败:', err)
      })
    }
    
    playStartTime.value = 0
    accumulatedDuration.value = 0
    lastUpdateTime.value = 0
  }
  
  // 播放音乐
  const playMusic = async (music, index = -1) => {
    initAudio()
    
    // 切歌前保存上一首的播放时长
    resetPlayDurationTracking()
    
    currentMusic.value = music
    currentIndex.value = index >= 0 ? index : playlist.value.findIndex(m => m.id === music.id)
    
    audio.value.src = music.url
    
    // 保存当前播放信息到本地存储
    localStorage.setItem('currentMusic', JSON.stringify(music))
    localStorage.setItem('currentIndex', currentIndex.value)
    
    try {
      await audio.value.play()
      isPlaying.value = true
      
      // 更新媒体会话信息
      updateMediaSession(music)
    } catch (error) {
      console.error('播放失败:', error)
      isPlaying.value = false
    }
  }
  
  // 暂停/播放
  const togglePlay = () => {
    if (!audio.value || !currentMusic.value) return
    
    if (isPlaying.value) {
      audio.value.pause()
    } else {
      audio.value.play()
    }
  }
  
  // 下一曲
  const playNext = () => {
    if (playMode.value === 'random') {
      const randomIndex = Math.floor(Math.random() * playlist.value.length)
      playMusic(playlist.value[randomIndex], randomIndex)
    } else if (hasNext.value) {
      playMusic(playlist.value[currentIndex.value + 1], currentIndex.value + 1)
    } else if (playMode.value === 'loop') {
      playMusic(playlist.value[0], 0)
    }
  }
  
  // 上一曲（不执行随机策略，始终播放列表中的上一首）
  const playPrev = () => {
    if (hasPrev.value) {
      // 播放列表中的上一首
      playMusic(playlist.value[currentIndex.value - 1], currentIndex.value - 1)
    } else if (playMode.value === 'loop') {
      // 循环模式下，跳到最后一首
      playMusic(playlist.value[playlist.value.length - 1], playlist.value.length - 1)
    }
    // 注意：上一曲不执行随机策略，即使在随机模式下也是顺序播放
  }
  
  // 播放结束处理
  const handleEnded = () => {
    if (playMode.value === 'single') {
      audio.value.currentTime = 0
      audio.value.play()
    } else {
      playNext()
    }
  }
  
  // 跳转到指定时间
  const seek = (time) => {
    if (audio.value) {
      audio.value.currentTime = time
      currentTime.value = time
      
      // 更新媒体会话位置
      if ('mediaSession' in navigator) {
        navigator.mediaSession.setPositionState({
          duration: duration.value,
          playbackRate: audio.value.playbackRate,
          position: time
        })
      }
    }
  }
  
  // 同步播放列表到服务器
  const syncPlaylistToServer = async (list) => {
    try {
      const token = localStorage.getItem('token')
      if (token && list && list.length > 0) {
        const musicIds = list.map(m => m.id)
        await savePlayQueue(musicIds)
      }
    } catch (error) {
      console.error('同步播放列表到服务器失败:', error)
    }
  }
  
  // 设置播放列表
  const setPlaylist = (list) => {
    playlist.value = list
    // 保存到本地存储
    localStorage.setItem('playlist', JSON.stringify(list))
    // 同步到后端
    syncPlaylistToServer(list)
  }
  
  // 添加到播放列表
  const addToPlaylist = (music) => {
    const exists = playlist.value.find(m => m.id === music.id)
    if (!exists) {
      playlist.value.push(music)
      localStorage.setItem('playlist', JSON.stringify(playlist.value))
      syncPlaylistToServer(playlist.value)
    }
  }
  
  // 从播放列表移除
  const removeFromPlaylist = (index) => {
    playlist.value.splice(index, 1)
    if (index < currentIndex.value) {
      currentIndex.value--
    } else if (index === currentIndex.value) {
      if (playlist.value.length > 0) {
        playNext()
      } else {
        currentMusic.value = null
        currentIndex.value = -1
      }
    }
    localStorage.setItem('playlist', JSON.stringify(playlist.value))
    syncPlaylistToServer(playlist.value)
  }
  
  // ==================== 播放历史相关（使用API） ====================
  
  // 加载播放历史
  const loadPlayHistory = async (page = 1, limit = 20) => {
    try {
      const res = await getPlayHistory({ page, limit })
      if (res.data && res.data.list) {
        playHistory.value = res.data.list
      }
    } catch (error) {
      console.error('加载播放历史失败:', error)
      // 降级到本地存储
      const savedHistory = localStorage.getItem('playHistory')
      if (savedHistory) {
        playHistory.value = JSON.parse(savedHistory)
      }
    }
  }
  
  // 添加到播放历史（已废弃，改用 updatePlayDuration）
  const addToHistory = async (music) => {
    // 此方法已被 updatePlayDuration 替代
    // 保留是为了兼容性，但不再主动调用
    try {
      const exists = playHistory.value.findIndex(m => m.id === music.id)
      if (exists >= 0) {
        playHistory.value.splice(exists, 1)
      }
      playHistory.value.unshift({ ...music, playTime: Date.now() })
      if (playHistory.value.length > 100) {
        playHistory.value.pop()
      }
      localStorage.setItem('playHistory', JSON.stringify(playHistory.value))
    } catch (error) {
      console.error('添加播放历史失败:', error)
    }
  }
  
  // 删除播放历史
  const removeFromHistory = async (musicId) => {
    try {
      await deletePlayHistory(musicId)
      playHistory.value = playHistory.value.filter(m => m.id !== musicId)
      localStorage.setItem('playHistory', JSON.stringify(playHistory.value))
    } catch (error) {
      console.error('删除播放历史失败:', error)
    }
  }
  
  // 清空播放历史
  const clearHistory = async () => {
    try {
      await clearPlayHistory()
      playHistory.value = []
      localStorage.removeItem('playHistory')
    } catch (error) {
      console.error('清空播放历史失败:', error)
    }
  }
  
  // ==================== 收藏相关（使用API） ====================
  
  // 加载收藏列表
  const loadFavorites = async (page = 1, limit = 20) => {
    try {
      const res = await getFavoriteList({ page, limit })
      if (res.data && res.data.list) {
        favorites.value = res.data.list
      }
    } catch (error) {
      console.error('加载收藏列表失败:', error)
      // 降级到本地存储
      const savedFavorites = localStorage.getItem('favorites')
      if (savedFavorites) {
        favorites.value = JSON.parse(savedFavorites)
      }
    }
  }
  
  // 收藏/取消收藏
  const toggleFavorite = async (music) => {
    const index = favorites.value.findIndex(m => m.id === music.id)
    
    try {
      if (index >= 0) {
        // 取消收藏
        await removeFavorite(music.id)
        favorites.value.splice(index, 1)
        localStorage.setItem('favorites', JSON.stringify(favorites.value))
        return false
      } else {
        // 添加收藏
        await addFavorite(music.id)
        favorites.value.unshift(music)
        localStorage.setItem('favorites', JSON.stringify(favorites.value))
        return true
      }
    } catch (error) {
      console.error('收藏操作失败:', error)
      // 降级到本地存储
      if (index >= 0) {
        favorites.value.splice(index, 1)
      } else {
        favorites.value.unshift(music)
      }
      localStorage.setItem('favorites', JSON.stringify(favorites.value))
      return index < 0
    }
  }
  
  // 是否已收藏
  const isFavorite = (musicId) => {
    return favorites.value.some(m => m.id === musicId)
  }
  
  // 检查收藏状态（从服务器）
  const checkIsFavorite = async (musicId) => {
    try {
      const res = await checkFavorite(musicId)
      return res.data?.is_favorite || false
    } catch (error) {
      console.error('检查收藏状态失败:', error)
      return isFavorite(musicId)
    }
  }
  
  // ==================== 播放模式 ====================
  
  // 切换播放模式
  const togglePlayMode = () => {
    const modes = ['loop', 'random', 'single']
    const currentModeIndex = modes.indexOf(playMode.value)
    playMode.value = modes[(currentModeIndex + 1) % modes.length]
    localStorage.setItem('playMode', playMode.value)
    return playMode.value
  }
  
  // ==================== 初始化 ====================
  
  // 初始化数据
  const initStore = async () => {
    try {
      // 从本地存储恢复基本数据
      const savedPlaylist = localStorage.getItem('playlist')
      if (savedPlaylist) {
        playlist.value = JSON.parse(savedPlaylist)
      }
      
      const savedMode = localStorage.getItem('playMode')
      if (savedMode) {
        playMode.value = savedMode
      }
      
      // 恢复当前播放的歌曲
      const savedCurrentMusic = localStorage.getItem('currentMusic')
      const savedCurrentIndex = localStorage.getItem('currentIndex')
      if (savedCurrentMusic) {
        try {
          currentMusic.value = JSON.parse(savedCurrentMusic)
          currentIndex.value = savedCurrentIndex ? parseInt(savedCurrentIndex) : -1
          
          // 初始化音频但不自动播放
          initAudio()
          if (currentMusic.value && currentMusic.value.url) {
            audio.value.src = currentMusic.value.url
            updateMediaSession(currentMusic.value)
          }
        } catch (e) {
          console.error('恢复播放状态失败:', e)
        }
      }
      
      // 只在已登录时加载用户数据
      const token = localStorage.getItem('token')
      if (token) {
        await Promise.all([
          loadPlayHistory(),
          loadFavorites()
        ])
      } else {
        // 未登录时从本地存储恢复
        const savedHistory = localStorage.getItem('playHistory')
        if (savedHistory) {
          playHistory.value = JSON.parse(savedHistory)
        }
        
        const savedFavorites = localStorage.getItem('favorites')
        if (savedFavorites) {
          favorites.value = JSON.parse(savedFavorites)
        }
      }
      
      // 监听页面关闭/刷新，保存播放时长
      window.addEventListener('beforeunload', () => {
        if (accumulatedDuration.value > 0 && currentMusic.value) {
          // 使用 sendBeacon 确保数据能发送出去
          const token = localStorage.getItem('token')
          if (token) {
            const data = new FormData()
            data.append('music_id', currentMusic.value.id)
            data.append('duration', accumulatedDuration.value)
            navigator.sendBeacon('/api/history/add', data)
          }
        }
      })
      
      // 监听页面可见性变化（切换标签页）
      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          // 页面隐藏时暂停追踪
          pausePlayDurationTracking()
        } else if (isPlaying.value) {
          // 页面显示且正在播放时恢复追踪
          startPlayDurationTracking()
        }
      })
    } catch (e) {
      console.error('初始化失败:', e)
    }
  }
  
  // 重新加载用户数据（登录后调用）
  const reloadUserData = async () => {
    await Promise.all([
      loadPlayHistory(),
      loadFavorites()
    ])
  }
  
  // 初始化
  initStore()
  
  return {
    // 状态
    musicList,
    currentMusic,
    currentIndex,
    isPlaying,
    currentTime,
    duration,
    progress,
    playlist,
    playHistory,
    favorites,
    playMode,
    hasNext,
    hasPrev,
    
    // 播放控制
    playMusic,
    togglePlay,
    playNext,
    playPrev,
    seek,
    
    // 播放列表
    setMusicList,
    setPlaylist,
    addToPlaylist,
    removeFromPlaylist,
    
    // 播放历史
    loadPlayHistory,
    removeFromHistory,
    clearHistory,
    
    // 收藏
    loadFavorites,
    toggleFavorite,
    isFavorite,
    checkIsFavorite,
    
    // 播放模式
    togglePlayMode,
    
    // 重新加载
    reloadUserData
  }
})

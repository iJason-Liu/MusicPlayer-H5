import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

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
      })
    }
  }
  
  // 设置音乐列表
  const setMusicList = (list) => {
    musicList.value = list
  }
  
  // 播放音乐
  const playMusic = (music, index = -1) => {
    initAudio()
    
    currentMusic.value = music
    currentIndex.value = index >= 0 ? index : playlist.value.findIndex(m => m.id === music.id)
    
    audio.value.src = music.url
    audio.value.play()
    isPlaying.value = true
    
    // 添加到播放历史
    addToHistory(music)
  }
  
  // 暂停/播放
  const togglePlay = () => {
    if (!audio.value || !currentMusic.value) return
    
    if (isPlaying.value) {
      audio.value.pause()
    } else {
      audio.value.play()
    }
    isPlaying.value = !isPlaying.value
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
  
  // 上一曲
  const playPrev = () => {
    if (hasPrev.value) {
      playMusic(playlist.value[currentIndex.value - 1], currentIndex.value - 1)
    } else if (playMode.value === 'loop') {
      playMusic(playlist.value[playlist.value.length - 1], playlist.value.length - 1)
    }
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
    }
  }
  
  // 设置播放列表
  const setPlaylist = (list) => {
    playlist.value = list
    // 保存到本地存储
    localStorage.setItem('playlist', JSON.stringify(list))
  }
  
  // 添加到播放列表
  const addToPlaylist = (music) => {
    const exists = playlist.value.find(m => m.id === music.id)
    if (!exists) {
      playlist.value.push(music)
      localStorage.setItem('playlist', JSON.stringify(playlist.value))
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
  }
  
  // 添加到播放历史
  const addToHistory = (music) => {
    const exists = playHistory.value.findIndex(m => m.id === music.id)
    if (exists >= 0) {
      playHistory.value.splice(exists, 1)
    }
    playHistory.value.unshift({ ...music, playTime: Date.now() })
    if (playHistory.value.length > 100) {
      playHistory.value.pop()
    }
    // 保存到本地存储
    localStorage.setItem('playHistory', JSON.stringify(playHistory.value))
  }
  
  // 收藏/取消收藏
  const toggleFavorite = (music) => {
    const index = favorites.value.findIndex(m => m.id === music.id)
    if (index >= 0) {
      favorites.value.splice(index, 1)
      localStorage.setItem('favorites', JSON.stringify(favorites.value))
      return false
    } else {
      favorites.value.unshift(music)
      localStorage.setItem('favorites', JSON.stringify(favorites.value))
      return true
    }
  }
  
  // 是否已收藏
  const isFavorite = (musicId) => {
    return favorites.value.some(m => m.id === musicId)
  }
  
  // 切换播放模式
  const togglePlayMode = () => {
    const modes = ['loop', 'random', 'single']
    const currentModeIndex = modes.indexOf(playMode.value)
    playMode.value = modes[(currentModeIndex + 1) % modes.length]
    localStorage.setItem('playMode', playMode.value)
    return playMode.value
  }
  
  // 初始化数据（从本地存储恢复）
  const initStore = () => {
    try {
      const savedPlaylist = localStorage.getItem('playlist')
      if (savedPlaylist) {
        playlist.value = JSON.parse(savedPlaylist)
      }
      
      const savedHistory = localStorage.getItem('playHistory')
      if (savedHistory) {
        playHistory.value = JSON.parse(savedHistory)
      }
      
      const savedFavorites = localStorage.getItem('favorites')
      if (savedFavorites) {
        favorites.value = JSON.parse(savedFavorites)
      }
      
      const savedMode = localStorage.getItem('playMode')
      if (savedMode) {
        playMode.value = savedMode
      }
    } catch (e) {
      console.error('Failed to load from localStorage:', e)
    }
  }
  
  // 初始化
  initStore()
  
  return {
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
    setMusicList,
    playMusic,
    togglePlay,
    playNext,
    playPrev,
    seek,
    setPlaylist,
    addToPlaylist,
    removeFromPlaylist,
    toggleFavorite,
    isFavorite,
    togglePlayMode
  }
})

<template>
  <div class="playlist-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">播放列表</h1>
      <i class="fas fa-trash clear-btn" @click="clearPlaylist" v-if="playlist.length > 0"></i>
      <div class="placeholder" v-else></div>
    </div>
    
    <div class="content" ref="contentRef">
      <div v-if="playlist.length === 0" class="empty">
        <i class="fas fa-list-ul"></i>
        <p>播放列表为空</p>
      </div>
      
      <div 
        v-for="(music, index) in playlist" 
        :key="music.id" 
        class="playlist-item"
        :class="{ active: currentMusic?.id === music.id }"
      >
        <div class="left" @click="handlePlay(music, index)">
          <div class="index">{{ index + 1 }}</div>
          <img :src="imgPath + music.cover" class="cover" />
          <div class="info">
            <div class="name">{{ music.name }}</div>
            <div class="artist">{{ music.artist }}</div>
          </div>
        </div>
        <i class="fas fa-times remove-btn" @click="removeFromPlaylist(index)"></i>
      </div>
    </div>
    
    <!-- 定位当前播放歌曲的悬浮按钮 -->
    <div 
      v-if="currentMusic && playlist.length > 0" 
      class="locate-btn" 
      @click="locateCurrentMusic"
    >
      <i class="fas fa-location-arrow"></i>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Playlist'
}
</script>

<script setup>
import { ref, computed, onActivated, nextTick, inject } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getPlayQueue } from '@/api'
import { showConfirmDialog, showToast } from 'vant'
const imgPath = inject('imgPath')
const musicStore = useMusicStore()
const playlist = computed(() => musicStore.playlist)
const currentMusic = computed(() => musicStore.currentMusic)
const contentRef = ref(null)

const handlePlay = (music, index) => {
  musicStore.playMusic(music, index)
}

const removeFromPlaylist = (index) => {
  musicStore.removeFromPlaylist(index)
  showToast('已移除')
}

const clearPlaylist = () => {
  showConfirmDialog({
    title: '清空播放列表',
    message: '确定要清空播放列表吗？',
  }).then(() => {
    musicStore.setPlaylist([])
    showToast('已清空')
  }).catch(() => {})
}

// 从接口加载播放队列
const loadPlayQueue = async () => {
  try {
    const res = await getPlayQueue()
    if (res.data && Array.isArray(res.data)) {
      musicStore.setPlaylist(res.data)
    }
  } catch (error) {
    console.error('加载播放队列失败:', error)
    // 降级到本地存储
    const savedPlaylist = localStorage.getItem('playlist')
    if (savedPlaylist) {
      try {
        musicStore.setPlaylist(JSON.parse(savedPlaylist))
      } catch (e) {
        console.error('解析播放列表失败:', e)
      }
    }
  }
}

// 定位到当前播放的歌曲
const locateCurrentMusic = () => {
  if (!currentMusic.value) {
    showToast('当前没有播放歌曲')
    return
  }
  
  nextTick(() => {
    const playlistItems = document.querySelectorAll('.playlist-item')
    let targetElement = null
    
    playlistItems.forEach(item => {
      if (item.classList.contains('active')) {
        targetElement = item
      }
    })
    
    if (targetElement && contentRef.value) {
      const containerRect = contentRef.value.getBoundingClientRect()
      const targetRect = targetElement.getBoundingClientRect()
      const scrollTop = contentRef.value.scrollTop
      const offset = targetRect.top - containerRect.top + scrollTop - 100
      
      contentRef.value.scrollTo({
        top: offset,
        behavior: 'smooth'
      })
      
      showToast('已定位到当前播放')
    }
  })
}

// 每次页面激活时从接口刷新播放列表
onActivated(() => {
  loadPlayQueue()
})
</script>

<style lang="scss" scoped>
.playlist-page {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  
  .header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 16px;
    
    .back-btn,
    .clear-btn {
      font-size: 20px;
      color: #fff;
      cursor: pointer;
      padding: 10px;
    }
    
    .title {
      font-size: 18px;
      font-weight: 600;
      color: #fff;
    }
    
    .placeholder {
      width: 40px;
    }
  }
  
  .content {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 20px;
    
    &::-webkit-scrollbar {
      width: 4px;
    }
    
    &::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 2px;
    }
    
    .empty {
      text-align: center;
      padding: 80px 20px;
      
      i {
        font-size: 60px;
        color: rgba(255, 255, 255, 0.3);
        margin-bottom: 20px;
      }
      
      p {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.5);
      }
    }
    
    .playlist-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      margin-bottom: 10px;
      transition: all 0.3s;
      
      &.active {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
      }
      
      .left {
        display: flex;
        align-items: center;
        flex: 1;
        cursor: pointer;
        
        .index {
          width: 30px;
          text-align: center;
          font-size: 14px;
          color: rgba(255, 255, 255, 0.6);
          font-weight: 600;
        }
        
        .cover {
          width: 45px;
          height: 45px;
          border-radius: 8px;
          object-fit: cover;
          margin: 0 12px;
        }
        
        .info {
          flex: 1;
          overflow: hidden;
          
          .name {
            font-size: 14px;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 4px;
          }
          
          .artist {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.6);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
          }
        }
      }
      
      .remove-btn {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        padding: 10px;
        
        &:active {
          transform: scale(0.9);
        }
      }
    }
  }
  
  .locate-btn {
    position: fixed;
    right: 20px;
    bottom: 180px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    
    i {
      font-size: 20px;
      color: #fff;
    }
    
    &:active {
      transform: scale(0.9);
      background: rgba(255, 255, 255, 0.3);
    }
  }
}
</style>

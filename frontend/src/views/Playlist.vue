<template>
  <div class="playlist-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">播放列表 ({{ playlist.length }})</h1>
      <div class="header-actions" v-if="playlist.length > 0">
        <i 
          class="fas action-btn" 
          :class="isEditMode ? 'fa-check' : 'fa-edit'" 
          @click="toggleEditMode"
        ></i>
        <i class="fas fa-trash action-btn" @click="clearPlaylist"></i>
      </div>
      <div class="placeholder" v-else></div>
    </div>
    
    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
      class="content"
      ref="contentRef"
    >
      <div v-if="playlist.length === 0" class="empty">
        <i class="fas fa-list-ul"></i>
        <p>播放列表为空</p>
      </div>
      
      <div v-if="isEditMode && playlist.length > 0" class="edit-tip">
        <i class="fas fa-info-circle"></i>
        <span>长按拖动可调整播放顺序</span>
      </div>
      
      <div 
        v-for="(music, index) in playlist" 
        :key="music.id" 
        class="playlist-item"
        :class="{ 
          active: currentMusic?.id === music.id,
          'edit-mode': isEditMode,
          'dragging': dragIndex === index,
          'drag-over': dragOverIndex === index
        }"
        :draggable="isEditMode"
        @dragstart="handleDragStart(index, $event)"
        @dragend="handleDragEnd"
        @dragover="handleDragOver(index, $event)"
        @dragleave="handleDragLeave"
        @drop="handleDrop(index, $event)"
      >
        <i 
          v-if="isEditMode" 
          class="fas fa-grip-vertical drag-handle"
        ></i>
        <div class="left" @click="!isEditMode && handlePlay(music, index)">
          <div class="index">{{ index + 1 }}</div>
          <img :src="getCoverUrl(music.cover)" class="cover" />
          <div class="info">
            <div class="name">{{ music.name }}</div>
            <div class="artist">{{ music.artist }}</div>
          </div>
        </div>
        <i 
          class="fas remove-btn" 
          :class="isEditMode ? 'fa-minus-circle' : 'fa-times'"
          @click="removeFromPlaylist(index)"
        ></i>
      </div>
    </van-list>
    
    <!-- 定位当前播放歌曲的悬浮按钮 -->
    <div 
      v-show="currentMusic && playlist.length > 0 && !isEditMode" 
      class="locate-btn" 
      @click="locateCurrentMusic"
      :title="'定位到: ' + (currentMusic?.name || '')"
    >
      <i class="fas fa-location-arrow"></i>
    </div>
    
    <!-- Mini播放器 -->
    <!-- <MiniPlayer v-if="currentMusic" /> -->
  </div>
</template>

<script>
export default {
  name: 'Playlist'
}
</script>

<script setup>
import { ref, computed, onActivated, nextTick } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getPlayQueue } from '@/api'
import { showConfirmDialog, showToast } from 'vant'
import { getCoverUrl } from '@/utils/image'
import MiniPlayer from '@/components/MiniPlayer.vue'

const musicStore = useMusicStore()
const playlist = computed(() => musicStore.playlist)
const currentMusic = computed(() => musicStore.currentMusic)
const contentRef = ref(null)

// van-list 相关
const loading = ref(false)
const finished = ref(true) // 播放列表一次性加载完，所以直接设为 true

const onLoad = () => {
  // 播放列表已经全部加载，不需要分页
  loading.value = false
  finished.value = true
}

// 编辑模式
const isEditMode = ref(false)
const dragIndex = ref(null)
const dragOverIndex = ref(null)

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
    musicStore.clearPlaylist();
    // musicStore.setPlaylist([])
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
      targetElement.scrollIntoView({ behavior: 'smooth', block: 'center' })
      showToast('已定位到当前播放')
    }
  })
}

// 切换编辑模式
const toggleEditMode = () => {
  isEditMode.value = !isEditMode.value
  if (!isEditMode.value) {
    showToast('已保存顺序')
  }
}

// 拖拽开始
const handleDragStart = (index, event) => {
  dragIndex.value = index
  event.dataTransfer.effectAllowed = 'move'
  event.dataTransfer.setData('text/html', event.target.innerHTML)
  event.target.style.opacity = '0.5'
}

// 拖拽结束
const handleDragEnd = (event) => {
  event.target.style.opacity = '1'
  dragIndex.value = null
  dragOverIndex.value = null
}

// 拖拽经过
const handleDragOver = (index, event) => {
  event.preventDefault()
  event.dataTransfer.dropEffect = 'move'
  dragOverIndex.value = index
}

// 拖拽离开
const handleDragLeave = () => {
  dragOverIndex.value = null
}

// 放置
const handleDrop = (dropIndex, event) => {
  event.preventDefault()
  event.stopPropagation()
  
  if (dragIndex.value === null || dragIndex.value === dropIndex) {
    return
  }
  
  const newPlaylist = [...playlist.value]
  const draggedItem = newPlaylist[dragIndex.value]
  
  newPlaylist.splice(dragIndex.value, 1)
  newPlaylist.splice(dropIndex, 0, draggedItem)
  
  musicStore.setPlaylist(newPlaylist)
  
  if (currentMusic.value) {
    const newIndex = newPlaylist.findIndex(m => m.id === currentMusic.value.id)
    if (newIndex >= 0) {
      musicStore.currentIndex.value = newIndex
    }
  }
  
  dragOverIndex.value = null
}

onActivated(() => {
  loadPlayQueue()
  isEditMode.value = false
  
  // 调试信息
  console.log('播放列表页面激活')
  console.log('当前播放:', currentMusic.value)
  console.log('播放列表长度:', playlist.value.length)
  console.log('编辑模式:', isEditMode.value)
  console.log('定位按钮应该显示:', !!(currentMusic.value && playlist.value.length > 0 && !isEditMode.value))
})
</script>

<style lang="scss" scoped>
.playlist-page {
  height: 100%;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  // padding-bottom: 80px; // 为 MiniPlayer 留出空间
  
  .header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 16px;
    
    .back-btn {
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
    
    .header-actions {
      display: flex;
      gap: 15px;
      
      .action-btn {
        font-size: 20px;
        color: #fff;
        cursor: pointer;
        padding: 10px;
        transition: all 0.2s;
        
        &:active {
          transform: scale(0.9);
        }
      }
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
    
    .edit-tip {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px;
      margin-bottom: 16px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 8px;
      font-size: 13px;
      color: rgba(255, 255, 255, 0.8);
      
      i {
        font-size: 14px;
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
      border: 1px solid transparent;
      margin-bottom: 10px;
      transition: all 0.3s;
      position: relative;
      
      &.active {
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.3);
      }
      
      &.edit-mode {
        cursor: move;
        
        .left {
          cursor: move;
        }
      }
      
      &.dragging {
        opacity: 0.5;
        transform: scale(0.95);
      }
      
      &.drag-over {
        border: 2px dashed rgba(255, 255, 255, 0.5);
        background: rgba(255, 255, 255, 0.15);
      }
      
      .drag-handle {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.6);
        margin-right: 8px;
        cursor: move;
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
          min-width: 0;
          max-width: 150px;
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
        
        &.fa-minus-circle {
          color: #ff4757;
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
    z-index: 999; // 提高层级，确保在最上层
    
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

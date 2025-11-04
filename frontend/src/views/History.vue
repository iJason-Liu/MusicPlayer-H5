<template>
  <div class="history-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">播放历史</h1>
      <div class="placeholder"></div>
    </div>
    
    <div class="content">
      <div v-if="playHistory.length === 0" class="empty">
        <i class="fas fa-history"></i>
        <p>暂无播放历史</p>
      </div>
      
      <div 
        v-for="music in playHistory" 
        :key="'history-' + music.id" 
        class="history-item"
        @click="handlePlay(music)"
      >
        <img :src="getCoverUrl(music.cover)" class="cover" />
        <div class="info">
          <div class="name">{{ music.name }}</div>
          <div class="artist">{{ music.artist }}</div>
          <div class="stats">
            <span class="play-count">播放 {{ music.play_count || 0 }} 次</span>
            <span v-if="music.total_duration_text" class="duration">· {{ music.total_duration_text }}</span>
          </div>
        </div>
        <div class="actions">
          <i 
            class="fas action-icon"
            :class="isFavorite(music.id) ? 'fa-heart' : 'fa-heart'"
            :style="{ color: isFavorite(music.id) ? '#ff4757' : 'rgba(255,255,255,0.6)' }"
            @click.stop="handleFavorite(music)"
          ></i>
          <i class="fas fa-trash action-icon" @click.stop="handleDelete(music.id)"></i>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'History'
}
</script>

<script setup>
import { computed, onActivated } from 'vue'
import { useMusicStore } from '@/stores/music'
import { showToast, showConfirmDialog } from 'vant'
import { getCoverUrl } from '@/utils/image'

const musicStore = useMusicStore()
const playHistory = computed(() => musicStore.playHistory)

// 每次页面激活时刷新数据
onActivated(() => {
  musicStore.loadPlayHistory()
})

const handlePlay = (music) => {
  musicStore.setPlaylist(playHistory.value)
  const index = playHistory.value.findIndex(m => m.id === music.id)
  musicStore.playMusic(music, index)
}

const isFavorite = (musicId) => {
  return musicStore.isFavorite(musicId)
}

const handleFavorite = (music) => {
  const added = musicStore.toggleFavorite(music)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
}

const handleDelete = async (musicId) => {
  try {
    await showConfirmDialog({
      title: '确认删除',
      message: '确定要删除这条播放记录吗？',
    })
    await musicStore.removeFromHistory(musicId)
    showToast('删除成功')
  } catch (error) {
    // 用户取消
  }
}
</script>

<style lang="scss" scoped>
.history-page {
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
    
    .history-item {
      display: flex;
      align-items: center;
      padding: 12px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s;
      
      &:active {
        transform: scale(0.98);
        background: rgba(255, 255, 255, 0.15);
      }
      
      .cover {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
        margin-right: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
      }
      
      .info {
        flex: 1;
        min-width: 0;
        overflow: hidden;
        margin-right: 12px;
        
        .name {
          font-size: 15px;
          font-weight: 600;
          color: #fff;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          margin-bottom: 4px;
        }
        
        .artist {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.7);
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
          margin-bottom: 4px;
        }
        
        .stats {
          font-size: 11px;
          color: rgba(255, 255, 255, 0.5);
          
          .play-count {
            margin-right: 4px;
          }
          
          .duration {
            margin-left: 4px;
          }
        }
      }
      
      .actions {
        display: flex;
        gap: 15px;
        flex-shrink: 0;
        
        .action-icon {
          font-size: 18px;
          color: rgba(255, 255, 255, 0.6);
          cursor: pointer;
          transition: all 0.2s;
          
          &:active {
            transform: scale(0.9);
          }
        }
      }
    }
  }
}
</style>

<template>
  <div class="playlist-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">播放列表</h1>
      <i class="fas fa-trash clear-btn" @click="clearPlaylist" v-if="playlist.length > 0"></i>
      <div class="placeholder" v-else></div>
    </div>
    
    <div class="content">
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
          <img :src="music.cover" class="cover" />
          <div class="info">
            <div class="name">{{ music.name }}</div>
            <div class="artist">{{ music.artist }}</div>
          </div>
        </div>
        <i class="fas fa-times remove-btn" @click="removeFromPlaylist(index)"></i>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMusicStore } from '@/stores/music'
import { showConfirmDialog, showToast } from 'vant'

const musicStore = useMusicStore()
const playlist = computed(() => musicStore.playlist)
const currentMusic = computed(() => musicStore.currentMusic)

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
}
</style>

<template>
  <div class="music-item" @click="handlePlay">
    <img :src="music.cover" class="cover" />
    <div class="info">
      <div class="name">{{ music.name }}</div>
      <div class="artist">{{ music.artist }} - {{ music.album }}</div>
    </div>
    <div class="actions">
      <i 
        class="fas action-icon"
        :class="isFavorite ? 'fa-heart' : 'fa-heart'"
        :style="{ color: isFavorite ? '#ff4757' : 'rgba(255,255,255,0.6)' }"
        @click.stop="handleFavorite"
      ></i>
      <i class="fas fa-ellipsis-v action-icon" @click.stop="showMenu"></i>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMusicStore } from '@/stores/music'
import { showToast } from 'vant'

const props = defineProps({
  music: {
    type: Object,
    required: true
  }
})

const musicStore = useMusicStore()

const isFavorite = computed(() => musicStore.isFavorite(props.music.id))

const handlePlay = () => {
  musicStore.playMusic(props.music)
}

const handleFavorite = () => {
  const added = musicStore.toggleFavorite(props.music)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
}

const showMenu = () => {
  // 可以扩展更多操作
  musicStore.addToPlaylist(props.music)
  showToast('已添加到播放列表')
}
</script>

<style lang="scss" scoped>
.music-item {
  display: flex;
  align-items: center;
  padding: 12px 16px;
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
    overflow: hidden;
    
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
    }
  }
  
  .actions {
    display: flex;
    gap: 15px;
    
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
</style>

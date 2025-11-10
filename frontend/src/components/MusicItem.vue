<template>
  <div class="music-item" @click="handlePlay">
    <img :src="getCoverUrl(music.cover)" class="cover" />
    <div class="info">
      <div class="name">{{ music.name }}</div>
      <div class="artist">{{ music.artist }}{{ music.album ? ' - ' + music.album : '' }}</div>
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

  <!-- 操作菜单 -->
  <MusicActionSheet 
    v-model:show="showActionSheet" 
    :music="music"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useMusicStore } from '@/stores/music'
import { showToast } from 'vant'
import { getCoverUrl } from '@/utils/image'
import MusicActionSheet from './MusicActionSheet.vue'

const props = defineProps({
  music: {
    type: Object,
    required: true
  },
  // 当前列表的所有歌曲
  playlist: {
    type: Array,
    default: () => []
  }
})

const musicStore = useMusicStore()
const showActionSheet = ref(false)

const isFavorite = computed(() => musicStore.isFavorite(props.music.id))

const handlePlay = () => {
  // 如果传递了播放列表，则设置为当前播放队列
  if (props.playlist && props.playlist.length > 0) {
    musicStore.setPlaylist(props.playlist)
    // 找到当前歌曲在列表中的索引
    const index = props.playlist.findIndex(m => m.id === props.music.id)
    musicStore.playMusic(props.music, index)
  } else {
    // 没有传递列表，只播放当前歌曲
    musicStore.playMusic(props.music)
  }
}

const handleFavorite = async () => {
  const added = await musicStore.toggleFavorite(props.music)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
}

const showMenu = () => {
  showActionSheet.value = true
}
</script>

<style lang="scss" scoped>
.music-item {
  display: flex;
  align-items: center;
  padding: 12px;
  background: rgba(255, 255, 255, 0.1);
  backdrop-filter: blur(10px);
  border-radius: 12px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: all 0.3s;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
  
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
</style>

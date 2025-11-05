<template>
  <div class="mini-player" @click="goToPlayer">
    <div class="progress-bar">
      <div class="progress" :style="{ width: progress + '%' }"></div>
    </div>
    <div class="mini-player-content">
      <img :src="getCoverUrl(currentMusic?.cover)" class="cover rotating" :class="{ paused: !isPlaying }" />
      <div class="info">
        <div class="name">{{ currentMusic.name }}</div>
        <div class="artist">{{ currentMusic.artist }}</div>
      </div>
      <div class="controls">
        <i 
          class="fas control-icon" 
          :class="isPlaying ? 'fa-pause' : 'fa-play'"
          @click.stop="togglePlay"
        ></i>
        <i class="fas fa-step-forward control-icon" @click.stop="playNext"></i>
        <i class="fas fa-ellipsis-v control-icon" @click.stop="showActionSheet = true"></i>
      </div>
    </div>
  </div>

  <!-- 操作菜单 -->
  <MusicActionSheet 
    v-model:show="showActionSheet" 
    :music="currentMusic"
  />
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMusicStore } from '@/stores/music'
import { getCoverUrl } from '@/utils/image'
import MusicActionSheet from './MusicActionSheet.vue'

const router = useRouter()
const musicStore = useMusicStore()

const currentMusic = computed(() => musicStore.currentMusic)
const isPlaying = computed(() => musicStore.isPlaying)
const progress = computed(() => musicStore.progress)
const showActionSheet = ref(false)

const togglePlay = () => {
  musicStore.togglePlay()
}

const playNext = () => {
  musicStore.playNext()
}

const goToPlayer = () => {
  router.push('/player')
}
</script>

<style lang="scss" scoped>
.mini-player {
  position: fixed;
  bottom: 80px;
  left: 0;
  right: 0;
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(30px) saturate(180%);
  -webkit-backdrop-filter: blur(30px) saturate(180%);
  border-top: 1px solid rgba(255, 255, 255, 0.25);
  z-index: 998;
  cursor: pointer;
  box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
  
  .mini-player-content {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    gap: 12px;
    
    .cover {
      width: 45px;
      height: 45px;
      border-radius: 50%;
      object-fit: cover;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
      
      &.rotating {
        animation: rotate 20s linear infinite;
      }
      
      &.paused {
        animation-play-state: paused;
      }
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
      }
      
      .artist {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        margin-top: 2px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
      }
    }
    
    .controls {
      display: flex;
      gap: 20px;
      
      .control-icon {
        font-size: 20px;
        color: #fff;
        cursor: pointer;
        transition: transform 0.2s;
        
        &:active {
          transform: scale(0.9);
        }
      }
    }
  }
  
  .progress-bar {
    height: 3px;
    background: rgba(255, 255, 255, 0.2);
    
    .progress {
      height: 100%;
      background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0.8));
      transition: width 0.3s;
    }
  }
}

@keyframes rotate {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>

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
      
      <MusicItem 
        v-for="music in playHistory" 
        :key="'history-' + music.id" 
        :music="music"
        :playlist="playHistory"
      />
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
import MusicItem from '@/components/MusicItem.vue'

const musicStore = useMusicStore()
const playHistory = computed(() => musicStore.playHistory)

// 每次页面激活时刷新数据
onActivated(() => {
  musicStore.loadPlayHistory()
})
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
  }
}
</style>

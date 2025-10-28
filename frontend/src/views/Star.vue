<template>
  <div class="star-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">我的喜欢</h1>
      <div class="placeholder"></div>
    </div>
    
    <div class="content">
      <div v-if="favorites.length === 0" class="empty">
        <i class="fas fa-heart"></i>
        <p>还没有喜欢的歌曲</p>
      </div>
      
      <MusicItem 
        v-for="music in favorites" 
        :key="music.id" 
        :music="music"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useMusicStore } from '@/stores/music'
import MusicItem from '@/components/MusicItem.vue'

const musicStore = useMusicStore()
const favorites = computed(() => musicStore.favorites)
</script>

<style lang="scss" scoped>
.star-page {
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

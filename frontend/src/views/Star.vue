<template>
  <div class="star-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">我的喜欢 ({{ favoriteList.length }})</h1>
      <div class="placeholder"></div>
    </div>
    
    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
      class="content"
    >
      <div v-if="favoriteList.length === 0 && !loading" class="empty">
        <i class="fas fa-heart"></i>
        <p>还没有喜欢的歌曲</p>
      </div>
      
      <MusicItem 
        v-for="music in favoriteList" 
        :key="music.id" 
        :music="music"
        :playlist="favoriteList"
      />
    </van-list>
    
    <!-- Mini播放器 -->
    <!-- <MiniPlayer v-if="currentMusic" /> -->
  </div>
</template>

<script>
export default {
  name: 'Star'
}
</script>

<script setup>
import { ref, computed, onActivated } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getFavoriteList } from '@/api'
import { showToast } from 'vant'
import MusicItem from '@/components/MusicItem.vue'
import MiniPlayer from '@/components/MiniPlayer.vue'

const musicStore = useMusicStore()
const currentMusic = computed(() => musicStore.currentMusic)

// 分页相关
const favoriteList = ref([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const limit = ref(20)

const loadfavoriteList = async () => {
  try {
    loading.value = true
    console.log(`加载收藏列表第 ${page.value} 页`)
    
    const res = await getFavoriteList({
      page: page.value,
      limit: limit.value
    })
    
    const favoriteData = res.data?.list || []
    const totalCount = res.data?.total || 0
    
    if (favoriteData.length > 0) {
      // 追加数据
      favoriteList.value = [...favoriteList.value, ...favoriteData]
      
      console.log(`当前 ${favoriteList.value.length} 条，共 ${totalCount} 条`)
      
      // 判断是否还有更多数据
      if (favoriteData.length < limit.value || favoriteList.value.length >= totalCount) {
        finished.value = true
        console.log('✅ 收藏列表加载完成')
      } else {
        finished.value = false
        page.value++
        console.log(`准备加载第 ${page.value} 页`)
      }
    } else {
      if (page.value === 1) {
        console.log('暂无收藏')
      }
      finished.value = true
    }
  } catch (error) {
    console.error('加载收藏列表失败:', error)
    showToast('加载失败')
    // finished.value = true
  } finally {
    loading.value = false
  }
}

// 加载数据
const onLoad = async () => {
  // 如果已经加载完成，不再加载
  if (finished.value) {
    console.log('已加载完成，跳过')
    return
  }
  
  // 加载当前页
  // 注意：不检查 loading.value，因为 van-list 组件会自动管理 loading 状态
  // 组件会在 loading 变为 false 后才触发下一次 onLoad
  loadfavoriteList()
}

// 每次页面激活时重新加载数据
onActivated(() => {
  page.value = 1
  finished.value = false
  favoriteList.value = []
  // van-list 会自动触发 onLoad
})
</script>

<style lang="scss" scoped>
.star-page {
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
    
    .placeholder {
      width: 40px;
    }
  }
  
  .content {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 20px;
    height: 0; // 确保 flex 子元素有正确的高度
    
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

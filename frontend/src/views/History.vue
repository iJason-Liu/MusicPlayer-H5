<template>
  <div class="history-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">播放历史 ({{ total }})</h1>
      <div class="placeholder"></div>
    </div>
    
    <van-list
      v-model:loading="loading"
      :finished="finished"
      finished-text="没有更多了"
      @load="onLoad"
      class="content"
    >
      <div v-if="historyList.length === 0 && !loading" class="empty">
        <i class="fas fa-history"></i>
        <p>暂无播放历史</p>
      </div>
      
      <div 
        v-for="music in historyList" 
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
    </van-list>
    
    <!-- Mini播放器 -->
    <!-- <MiniPlayer v-if="currentMusic" /> -->
  </div>
</template>

<script>
export default {
  name: 'History'
}
</script>

<script setup>
import { ref, computed, onActivated } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getPlayHistory } from '@/api'
import { showToast, showConfirmDialog } from 'vant'
import { getCoverUrl } from '@/utils/image'
import MiniPlayer from '@/components/MiniPlayer.vue'

const musicStore = useMusicStore()
const currentMusic = computed(() => musicStore.currentMusic)

// 分页相关
const historyList = ref([])
const loading = ref(false)
const finished = ref(false)
const page = ref(1)
const limit = ref(20)
const total = ref(0)

const loadHistory = async () => {
  try {
    loading.value = true
    console.log(`加载播放历史第 ${page.value} 页`)
    
    const res = await getPlayHistory({
      page: page.value,
      limit: limit.value
    })
    
    const historyData = res.data?.list || []
    const totalCount = res.data?.total || 0
    total.value = totalCount
    
    if (historyData.length > 0) {
      // 追加数据
      historyList.value = [...historyList.value, ...historyData]
      
      console.log(`当前 ${historyList.value.length} 条，共 ${totalCount} 条`)
      
      // 判断是否还有更多数据
      if (historyData.length < limit.value || historyList.value.length >= totalCount) {
        finished.value = true
        console.log('✅ 播放历史加载完成')
      } else {
        finished.value = false
        page.value++
        console.log(`准备加载第 ${page.value} 页`)
      }
    } else {
      if (page.value === 1) {
        console.log('暂无播放历史')
      }
      finished.value = true
    }
  } catch (error) {
    console.error('加载播放历史失败:', error)
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
  loadHistory()
}

// 每次页面激活时重新加载数据
onActivated(() => {
  page.value = 1
  finished.value = false
  historyList.value = []
  // 不需要手动调用 onLoad，van-list 会自动触发
})

const handlePlay = (music) => {
  musicStore.setPlaylist(historyList.value)
  const index = historyList.value.findIndex(m => m.id === music.id)
  musicStore.playMusic(music, index)
}

const isFavorite = (musicId) => {
  return musicStore.isFavorite(musicId)
}

const handleFavorite = async (music) => {
  const added = await musicStore.toggleFavorite(music)
  // console.log(added)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
}

const handleDelete = async (musicId) => {
  try {
    await showConfirmDialog({
      title: '确认删除',
      message: '确定要删除这条播放记录吗？',
    })
    await musicStore.removeFromHistory(musicId)
    // 从列表中移除
    historyList.value = historyList.value.filter(m => m.id !== musicId)
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

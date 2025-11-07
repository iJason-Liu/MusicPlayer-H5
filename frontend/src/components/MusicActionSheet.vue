<template>
  <van-action-sheet v-model:show="visible" @close="handleClose" :z-index="2100">
    <!-- 歌曲信息 -->
    <div class="music-info" v-if="music">
      <img :src="getCoverUrl(music.cover)" class="cover" />
      <div class="info">
        <div class="name">{{ music.name }}</div>
        <div class="artist">{{ music.artist }} {{ music.album ? ' - ' + music.album : '' }}</div>
      </div>
    </div>

    <!-- 操作列表 -->
    <div class="action-list">
      <div class="action-item" @click="handlePlayNext">
        <i class="fas fa-play-circle"></i>
        <span>下一首播放</span>
      </div>
      
      <div class="action-item" @click="handleViewDetail">
        <i class="fas fa-info-circle"></i>
        <span>歌曲详情</span>
      </div>
      
      <div class="action-item" @click="handleToggleFavorite">
        <i class="fas fa-heart" :style="{ color: isFavorite ? '#ff4757' : '' }"></i>
        <span>{{ isFavorite ? '取消喜欢' : '我的喜欢' }}</span>
      </div>
      
      <div class="action-item" @click="handleAddToPlaylist">
        <i class="fas fa-plus-circle"></i>
        <span>添加到歌单</span>
      </div>
    </div>

    <!-- 添加到歌单弹窗 -->
    <van-popup
      v-model:show="showPlaylistSelector"
      position="bottom"
      round
      :style="{ maxHeight: '70vh' }"
      :z-index="2200"
    >
      <div class="playlist-selector">
        <div class="selector-header">
          <h3>添加到歌单</h3>
          <i class="fas fa-times" @click="showPlaylistSelector = false"></i>
        </div>
        
        <div class="create-new" @click="handleCreatePlaylist">
          <i class="fas fa-plus-circle"></i>
          <span>创建新歌单</span>
        </div>
        
        <div class="playlist-list" v-if="playlists.length > 0">
          <div 
            v-for="playlist in playlists" 
            :key="playlist.id"
            class="playlist-item"
            @click="handleSelectPlaylist(playlist)"
          >
            <img :src="getCoverUrl(playlist.cover)" class="cover" />
            <div class="info">
              <div class="name">{{ playlist.name }}</div>
              <div class="count">{{ playlist.music_count || 0 }}首</div>
            </div>
            <i class="fas fa-check" v-if="selectedPlaylists.includes(playlist.id)"></i>
          </div>
        </div>
        
        <div class="empty" v-else>
          <i class="fas fa-list-ul"></i>
          <p>还没有创建歌单</p>
        </div>
      </div>
    </van-popup>
  </van-action-sheet>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useMusicStore } from '@/stores/music'
import { showToast, showConfirmDialog } from 'vant'
import { getPlaylistList, addMusicToPlaylist } from '@/api'
import { getCoverUrl } from '@/utils/image'

const router = useRouter()
const musicStore = useMusicStore()

const props = defineProps({
  show: {
    type: Boolean,
    default: false
  },
  music: {
    type: Object,
    default: null
  }
})

const emit = defineEmits(['update:show', 'close'])

const visible = computed({
  get: () => props.show,
  set: (val) => emit('update:show', val)
})

const showPlaylistSelector = ref(false)
const playlists = ref([])
const selectedPlaylists = ref([])

const isFavorite = computed(() => {
  return props.music ? musicStore.isFavorite(props.music.id) : false
})

// 加载歌单列表
const loadPlaylists = async () => {
  try {
    const res = await getPlaylistList()
    playlists.value = res.data?.list || []
  } catch (error) {
    console.error('加载歌单失败:', error)
  }
}

// 下一首播放
const handlePlayNext = () => {
  if (!props.music) return
  
  // 获取当前播放列表
  const currentPlaylist = [...musicStore.playlist]
  const currentIndex = musicStore.currentIndex
  
  // 在当前播放位置后插入
  currentPlaylist.splice(currentIndex + 1, 0, props.music)
  
  // 更新播放列表
  musicStore.setPlaylist(currentPlaylist)
  
  showToast('已添加到下一首播放')
  handleClose()
}

// 查看详情
const handleViewDetail = () => {
  if (!props.music) return
  router.push(`/music/${props.music.id}`)
  handleClose()
}

// 切换收藏
const handleToggleFavorite = () => {
  if (!props.music) return
  const added = musicStore.toggleFavorite(props.music)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
  handleClose()
}

// 添加到歌单
const handleAddToPlaylist = async () => {
  await loadPlaylists()
  
  if (playlists.value.length === 0) {
    showConfirmDialog({
      title: '提示',
      message: '还没有创建歌单，是否立即创建？',
      confirmButtonText: '创建歌单',
      cancelButtonText: '取消'
    }).then(() => {
      router.push('/mine/playlists')
      handleClose()
    }).catch(() => {})
  } else {
    showPlaylistSelector.value = true
  }
}

// 创建新歌单
const handleCreatePlaylist = () => {
  router.push('/mine/playlists')
  handleClose()
}

// 选择歌单
const handleSelectPlaylist = async (playlist) => {
  if (!props.music) return
  
  try {
    await addMusicToPlaylist(playlist.id, props.music.id)
    showToast('已添加到歌单')
    selectedPlaylists.value.push(playlist.id)
    
    // 延迟关闭，让用户看到选中效果
    setTimeout(() => {
      showPlaylistSelector.value = false
      handleClose()
    }, 500)
  } catch (error) {
    console.error('添加到歌单失败:', error.message)
    showToast(error.message || '添加失败')
    // 延迟关闭，让用户看到选中效果
    setTimeout(() => {
      showPlaylistSelector.value = false
      handleClose()
    }, 100)
  }
}

// 关闭
const handleClose = () => {
  visible.value = false
  showPlaylistSelector.value = false
  selectedPlaylists.value = []
  emit('close')
}

// 监听显示状态
watch(() => props.show, (val) => {
  if (val) {
    selectedPlaylists.value = []
  }
})
</script>

<style lang="scss" scoped>
:deep(.van-action-sheet) {
  max-height: 80vh;
  padding-bottom: env(safe-area-inset-bottom);
}

.music-info {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 20px;
  border-bottom: 1px solid #f0f0f0;
  
  .cover {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
  }
  
  .info {
    flex: 1;
    min-width: 0;
    
    .name {
      font-size: 15px;
      font-weight: 600;
      color: #333;
      margin-bottom: 4px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
    
    .artist {
      font-size: 13px;
      color: #999;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }
  }
}

.action-list {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  padding: 24px 20px;
  
  .action-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    transition: all 0.2s;
    
    &:active {
      transform: scale(0.95);
    }
    
    i {
      font-size: 28px;
      color: #666;
      transition: color 0.2s;
    }
    
    span {
      font-size: 12px;
      color: #666;
      text-align: center;
    }
  }
}

.playlist-selector {
  padding: 20px;
  max-height: 70vh;
  overflow-y: auto;
  
  .selector-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f0f0f0;
    
    h3 {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin: 0;
    }
    
    i {
      font-size: 20px;
      color: #999;
      cursor: pointer;
      padding: 8px;
      
      &:active {
        color: #666;
      }
    }
  }
  
  .create-new {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f5f5f5;
    border-radius: 12px;
    margin-bottom: 16px;
    cursor: pointer;
    transition: all 0.2s;
    
    &:active {
      background: #e8e8e8;
    }
    
    i {
      font-size: 24px;
      color: #1989fa;
    }
    
    span {
      font-size: 15px;
      font-weight: 500;
      color: #333;
    }
  }
  
  .playlist-list {
    .playlist-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      border-radius: 12px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: all 0.2s;
      
      &:active {
        background: #f5f5f5;
      }
      
      .cover {
        width: 50px;
        height: 50px;
        border-radius: 8px;
        object-fit: cover;
      }
      
      .info {
        flex: 1;
        min-width: 0;
        
        .name {
          font-size: 14px;
          font-weight: 500;
          color: #333;
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        
        .count {
          font-size: 12px;
          color: #999;
        }
      }
      
      .fa-check {
        font-size: 18px;
        color: #1989fa;
      }
    }
  }
  
  .empty {
    text-align: center;
    padding: 40px 20px;
    
    i {
      font-size: 48px;
      color: #ddd;
      margin-bottom: 12px;
    }
    
    p {
      font-size: 14px;
      color: #999;
      margin: 0;
    }
  }
}
</style>

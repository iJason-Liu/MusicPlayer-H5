<template>
  <div class="home-page">
    <div class="header">
      <h1 class="title">
        <i class="fas fa-music"></i>
        音乐库
      </h1>
      <van-search
        v-model="searchKeyword"
        placeholder="搜索歌曲、歌手"
        shape="round"
        background="transparent"
        @search="handleSearch"
      />
    </div>
    
    <div class="content">
      <van-list
        v-model:loading="loading"
        :finished="finished"
        finished-text="没有更多了"
        @load="onLoad"
      >
        <MusicItem 
          v-for="music in displayList" 
          :key="music.id" 
          :music="music"
        />
      </van-list>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getMusicList, searchMusic } from '@/api/music'
import MusicItem from '@/components/MusicItem.vue'
import { showToast } from 'vant'

const musicStore = useMusicStore()
const searchKeyword = ref('')
const displayList = ref([])
const loading = ref(false)
const finished = ref(false)

// 分页参数
const page = ref(1)
const limit = ref(20)
const total = ref(0)

const loadMusicList = async () => {
  try {
    console.log(`开始加载音乐列表... 第${page.value}页`)
    loading.value = true
    
    const res = await getMusicList({
      page: page.value,
      limit: limit.value,
      keyword: searchKeyword.value
    })
    
    console.log('音乐列表响应:', res)
    
    if (res.data && res.data.list && res.data.list.length > 0) {
      // 第一页替换，后续页追加
      if (page.value === 1) {
        displayList.value = res.data.list
        musicStore.setMusicList(res.data.list)
        musicStore.setPlaylist(res.data.list)
      } else {
        displayList.value = [...displayList.value, ...res.data.list]
      }
      
      total.value = res.data.total
      
      // 判断是否还有更多数据
      if (displayList.value.length >= res.data.total) {
        finished.value = true
      }
      
      console.log(`✅ 音乐列表加载成功，当前${displayList.value.length}首，共${res.data.total}首`)
    } else {
      if (page.value === 1) {
        console.warn('⚠️ 暂无音乐数据')
        showToast('暂无音乐')
      }
      finished.value = true
    }
  } catch (error) {
    console.error('❌ 加载音乐列表失败:', error)
    console.error('错误详情:', error.response || error.message)
    showToast('加载失败，请稍后重试')
    finished.value = true
  } finally {
    loading.value = false
  }
}

const handleSearch = async () => {
  // 重置分页
  page.value = 1
  displayList.value = []
  finished.value = false
  
  if (!searchKeyword.value.trim()) {
    // 清空搜索，重新加载全部
    await loadMusicList()
    return
  }
  
  try {
    loading.value = true
    const res = await searchMusic(searchKeyword.value)
    
    if (res.data && res.data.length > 0) {
      displayList.value = res.data
      finished.value = true
    } else {
      displayList.value = []
      showToast('未找到相关音乐')
      finished.value = true
    }
  } catch (error) {
    console.error('搜索失败:', error)
    showToast('搜索失败，请稍后重试')
    finished.value = true
  } finally {
    loading.value = false
  }
}

const onLoad = () => {
  if (!finished.value) {
    page.value++
    loadMusicList()
  }
}

onMounted(() => {
  console.log('=== Home.vue 已挂载 ===')
  loadMusicList()
})
</script>

<style lang="scss" scoped>
.home-page {
  height: 100%;
  display: flex;
  flex-direction: column;
  
  .header {
    flex-shrink: 0;
    padding: 20px 16px 10px;
    
    .title {
      font-size: 28px;
      font-weight: 700;
      color: #fff;
      margin-bottom: 15px;
      display: flex;
      align-items: center;
      gap: 10px;
      
      i {
        font-size: 24px;
      }
    }
    
    :deep(.van-search) {
      padding: 0;
      
      .van-search__content {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        
        input {
          color: #fff;
          
          &::placeholder {
            color: rgba(255, 255, 255, 0.6);
          }
        }
      }
      
      .van-icon {
        color: rgba(255, 255, 255, 0.8);
      }
    }
  }
  
  .content {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 75px;
    
    &::-webkit-scrollbar {
      width: 4px;
    }
    
    &::-webkit-scrollbar-thumb {
      background: rgba(255, 255, 255, 0.3);
      border-radius: 2px;
    }
  }
}
</style>

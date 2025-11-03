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
    
    <div class="content" ref="contentRef">
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
          :playlist="displayList"
          :ref="el => { if (music.id === currentMusic?.id) currentMusicRef = el }"
        />
      </van-list>
    </div>
    
    <!-- 定位当前播放歌曲的悬浮按钮 -->
    <div 
      v-if="currentMusic && showLocateBtn" 
      class="locate-btn" 
      @click="locateCurrentMusic"
    >
      <i class="fas fa-location-arrow"></i>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Home'
}
</script>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useMusicStore } from '@/stores/music'
import { getMusicList, searchMusic } from '@/api'
import MusicItem from '@/components/MusicItem.vue'
import { showToast } from 'vant'

const musicStore = useMusicStore()
const searchKeyword = ref('')
const displayList = ref([])
const loading = ref(false)
const finished = ref(false)
const contentRef = ref(null)
const currentMusicRef = ref(null)

// 分页参数
const page = ref(1)
const limit = ref(20)
const total = ref(0)

// 当前播放的音乐
const currentMusic = computed(() => musicStore.currentMusic)

// 是否显示定位按钮（当前播放的歌曲在列表中时显示）
const showLocateBtn = computed(() => {
  if (!currentMusic.value) return false
  return displayList.value.some(m => m.id === currentMusic.value.id)
})

// 定位到当前播放的歌曲
const locateCurrentMusic = () => {
  if (!currentMusic.value) return
  
  nextTick(() => {
    // 查找当前播放歌曲的元素
    const musicItems = document.querySelectorAll('.music-item')
    let targetElement = null
    
    musicItems.forEach(item => {
      // 通过检查元素内容来找到对应的歌曲
      if (item.querySelector('.name')?.textContent === currentMusic.value.name) {
        targetElement = item
      }
    })
    
    if (targetElement && contentRef.value) {
      // 计算目标元素相对于滚动容器的位置
      const containerRect = contentRef.value.getBoundingClientRect()
      const targetRect = targetElement.getBoundingClientRect()
      const scrollTop = contentRef.value.scrollTop
      const offset = targetRect.top - containerRect.top + scrollTop - 100 // 留出一些顶部空间
      
      // 平滑滚动到目标位置
      contentRef.value.scrollTo({
        top: offset,
        behavior: 'smooth'
      })
      
      showToast('已定位到当前播放')
    } else {
      showToast('当前播放的歌曲不在列表中')
    }
  })
}

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
    
    // 处理分页数据格式
    const musicData = res.data?.list || res.data || []
    const totalCount = res.data?.total || musicData.length
    
    if (musicData.length > 0) {
      // 第一页替换，后续页追加
      if (page.value === 1) {
        displayList.value = musicData
        musicStore.setMusicList(musicData)
      } else {
        displayList.value = [...displayList.value, ...musicData]
      }
      
      total.value = totalCount
      
      // 判断是否还有更多数据
      if (displayList.value.length >= totalCount) {
        finished.value = true
      }
      
      console.log(`✅ 音乐列表加载成功，当前${displayList.value.length}首，共${totalCount}首`)
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
  // console.log('onLoad', page.value, displayList.value.length, finished.value, loading.value)
  // 如果是第一页且列表为空，说明是首次加载
  if (page.value === 1 && displayList.value.length === 0) {
    loadMusicList()
  } else if (!finished.value && loading.value) {
    // console.log('触底加载下一页')
    // 触底加载下一页
    page.value++
    loadMusicList()
  }
}

onMounted(() => {
  console.log('=== Home.vue 已挂载 ===')
  // 只在首次挂载且列表为空时加载
  if (displayList.value.length === 0) {
    loadMusicList()
  }
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
  
  .locate-btn {
    position: fixed;
    right: 20px;
    bottom: 180px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    
    i {
      font-size: 20px;
      color: #fff;
    }
    
    &:active {
      transform: scale(0.9);
      background: rgba(255, 255, 255, 0.3);
    }
  }
}
</style>

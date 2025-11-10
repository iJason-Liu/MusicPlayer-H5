<template>
  <div class="playlist-detail-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">歌单详情</h1>
      <i class="fas fa-ellipsis-v more-btn" @click="showActions = true"></i>
    </div>

    <!-- 歌单信息 -->
    <div class="playlist-info" v-if="playlist">
      <div class="cover">
        <img :src="getCoverUrl(playlist.cover) || defaultCover" />
      </div>
      <div class="info">
        <h2 class="name">{{ playlist.name }}</h2>
        <p class="desc">{{ playlist.description || '暂无描述' }}</p>
        <div class="meta">
          <span>{{ playlist.music_count || 0 }}首</span>
          <span>·</span>
          <span>播放{{ playlist.play_count || 0 }}次</span>
        </div>
      </div>
      
    </div>

    <!-- 操作按钮 -->
    <div class="action-bar">
      <van-button type="primary" block @click="playAll">
        <i class="fas fa-play"></i>
        播放全部
      </van-button>
    </div>

    <!-- 空状态 -->
    <div v-if="musicList.length === 0 && !loading" class="empty">
      <i class="fas fa-music"></i>
      <p>歌单还没有歌曲</p>
    </div>

    <!-- 音乐列表 -->
    <div v-else class="music-list">
      <div 
        v-for="(music, index) in musicList" 
        :key="music.id"
        class="music-item"
        @click="handlePlay(music, index)"
      >
        <div class="index">{{ index + 1 }}</div>
        <img :src="getCoverUrl(music.cover)" class="cover" />
        <div class="info">
          <div class="name">{{ music.name }}</div>
          <div class="artist">{{ music.artist }}</div>
        </div>
        <div class="actions" @click.stop>
          <i 
            class="fas fa-heart action-icon"
            :style="{ color: isFavorite(music.id) ? '#ff4757' : 'rgba(255,255,255,0.6)' }"
            @click="handleFavorite(music)"
          ></i>
          <i class="fas fa-trash action-icon" @click="handleRemove(music)"></i>
        </div>
      </div>
    </div>

    <!-- 操作菜单 -->
    <van-action-sheet
      v-model:show="showActions"
      :actions="actions"
      cancel-text="取消"
      @select="onActionSelect"
    />

    <!-- 编辑歌单弹窗 -->
    <van-popup
      v-model:show="showEditDialog"
      position="bottom"
      round
      :style="{ padding: '20px' }"
    >
      <div class="edit-popup">
        <div class="popup-header">
          <h3>编辑歌单</h3>
          <i class="fas fa-times" @click="showEditDialog = false"></i>
        </div>
        
        <div class="popup-content">
          <!-- 封面上传 - 居中布局 -->
          <div class="cover-field">
            <div class="field-label">歌单封面</div>
            <div class="field-value">
              <div class="cover-preview" v-if="formData.cover || coverPreview" @click="triggerUpload">
                <img :src="formData.cover ? getCoverUrl(formData.cover) : getCoverUrl(coverPreview)" />
                <div class="cover-overlay">
                  <i class="fas fa-camera"></i>
                  <span>更换封面</span>
                </div>
              </div>
              <div class="upload-btn" v-else @click="triggerUpload">
                <i class="fas fa-plus"></i>
                <span>上传封面</span>
              </div>
              <input 
                ref="fileInput"
                type="file" 
                accept="image/*" 
                @change="handleFileChange"
                style="display: none"
              />
            </div>
          </div>

          <van-field
            v-model="formData.name"
            label="歌单名称"
            placeholder="请输入歌单名称"
            required
            clearable
          />
          
          <van-field
            v-model="formData.description"
            label="描述"
            type="textarea"
            placeholder="请输入歌单描述（可选）"
            rows="3"
            maxlength="200"
            show-word-limit
            clearable
          />
        </div>
        
        <div class="popup-actions">
          <van-button block @click="showEditDialog = false">取消</van-button>
          <van-button type="primary" block @click="handleSaveEdit">确定</van-button>
        </div>
      </div>
    </van-popup>
  </div>
</template>

<script setup>
import { ref, onActivated } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useMusicStore } from '@/stores/music'
import { showToast, showConfirmDialog } from 'vant'
import { getPlaylistDetail, removeMusicFromPlaylist, deletePlaylist, updatePlaylist } from '@/api'
import { getCoverUrl } from '@/utils/image'

const router = useRouter()
const route = useRoute()
const musicStore = useMusicStore()

const playlist = ref(null)
const musicList = ref([])
const loading = ref(false)
const showActions = ref(false)
const showEditDialog = ref(false)
const defaultCover = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200"%3E%3Crect fill="%23667eea" width="200" height="200"/%3E%3Ctext x="50%25" y="50%25" dominant-baseline="middle" text-anchor="middle" font-size="80" fill="white"%3E♪%3C/text%3E%3C/svg%3E'

const formData = ref({
  name: '',
  description: '',
  cover: ''
})

const fileInput = ref(null)
const coverPreview = ref('')
const uploadedFile = ref(null)

const actions = [
  { name: '编辑歌单', value: 'edit' },
  { name: '删除歌单', value: 'delete', color: '#ee0a24' }
]

// 加载歌单详情
const loadPlaylistDetail = async () => {
  try {
    loading.value = true
    const res = await getPlaylistDetail(route.params.id)
    playlist.value = res.data
    musicList.value = res.data.music_list || []
  } catch (error) {
    console.error('加载歌单详情失败:', error)
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

// 播放全部
const playAll = () => {
  if (musicList.value.length === 0) {
    showToast('歌单还没有歌曲')
    return
  }
  musicStore.setPlaylist(musicList.value)
  musicStore.playMusic(musicList.value[0], 0)
  router.push('/player')
}

// 播放单曲
const handlePlay = (music, index) => {
  musicStore.setPlaylist(musicList.value)
  musicStore.playMusic(music, index)
  router.push('/player')
}

// 收藏/取消收藏
const isFavorite = (musicId) => {
  return musicStore.isFavorite(musicId)
}

const handleFavorite = async (music) => {
  const added = await musicStore.toggleFavorite(music)
  showToast(added ? '已添加到我的喜欢' : '已取消喜欢')
}

// 从歌单移除
const handleRemove = async (music) => {
  try {
    await showConfirmDialog({
      title: '确认移除',
      message: `确定要从歌单中移除"${music.name}"吗？`,
    })
    await removeMusicFromPlaylist(route.params.id, music.id)
    showToast('移除成功')
    loadPlaylistDetail()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('移除失败:', error)
      showToast('移除失败')
    }
  }
}

// 操作选择
const onActionSelect = async (action) => {
  showActions.value = false
  if (action.value === 'edit') {
    handleEdit()
  } else if (action.value === 'delete') {
    try {
      await showConfirmDialog({
        title: '确认删除',
        message: `确定要删除歌单"${playlist.value.name}"吗？`,
      })
      await deletePlaylist(route.params.id)
      showToast('删除成功')
      router.back()
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除失败:', error)
        showToast('删除失败')
      }
    }
  }
}

// 编辑歌单
const handleEdit = () => {
  const coverUrl = playlist.value.cover ? getCoverUrl(playlist.value.cover) : ''
  formData.value = {
    name: playlist.value.name,
    description: playlist.value.description || '',
    cover: coverUrl
  }
  coverPreview.value = ''
  uploadedFile.value = null
  showEditDialog.value = true
}

// 触发文件选择
const triggerUpload = () => {
  fileInput.value?.click()
}

// 处理文件选择
const handleFileChange = (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  // 验证文件类型
  if (!file.type.startsWith('image/')) {
    showToast('请选择图片文件')
    return
  }
  
  // 验证文件大小（限制5MB）
  if (file.size > 5 * 1024 * 1024) {
    showToast('图片大小不能超过5MB')
    return
  }
  
  // 保存文件
  uploadedFile.value = file
  
  // 预览图片
  const reader = new FileReader()
  reader.onload = (e) => {
    coverPreview.value = e.target.result
  }
  reader.readAsDataURL(file)
}

// 保存编辑
const handleSaveEdit = async () => {
  if (!formData.value.name.trim()) {
    showToast('请输入歌单名称')
    return
  }

  try {
    // 如果有上传的文件，转换为base64
    let coverData = formData.value.cover
    if (uploadedFile.value) {
      coverData = coverPreview.value
    }
    
    await updatePlaylist({
      id: route.params.id,
      name: formData.value.name,
      description: formData.value.description,
      cover: coverData
    })
    
    showToast('更新成功')
    showEditDialog.value = false
    
    // 重新加载歌单详情
    loadPlaylistDetail()
  } catch (error) {
    console.error('更新失败:', error)
    showToast('更新失败')
  }
}

// 每次页面激活时刷新数据
onActivated(() => {
  loadPlaylistDetail()
})
</script>

<style lang="scss" scoped>
.playlist-detail-page {
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
    
    .back-btn, .more-btn {
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
  }
  
  .playlist-info {
    flex-shrink: 0;
    display: flex;
    gap: 16px;
    padding: 0 16px 20px;
    
    .cover {
      width: 120px;
      height: 120px;
      border-radius: 12px;
      overflow: hidden;
      flex-shrink: 0;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
      
      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }
    
    .info {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      
      .name {
        font-size: 18px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
      }
      
      .desc {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
        margin-bottom: 8px;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
      }
      
      .meta {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.6);
        
        span {
          margin-right: 4px;
        }
      }
    }
  }
  
  .action-bar {
    flex-shrink: 0;
    padding: 0 16px 16px;
    
    :deep(.van-button) {
      height: 44px;
      border-radius: 22px;
      font-size: 15px;
      font-weight: 600;
      
      i {
        margin-right: 6px;
      }
    }
  }
  
  .music-list {
    flex: 1;
    overflow-y: auto;
    padding: 0 16px 20px;
    
    .music-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      margin-bottom: 8px;
      cursor: pointer;
      transition: all 0.3s;
      
      &:active {
        transform: scale(0.98);
      }
      
      .index {
        width: 24px;
        text-align: center;
        font-size: 14px;
        color: rgba(255, 255, 255, 0.6);
        font-weight: 600;
      }
      
      .cover {
        width: 45px;
        height: 45px;
        border-radius: 8px;
        object-fit: cover;
      }
      
      .info {
        flex: 1;
        min-width: 0;
        
        .name {
          font-size: 14px;
          font-weight: 600;
          color: #fff;
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        
        .artist {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.6);
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
      }
      
      .actions {
        display: flex;
        gap: 12px;
        flex-shrink: 0;
        
        .action-icon {
          font-size: 16px;
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
  
  .empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px 20px;
    
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

:deep(.van-popup) {
  background: #fff;
  max-height: 80vh;
}

.edit-popup {
  .popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
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
      transition: all 0.2s;
      
      &:active {
        color: #666;
        transform: scale(0.9);
      }
    }
  }
  
  .popup-content {
    margin-bottom: 24px;
    
    .cover-field {
      display: flex;
      align-items: center;
      padding: 14px 0;
      border-bottom: 1px solid #f0f0f0;
      
      .field-label {
        width: 80px;
        font-size: 14px;
        font-weight: 500;
        color: #666;
        flex-shrink: 0;
      }
      
      .field-value {
        flex: 1;
        display: flex;
        justify-content: flex-end;
        
        .cover-preview {
          position: relative;
          width: 80px;
          height: 80px;
          border-radius: 8px;
          overflow: hidden;
          cursor: pointer;
          
          img {
            width: 100%;
            height: 100%;
            object-fit: cover;
          }
          
          .cover-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            
            i {
              font-size: 20px;
              color: #fff;
            }
          }
          
          &:active .cover-overlay {
            opacity: 1;
          }
        }
        
        .upload-btn {
          width: 80px;
          height: 80px;
          border: 2px dashed #ddd;
          border-radius: 8px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          gap: 4px;
          cursor: pointer;
          transition: all 0.2s;
          
          &:active {
            border-color: #1989fa;
            background: #f5f5f5;
          }
          
          i {
            font-size: 24px;
            color: #999;
          }
          
          span {
            font-size: 11px;
            color: #999;
          }
        }
      }
    }
    
    :deep(.van-cell) {
      padding: 14px 0;
      background: transparent;
      
      &::after {
        border-color: #f0f0f0;
      }
    }
    
    :deep(.van-field__label) {
      width: 80px;
      color: #666;
      font-size: 14px;
      font-weight: 500;
    }
    
    :deep(.van-field__control) {
      color: #333;
      font-size: 14px;
    }
    
    :deep(.van-field__control::placeholder) {
      color: #c8c9cc;
    }
    
    :deep(textarea.van-field__control) {
      min-height: 80px;
    }
  }
  
  .popup-actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    
    :deep(.van-button) {
      height: 46px;
      border-radius: 23px;
      font-size: 15px;
      font-weight: 600;
      transition: all 0.2s;
      
      &:first-child {
        background: #f5f5f5;
        color: #666;
        border: none;
        
        &:active {
          background: #e8e8e8;
        }
      }
      
      &:last-child {
        &:active {
          opacity: 0.8;
        }
      }
    }
  }
}
</style>

<template>
  <div class="my-playlists-page">
    <div class="header">
      <i class="fas fa-chevron-left back-btn" @click="$router.back()"></i>
      <h1 class="title">我的歌单</h1>
      <i class="fas fa-plus add-btn" @click="handleCreate"></i>
    </div>

    <!-- 统计信息 -->
    <div class="stats-bar">
      <div class="stat-item">
        <span class="label">歌单总数</span>
        <span class="value">{{ total }}</span>
      </div>
      <div class="view-toggle">
        <i 
          class="fas fa-th" 
          :class="{ active: viewMode === 'grid' }"
          @click="viewMode = 'grid'"
        ></i>
        <i 
          class="fas fa-list" 
          :class="{ active: viewMode === 'list' }"
          @click="viewMode = 'list'"
        ></i>
      </div>
    </div>

    <!-- 空状态 -->
    <div v-if="playlists.length === 0 && !loading" class="empty">
      <i class="fas fa-list-ul"></i>
      <p>还没有创建歌单</p>
      <van-button type="primary" @click="showCreateDialog = true">新建歌单</van-button>
    </div>

    <!-- 宫格视图 -->
    <div v-else-if="viewMode === 'grid'" class="grid-view">
      <div 
        v-for="playlist in playlists" 
        :key="playlist.id"
        class="playlist-card"
        @click="goToDetail(playlist.id)"
      >
        <div class="cover">
          <img :src="getCoverUrl(playlist.cover) || defaultCover" />
          <div class="play-count">
            <i class="fas fa-play"></i>
            {{ playlist.play_count || 0 }}
          </div>
        </div>
        <div class="info">
          <div class="name">{{ playlist.name }}</div>
          <div class="count">{{ playlist.music_count || 0 }}首</div>
        </div>
        <!-- <div class="actions" @click.stop>
          <i class="fas fa-ellipsis-h" @click="showActions(playlist)"></i>
        </div> -->
      </div>
    </div>

    <!-- 列表视图 -->
    <div v-else class="list-view">
      <div 
        v-for="playlist in playlists" 
        :key="playlist.id"
        class="playlist-item"
        @click="goToDetail(playlist.id)"
      >
        <div class="cover">
          <img :src="getCoverUrl(playlist.cover) || defaultCover" />
        </div>
        <div class="info">
          <div class="name">{{ playlist.name }}</div>
          <div class="desc">{{ playlist.description || '暂无描述' }}</div>
          <div class="meta">
            <span>{{ playlist.music_count || 0 }}首</span>
            <span>·</span>
            <span>播放{{ playlist.play_count || 0 }}次</span>
          </div>
        </div>
        <div class="actions" @click.stop>
          <i class="fas fa-ellipsis-v" @click="showActions(playlist)"></i>
        </div>
      </div>
    </div>

    <!-- 创建/编辑歌单弹窗 -->
    <van-popup
      v-model:show="showCreateDialog"
      position="bottom"
      round
      :style="{ padding: '20px' }"
    >
      <div class="create-popup">
        <div class="popup-header">
          <h3>{{ editingPlaylist ? '编辑歌单' : '新建歌单' }}</h3>
          <i class="fas fa-times" @click="showCreateDialog = false"></i>
        </div>
        
        <div class="popup-content">
          <!-- 封面上传 - 左右布局 -->
          <div class="cover-field">
            <div class="field-label">歌单封面</div>
            <div class="field-value">
              <div class="cover-preview" v-if="formData.cover || coverPreview" @click="triggerUpload">
                <img :src="formData.cover ? getCoverUrl(formData.cover) : getCoverUrl(coverPreview)" />
                <div class="cover-overlay">
                  <i class="fas fa-camera"></i>
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
          <van-button block @click="showCreateDialog = false">取消</van-button>
          <van-button type="primary" block @click="handleSave">确定</van-button>
        </div>
      </div>
    </van-popup>

    <!-- 操作菜单 -->
    <van-action-sheet
      v-model:show="showActionSheet"
      :actions="actions"
      cancel-text="取消"
      @select="onActionSelect"
    />
  </div>
</template>

<script setup>
import { ref, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { showToast, showConfirmDialog } from 'vant'
import { getPlaylistList, createPlaylist, updatePlaylist, deletePlaylist } from '@/api'
import { getCoverUrl } from '@/utils/image'

const router = useRouter()
const playlists = ref([])
const total = ref(0)
const loading = ref(false)
const viewMode = ref('grid') // grid 或 list
const showCreateDialog = ref(false)
const showActionSheet = ref(false)
const editingPlaylist = ref(null)
const currentPlaylist = ref(null)
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
  { name: '编辑', value: 'edit' },
  { name: '删除', value: 'delete', color: '#ee0a24' }
]

// 加载播放列表
const loadPlaylists = async () => {
  try {
    loading.value = true
    const res = await getPlaylistList()
    playlists.value = res.data?.list || []
    total.value = res.data?.total || 0
  } catch (error) {
    console.error('加载歌单失败:', error)
    showToast('加载失败')
  } finally {
    loading.value = false
  }
}

// 显示操作菜单
const showActions = (playlist) => {
  currentPlaylist.value = playlist
  showActionSheet.value = true
}

// 操作选择
const onActionSelect = (action) => {
  showActionSheet.value = false
  if (action.value === 'edit') {
    handleEdit()
  } else if (action.value === 'delete') {
    handleDelete()
  }
}

// 创建新歌单
const handleCreate = () => {
  editingPlaylist.value = null
  formData.value = {
    name: '',
    description: '',
    cover: ''
  }
  coverPreview.value = ''
  uploadedFile.value = null
  showCreateDialog.value = true
}

// 编辑歌单
const handleEdit = () => {
  editingPlaylist.value = currentPlaylist.value
  const coverUrl = currentPlaylist.value.cover ? getCoverUrl(currentPlaylist.value.cover) : ''
  formData.value = {
    name: currentPlaylist.value.name,
    description: currentPlaylist.value.description || '',
    cover: coverUrl
  }
  coverPreview.value = ''
  uploadedFile.value = null
  showCreateDialog.value = true
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

// 删除歌单
const handleDelete = async () => {
  try {
    await showConfirmDialog({
      title: '确认删除',
      message: `确定要删除歌单"${currentPlaylist.value.name}"吗？`,
    })
    await deletePlaylist(currentPlaylist.value.id)
    showToast('删除成功')
    loadPlaylists()
  } catch (error) {
    if (error !== 'cancel') {
      console.error('删除失败:', error)
      showToast('删除失败')
    }
  }
}

// 保存歌单
const handleSave = async () => {
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
    
    const data = {
      name: formData.value.name,
      description: formData.value.description,
      cover: coverData
    }
    
    if (editingPlaylist.value) {
      // 编辑
      await updatePlaylist({
        id: editingPlaylist.value.id,
        ...data
      })
      showToast('更新成功')
    } else {
      // 创建
      await createPlaylist(data)
      showToast('创建成功')
    }
    
    showCreateDialog.value = false
    editingPlaylist.value = null
    formData.value = { name: '', description: '', cover: '' }
    coverPreview.value = ''
    uploadedFile.value = null
    loadPlaylists()
  } catch (error) {
    console.error('保存失败:', error)
    showToast('保存失败')
  }
}

// 跳转到详情
const goToDetail = (id) => {
  router.push(`/playlist/${id}`)
}

// 每次页面加载都执行
onActivated(() => {
  loadPlaylists()
})
</script>

<style lang="scss" scoped>
.my-playlists-page {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  
  .header {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 16px;
    
    .back-btn, .add-btn {
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
  
  .stats-bar {
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    margin: 0 16px 16px;
    border-radius: 12px;
    
    .stat-item {
      display: flex;
      align-items: center;
      gap: 8px;
      
      .label {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
      }
      
      .value {
        font-size: 16px;
        font-weight: 600;
        color: #fff;
      }
    }
    
    .view-toggle {
      display: flex;
      gap: 12px;
      
      i {
        font-size: 18px;
        color: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.2s;
        
        &.active {
          color: #fff;
        }
      }
    }
  }
  
  .grid-view {
    position: absolute;
    top: 140px;
    left: 0;
    right: 0;
    bottom: 0;
    overflow-y: scroll;
    overflow-x: hidden;
    padding: 0 16px 20px;
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    align-content: start;
    -webkit-overflow-scrolling: touch;
    
    .playlist-card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
      
      &:active {
        transform: scale(0.98);
      }
      
      .cover {
        position: relative;
        width: 100%;
        padding-top: 100%;
        
        img {
          position: absolute;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          border-radius: 12px 12px 0 0;
          object-fit: cover;
        }
        
        .play-count {
          position: absolute;
          top: 8px;
          right: 8px;
          display: flex;
          align-items: center;
          gap: 4px;
          padding: 4px 8px;
          background: rgba(0, 0, 0, 0.5);
          border-radius: 12px;
          font-size: 11px;
          color: #fff;
          
          i {
            font-size: 10px;
          }
        }
      }
      
      .info {
        padding: 10px;
        
        .name {
          font-size: 14px;
          font-weight: 600;
          color: #fff;
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        
        .count {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.6);
        }
      }
      
      .actions {
        padding: 0 10px 10px;
        
        i {
          font-size: 16px;
          color: rgba(255, 255, 255, 0.6);
          cursor: pointer;
          padding: 5px;
        }
      }
    }
  }
  
  .list-view {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 0 16px 20px;
    
    .playlist-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 12px;
      margin-bottom: 10px;
      cursor: pointer;
      transition: all 0.3s;
      
      &:active {
        transform: scale(0.98);
      }
      
      .cover {
        width: 60px;
        height: 60px;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
        
        img {
          width: 100%;
          height: 100%;
          object-fit: cover;
        }
      }
      
      .info {
        flex: 1;
        min-width: 0;
        
        .name {
          font-size: 15px;
          font-weight: 600;
          color: #fff;
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        
        .desc {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.6);
          margin-bottom: 4px;
          overflow: hidden;
          text-overflow: ellipsis;
          white-space: nowrap;
        }
        
        .meta {
          font-size: 11px;
          color: rgba(255, 255, 255, 0.5);
          
          span {
            margin-right: 4px;
          }
        }
      }
      
      .actions {
        flex-shrink: 0;
        
        i {
          font-size: 18px;
          color: rgba(255, 255, 255, 0.6);
          cursor: pointer;
          padding: 10px;
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
      margin-bottom: 20px;
    }
  }
}

:deep(.van-popup) {
  background: #fff;
  max-height: 80vh;
}

.create-popup {
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

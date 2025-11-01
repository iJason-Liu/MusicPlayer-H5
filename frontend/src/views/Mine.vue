<template>
  <div class="mine-page">
    <div class="header">
      <div class="user-info">
        <div class="avatar">
          <i class="fas fa-user" v-if="!userStore.isLoggedIn || userStore.userInfo?.avatar == ''"></i>
          <img :src="userStore.userInfo?.avatar" alt="avatar" v-if="userStore.isLoggedIn && userStore.userInfo?.avatar != ''">
        </div>
        <div class="info">
          <div class="name">{{ userStore.userInfo.nickname }}</div>
          <div class="desc">享受音乐，享受生活</div>
        </div>
      </div>
    </div>

    <div class="stats">
      <div class="stat-item">
        <div class="value">{{ musicList.length }}</div>
        <div class="label">歌曲总数</div>
      </div>
      <div class="stat-item">
        <div class="value">{{ formatDuration(totalDuration) }}</div>
        <div class="label">总时长</div>
      </div>
    </div>
    
    <div class="menu-list">
      <div class="menu-item" @click="$router.push('/mine/history')">
        <div class="left">
          <i class="fas fa-history icon"></i>
          <span>播放历史</span>
        </div>
        <div class="right">
          <span class="count">{{ playHistory.length }}</span>
          <i class="fas fa-chevron-right"></i>
        </div>
      </div>
      
      <div class="menu-item" @click="$router.push('/mine/playlist')">
        <div class="left">
          <i class="fas fa-list-ul icon"></i>
          <span>播放列表</span>
        </div>
        <div class="right">
          <span class="count">{{ playlist.length }}</span>
          <i class="fas fa-chevron-right"></i>
        </div>
      </div>
      
      <div class="menu-item" @click="$router.push('/mine/star')">
        <div class="left">
          <i class="fas fa-heart icon"></i>
          <span>我的喜欢</span>
        </div>
        <div class="right">
          <span class="count">{{ favorites.length }}</span>
          <i class="fas fa-chevron-right"></i>
        </div>
      </div>
      
      <div class="menu-item" @click="handleLogout" v-if="userStore.isLoggedIn">
        <div class="left">
          <i class="fas fa-sign-out-alt icon"></i>
          <span>退出登录</span>
        </div>
        <div class="right">
          <i class="fas fa-chevron-right"></i>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useMusicStore } from '@/stores/music'
import { useUserStore } from '@/stores/user'
import { showConfirmDialog, showToast } from 'vant'

const router = useRouter()
const musicStore = useMusicStore()
const userStore = useUserStore()

const playHistory = computed(() => musicStore.playHistory)
const playlist = computed(() => musicStore.playlist)
const favorites = computed(() => musicStore.favorites)
const musicList = computed(() => musicStore.musicList)

const totalDuration = computed(() => {
  return musicList.value.reduce((sum, music) => sum + (music.duration || 0), 0)
})

const formatDuration = (seconds) => {
  const hours = Math.floor(seconds / 3600)
  const mins = Math.floor((seconds % 3600) / 60)
  return hours > 0 ? `${hours}小时${mins}分钟` : `${mins}分钟`
}

const handleLogout = () => {
  showConfirmDialog({
    title: '退出登录',
    message: '确定要退出登录吗？',
  }).then(() => {
    userStore.logout()
    showToast('已退出登录')
    router.replace('/login')
  }).catch(() => {})
}
</script>

<style lang="scss" scoped>
.mine-page {
  height: 100%;
  overflow-y: auto;
  padding: 20px 16px 20px;
  
  &::-webkit-scrollbar {
    width: 4px;
  }
  
  &::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 2px;
  }
  
  .header {
    margin-bottom: 20px;
    
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 16px;
      background: rgba(255, 255, 255, 0.15);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      
      .avatar {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        
        i {
          font-size: 32px;
          color: #fff;
        }
      }
      
      .info {
        flex: 1;
        
        .name {
          font-size: 18px;
          font-weight: 700;
          color: #fff;
          margin-bottom: 6px;
        }
        
        .desc {
          font-size: 12px;
          color: rgba(255, 255, 255, 0.7);
        }
      }
    }
  }
  
  .menu-list {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(20px);
    border-radius: 16px;
    overflow: hidden;
    
    .menu-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 18px 20px;
      cursor: pointer;
      transition: all 0.3s;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
      
      &:last-child {
        border-bottom: none;
      }
      
      &:active {
        background: rgba(255, 255, 255, 0.1);
      }
      
      .left {
        display: flex;
        align-items: center;
        gap: 10px;
        
        .icon {
          font-size: 18px;
          color: #fff;
          width: 24px;
        }
        
        span {
          font-size: 14px;
          color: #fff;
          font-weight: 500;
        }
      }
      
      .right {
        display: flex;
        align-items: center;
        gap: 8px;
        
        .count {
          font-size: 13px;
          color: rgba(255, 255, 255, 0.6);
        }
        
        i {
          font-size: 14px;
          color: rgba(255, 255, 255, 0.4);
        }
      }
    }
  }
  
  .stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 20px;
    
    .stat-item {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(20px);
      border-radius: 16px;
      padding: 20px;
      text-align: center;
      
      .value {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        margin-bottom: 8px;
      }
      
      .label {
        font-size: 13px;
        color: rgba(255, 255, 255, 0.7);
      }
    }
  }
}
</style>

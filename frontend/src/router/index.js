import { createRouter, createWebHashHistory } from 'vue-router'
import { useUserStore } from '@/stores/user'

const routes = [
  {
    path: '/',
    redirect: '/home'
  },
  {
    path: '/home',
    name: 'Home',
    component: () => import('@/views/Home.vue'),
    meta: { title: '音乐库', requiresAuth: true }
  },
  {
    path: '/player',
    name: 'Player',
    component: () => import('@/views/Player.vue'),
    meta: { title: '播放器', requiresAuth: false }  // 不设置 requiresAuth 分享功能需要使用
  },
  {
    path: '/music/:id',
    name: 'MusicDetail',
    component: () => import('@/views/MusicDetail.vue'),
    meta: { title: '歌曲详情', requiresAuth: true }
  },
  {
    path: '/mine',
    name: 'Mine',
    component: () => import('@/views/Mine.vue'),
    meta: { title: '我的', requiresAuth: true }
  },
  {
    path: '/mine/history',
    name: 'History',
    component: () => import('@/views/History.vue'),
    meta: { title: '播放历史', requiresAuth: true }
  },
  {
    path: '/mine/playlist',
    name: 'Playlist',
    component: () => import('@/views/Playlist.vue'),
    meta: { title: '播放列表', requiresAuth: true }
  },
  {
    path: '/mine/playlists',
    name: 'MyPlaylists',
    component: () => import('@/views/MyPlaylists.vue'),
    meta: { title: '我的歌单', requiresAuth: true }
  },
  {
    path: '/playlist/:id',
    name: 'PlaylistDetail',
    component: () => import('@/views/PlaylistDetail.vue'),
    meta: { title: '歌单详情', requiresAuth: true }
  },
  {
    path: '/mine/star',
    name: 'Star',
    component: () => import('@/views/Star.vue'),
    meta: { title: '我的喜欢', requiresAuth: true }
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/Login.vue'),
    meta: { title: '登录', requiresAuth: false }
  }
]

const router = createRouter({
  history: createWebHashHistory(),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  console.log('路由跳转:', from.path, '->', to.path)
  
  // 设置页面标题
  document.title = to.meta.title || '音乐播放器'
  
  // 检查是否需要登录
  if (to.meta.requiresAuth) {
    const userStore = useUserStore()
    if (!userStore.isLoggedIn) {
      next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
    } else {
      next()
    }
  } else {
    console.log('无需登录，允许访问')
    next()
  }
})

export default router

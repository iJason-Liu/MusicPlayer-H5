import { createRouter, createWebHistory } from 'vue-router'
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
    meta: { title: '播放器', requiresAuth: true }
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
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
})

// 路由守卫
router.beforeEach((to, from, next) => {
  // 设置页面标题
  document.title = to.meta.title || '音乐播放器'
  
  // 检查是否需要登录
  if (to.meta.requiresAuth) {
    const userStore = useUserStore()
    
    // 如果需要登录但未登录（且非开发模式）
    if (userStore.needLogin()) {
      // 保存原始目标路径
      next({
        path: '/login',
        query: { redirect: to.fullPath }
      })
    } else {
      next()
    }
  } else {
    next()
  }
})

export default router

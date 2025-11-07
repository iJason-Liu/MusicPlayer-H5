import { createApp } from 'vue'
import App from './App.vue'
import { createPinia } from 'pinia'
import { App as CapacitorApp } from '@capacitor/app';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';
import router from './router'

// Vant 组件
import { Button, Icon, Slider, Popup, List, Search, Tab, Tabs, Tabbar, TabbarItem, Dialog, Field, ActionSheet } from 'vant'
import 'vant/lib/index.css'

// Font Awesome
import '@fortawesome/fontawesome-free/css/all.min.css'

// 全局样式
import './styles/index.scss'

// 定义全局域名
const imgPath = 'https://diary.crayon.vip'

// 调试信息
console.log('=== 音乐播放器启动 ===')
console.log('环境:', import.meta.env.MODE)
console.log('Base URL:', import.meta.env.BASE_URL)
console.log('API URL:', import.meta.env.VITE_API_BASE_URL)
console.log('开发模式:', import.meta.env.DEV)

const app = createApp(App)
const pinia = createPinia()

// 注册插件
app.use(pinia)
app.use(router)
app.use(Button)
app.use(Icon)
app.use(Slider)
app.use(Popup)
app.use(List)
app.use(Search)
app.use(Tab)
app.use(Tabs)
app.use(Tabbar)
app.use(TabbarItem)
app.use(Dialog)
app.use(Field)
app.use(ActionSheet)

app.provide('imgPath', imgPath)

// Capacitor 初始化
const initCapacitor = async () => {
  try {
    await StatusBar.setStyle({ style: Style.Dark });
    await StatusBar.setBackgroundColor({ color: '#5e72e4' });
    console.log('✅ StatusBar initialized');
  } catch (e) {
    console.log('StatusBar not available:', e.message);
  }

  try {
    await SplashScreen.hide();
    console.log('✅ SplashScreen hidden');
  } catch (e) {
    console.log('SplashScreen not available:', e.message);
  }

  try {
    CapacitorApp.addListener('backButton', ({ canGoBack }) => {
      if (!canGoBack) {
        CapacitorApp.exitApp();
      } else {
        window.history.back();
      }
    });
    console.log('✅ BackButton listener added');
  } catch (e) {
    console.log('BackButton not available:', e.message);
  }
};

// 等待路由准备好后挂载应用
router.isReady().then(() => {
  app.mount('#app')
  console.log('✅ 应用已挂载')
  
  // 初始化 Capacitor（仅在原生平台）
  initCapacitor()
})

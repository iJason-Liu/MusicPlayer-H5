import { createApp } from 'vue'
import App from './App.vue'
import { createPinia } from 'pinia'
import router from './router'

// Vant 组件
import { Button, Icon, Slider, Popup, List, Search, Tab, Tabs, Tabbar, TabbarItem } from 'vant'
import 'vant/lib/index.css'

// Font Awesome
import '@fortawesome/fontawesome-free/css/all.min.css'

// 全局样式
import './styles/index.scss'

// 调试信息
console.log('=== 音乐播放器启动 ===')
console.log('环境:', import.meta.env.MODE)
console.log('Base URL:', import.meta.env.BASE_URL)
console.log('API URL:', import.meta.env.VITE_API_BASE_URL)
console.log('开发模式:', import.meta.env.DEV)

const app = createApp(App)
const pinia = createPinia()

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

app.mount('#app')

console.log('✅ 应用已挂载')

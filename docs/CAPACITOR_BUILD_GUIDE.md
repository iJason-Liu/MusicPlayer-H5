# Capacitor Android 打包配置文档

## 📋 目录

- [环境准备](#环境准备)
- [项目初始化](#项目初始化)
- [配置文件](#配置文件)
- [插件安装](#插件安装)
- [代码适配](#代码适配)
- [构建打包](#构建打包)
- [签名发布](#签名发布)
- [常见问题](#常见问题)

---

## 🔧 环境准备

### 1. 安装 Node.js
确保已安装 Node.js 16+ 版本：
```bash
node -v  # 应该显示 v16.0.0 或更高
npm -v
```

### 2. 安装 JDK
下载并安装 JDK 11 或 JDK 17（推荐）：
- 下载地址：https://adoptium.net/
- 配置环境变量 `JAVA_HOME`

验证安装：
```bash
java -version
```

### 3. 安装 Android Studio
- 下载地址：https://developer.android.com/studio
- 安装时选择 Android SDK、Android SDK Platform 和 Android Virtual Device

### 4. 配置 Android SDK 环境变量

**macOS/Linux:**
```bash
# 编辑 ~/.zshrc 或 ~/.bash_profile
export ANDROID_HOME=$HOME/Library/Android/sdk
export PATH=$PATH:$ANDROID_HOME/emulator
export PATH=$PATH:$ANDROID_HOME/platform-tools
export PATH=$PATH:$ANDROID_HOME/tools
export PATH=$PATH:$ANDROID_HOME/tools/bin

# 使配置生效
source ~/.zshrc
```

**Windows:**
```
ANDROID_HOME=C:\Users\你的用户名\AppData\Local\Android\Sdk
Path=%ANDROID_HOME%\platform-tools;%ANDROID_HOME%\tools
```

验证安装：
```bash
adb version
```

---

## 🚀 项目初始化

### 1. 进入前端项目目录
```bash
cd frontend
```

### 2. 安装 Capacitor 核心依赖
```bash
npm install @capacitor/core @capacitor/cli
```

### 3. 初始化 Capacitor
```bash
npx cap init
```

按提示输入以下信息：
- **App name**: `Music Player` （应用名称）
- **App ID**: `com.yourname.musicplayer` （包名，使用反向域名格式）
- **Web asset directory**: `h5` （与 vite.config.js 的 outDir 一致）

### 4. 添加 Android 平台
```bash
npm install @capacitor/android
npx cap add android
```

执行后会在项目根目录生成 `android` 文件夹。

---

## ⚙️ 配置文件

### 1. 创建 `capacitor.config.json`

在 `frontend` 目录下创建或修改 `capacitor.config.json`：

```json
{
  "appId": "com.yourname.musicplayer",
  "appName": "Music Player",
  "webDir": "h5",
  "server": {
    "androidScheme": "https",
    "cleartext": true,
    "allowNavigation": [
      "diary.crayon.vip"
    ]
  },
  "plugins": {
    "SplashScreen": {
      "launchShowDuration": 2000,
      "launchAutoHide": true,
      "backgroundColor": "#5e72e4",
      "androidScaleType": "CENTER_CROP",
      "showSpinner": false
    },
    "StatusBar": {
      "style": "DARK",
      "backgroundColor": "#5e72e4"
    }
  }
}
```

### 2. 修改 `package.json` 添加快捷脚本

在 `frontend/package.json` 的 `scripts` 中添加：

```json
{
  "scripts": {
    "dev": "vite",
    "build": "vite build",
    "preview": "vite preview",
    "cap:init": "npx cap init",
    "cap:add:android": "npx cap add android",
    "cap:sync": "npm run build && npx cap sync android",
    "cap:copy": "npm run build && npx cap copy android",
    "cap:open": "npx cap open android",
    "cap:build": "npm run build && npx cap sync android && npx cap open android"
  }
}
```

### 3. 确认 `vite.config.js` 配置

确保 `base` 设置为相对路径（你的配置已正确）：

```javascript
export default defineConfig({
  base: './',  // ✅ 必须是相对路径
  build: {
    outDir: "h5",  // ✅ 与 capacitor.config.json 的 webDir 一致
  }
})
```

---

## 🔌 插件安装

### 1. 基础插件
```bash
# 状态栏和启动屏
npm install @capacitor/status-bar @capacitor/splash-screen

# 应用生命周期
npm install @capacitor/app

# 网络状态
npm install @capacitor/network

# 本地存储（替代 localStorage）
npm install @capacitor/preferences
```

### 2. 音乐播放相关插件
```bash
# 后台模式（保持应用在后台运行）
npm install @capacitor-community/background-mode

# 媒体会话（通知栏控制）
npm install @capacitor-community/media-session

# 音频焦点管理
npm install capacitor-plugin-audio-focus
```

### 3. 可选插件
```bash
# 文件系统
npm install @capacitor/filesystem

# 分享功能
npm install @capacitor/share

# 设备信息
npm install @capacitor/device
```

---

## 💻 代码适配

### 1. 修改 API 请求配置

由于打包后无法使用 Vite 的 proxy，需要修改 API 请求地址。

**方式一：环境变量（推荐）**

创建 `frontend/.env.production`：
```env
VITE_API_BASE_URL=https://diary.crayon.vip
```

修改 `frontend/src/api/index.js`：
```javascript
import axios from 'axios';

const baseURL = import.meta.env.VITE_API_BASE_URL || '';

const request = axios.create({
  baseURL: baseURL,
  timeout: 10000,
});

// 请求拦截器
request.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

export default request;
```

**方式二：直接修改**

如果 API 地址固定，可以直接在 `axios.create` 中设置：
```javascript
const request = axios.create({
  baseURL: 'https://diary.crayon.vip',
  timeout: 10000,
});
```

### 2. 添加 Capacitor 初始化代码

修改 `frontend/src/main.js`：

```javascript
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import { App as CapacitorApp } from '@capacitor/app';
import { StatusBar, Style } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';

// Vant 组件库
import 'vant/lib/index.css';

// FontAwesome 图标
import '@fortawesome/fontawesome-free/css/all.min.css';

const app = createApp(App);
const pinia = createPinia();

app.use(router);
app.use(pinia);

// Capacitor 初始化
const initCapacitor = async () => {
  // 设置状态栏样式
  try {
    await StatusBar.setStyle({ style: Style.Dark });
    await StatusBar.setBackgroundColor({ color: '#5e72e4' });
  } catch (e) {
    console.log('StatusBar not available');
  }

  // 隐藏启动屏
  try {
    await SplashScreen.hide();
  } catch (e) {
    console.log('SplashScreen not available');
  }

  // 监听返回按钮
  CapacitorApp.addListener('backButton', ({ canGoBack }) => {
    if (!canGoBack) {
      CapacitorApp.exitApp();
    } else {
      window.history.back();
    }
  });
};

// 等待路由准备好后初始化
router.isReady().then(() => {
  app.mount('#app');
  initCapacitor();
});
```

### 3. 添加后台音频播放支持

创建 `frontend/src/utils/capacitor-audio.js`：

```javascript
import { Capacitor } from '@capacitor/core';

export const setupBackgroundAudio = async () => {
  if (Capacitor.isNativePlatform()) {
    try {
      // 动态导入插件（避免 Web 端报错）
      const { BackgroundMode } = await import('@capacitor-community/background-mode');
      
      // 启用后台模式
      await BackgroundMode.enable();
      
      console.log('Background mode enabled');
    } catch (e) {
      console.log('Background mode not available:', e);
    }
  }
};

export const setupMediaSession = async (music) => {
  if (Capacitor.isNativePlatform()) {
    try {
      const { MediaSession } = await import('@capacitor-community/media-session');
      
      await MediaSession.setMetadata({
        title: music.name,
        artist: music.artist,
        album: music.album || '',
        artwork: [
          { src: music.cover, sizes: '512x512', type: 'image/jpeg' }
        ]
      });
      
      console.log('Media session updated');
    } catch (e) {
      console.log('Media session not available:', e);
    }
  }
};
```

在音乐播放器中使用：
```javascript
import { setupBackgroundAudio, setupMediaSession } from '@/utils/capacitor-audio';

// 在组件挂载时
onMounted(() => {
  setupBackgroundAudio();
});

// 在播放音乐时
watch(currentMusic, (music) => {
  if (music) {
    setupMediaSession(music);
  }
});
```

### 4. 使用 Capacitor Preferences 替代 localStorage

创建 `frontend/src/utils/storage.js`：

```javascript
import { Capacitor } from '@capacitor/core';
import { Preferences } from '@capacitor/preferences';

export const storage = {
  async setItem(key, value) {
    if (Capacitor.isNativePlatform()) {
      await Preferences.set({ key, value: JSON.stringify(value) });
    } else {
      localStorage.setItem(key, JSON.stringify(value));
    }
  },

  async getItem(key) {
    if (Capacitor.isNativePlatform()) {
      const { value } = await Preferences.get({ key });
      return value ? JSON.parse(value) : null;
    } else {
      const value = localStorage.getItem(key);
      return value ? JSON.parse(value) : null;
    }
  },

  async removeItem(key) {
    if (Capacitor.isNativePlatform()) {
      await Preferences.remove({ key });
    } else {
      localStorage.removeItem(key);
    }
  },

  async clear() {
    if (Capacitor.isNativePlatform()) {
      await Preferences.clear();
    } else {
      localStorage.clear();
    }
  }
};
```

---

## 🔨 构建打包

### 1. 构建前端项目
```bash
cd frontend
npm run build
```

构建完成后，会在 `frontend/h5` 目录生成打包文件。

### 2. 同步到 Android 项目
```bash
npx cap sync android
```

或者使用快捷命令：
```bash
npm run cap:sync
```

这个命令会：
- 复制 `h5` 目录到 Android 项目
- 更新 Capacitor 配置
- 安装原生插件

### 3. 打开 Android Studio
```bash
npx cap open android
```

或者使用快捷命令：
```bash
npm run cap:open
```

### 4. 在 Android Studio 中配置

#### 4.1 配置权限

打开 `android/app/src/main/AndroidManifest.xml`，添加必要权限：

```xml
<?xml version="1.0" encoding="utf-8"?>
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <application
        android:allowBackup="true"
        android:icon="@mipmap/ic_launcher"
        android:label="@string/app_name"
        android:roundIcon="@mipmap/ic_launcher_round"
        android:supportsRtl="true"
        android:theme="@style/AppTheme"
        android:usesCleartextTraffic="true">

        <activity
            android:configChanges="orientation|keyboardHidden|keyboard|screenSize|locale|smallestScreenSize|screenLayout|uiMode"
            android:name=".MainActivity"
            android:label="@string/title_activity_main"
            android:theme="@style/AppTheme.NoActionBarLaunch"
            android:launchMode="singleTask"
            android:exported="true">

            <intent-filter>
                <action android:name="android.intent.action.MAIN" />
                <category android:name="android.intent.category.LAUNCHER" />
            </intent-filter>

        </activity>
    </application>

    <!-- 网络权限 -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />
    
    <!-- 音频播放权限 -->
    <uses-permission android:name="android.permission.WAKE_LOCK" />
    <uses-permission android:name="android.permission.FOREGROUND_SERVICE" />
    <uses-permission android:name="android.permission.MODIFY_AUDIO_SETTINGS" />
    
    <!-- 存储权限（如果需要下载音乐） -->
    <uses-permission android:name="android.permission.READ_EXTERNAL_STORAGE" />
    <uses-permission android:name="android.permission.WRITE_EXTERNAL_STORAGE" 
        android:maxSdkVersion="32" />

</manifest>
```

#### 4.2 配置应用信息

修改 `android/app/src/main/res/values/strings.xml`：

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <string name="app_name">Music Player</string>
    <string name="title_activity_main">Music Player</string>
    <string name="package_name">com.yourname.musicplayer</string>
    <string name="custom_url_scheme">com.yourname.musicplayer</string>
</resources>
```

#### 4.3 配置网络安全

创建 `android/app/src/main/res/xml/network_security_config.xml`：

```xml
<?xml version="1.0" encoding="utf-8"?>
<network-security-config>
    <domain-config cleartextTrafficPermitted="true">
        <domain includeSubdomains="true">diary.crayon.vip</domain>
        <domain includeSubdomains="true">localhost</domain>
    </domain-config>
</network-security-config>
```

在 `AndroidManifest.xml` 中引用：
```xml
<application
    android:networkSecurityConfig="@xml/network_security_config"
    ...>
```

### 5. 运行调试版本

在 Android Studio 中：
1. 连接 Android 设备（开启 USB 调试）或启动模拟器
2. 等待 Gradle 同步完成
3. 点击工具栏的 Run 按钮（绿色三角形）
4. 选择目标设备
5. 等待应用安装并启动

或者使用命令行：
```bash
cd android
./gradlew assembleDebug

# APK 位置：android/app/build/outputs/apk/debug/app-debug.apk
```

---

## 🔐 签名发布

### 1. 生成签名密钥

```bash
# 进入 android/app 目录
cd android/app

# 生成密钥库
keytool -genkey -v -keystore music-player-release.keystore -alias music-player -keyalg RSA -keysize 2048 -validity 10000

# 按提示输入信息：
# - 密钥库密码（记住这个密码）
# - 姓名、组织等信息
# - 密钥密码（可以与密钥库密码相同）
```

### 2. 配置签名

创建 `android/key.properties`（不要提交到 Git）：

```properties
storePassword=你的密钥库密码
keyPassword=你的密钥密码
keyAlias=music-player
storeFile=app/music-player-release.keystore
```

修改 `android/app/build.gradle`：

```gradle
android {
    ...
    
    // 在 android 块中添加签名配置
    def keystorePropertiesFile = rootProject.file("key.properties")
    def keystoreProperties = new Properties()
    if (keystorePropertiesFile.exists()) {
        keystoreProperties.load(new FileInputStream(keystorePropertiesFile))
    }

    signingConfigs {
        release {
            if (keystorePropertiesFile.exists()) {
                keyAlias keystoreProperties['keyAlias']
                keyPassword keystoreProperties['keyPassword']
                storeFile file(keystoreProperties['storeFile'])
                storePassword keystoreProperties['storePassword']
            }
        }
    }

    buildTypes {
        release {
            signingConfig signingConfigs.release
            minifyEnabled false
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}
```

### 3. 构建 Release APK

**方式一：Android Studio**
1. 菜单：Build > Generate Signed Bundle / APK
2. 选择 APK
3. 选择密钥库文件和输入密码
4. 选择 release 构建类型
5. 点击 Finish

**方式二：命令行**
```bash
cd android
./gradlew assembleRelease

# APK 位置：android/app/build/outputs/apk/release/app-release.apk
```

### 4. 构建 AAB（Google Play 上架）

```bash
cd android
./gradlew bundleRelease

# AAB 位置：android/app/build/outputs/bundle/release/app-release.aab
```

### 5. 优化 APK 大小

在 `android/app/build.gradle` 中添加：

```gradle
android {
    ...
    
    buildTypes {
        release {
            ...
            // 启用代码压缩
            minifyEnabled true
            // 启用资源压缩
            shrinkResources true
            proguardFiles getDefaultProguardFile('proguard-android-optimize.txt'), 'proguard-rules.pro'
        }
    }
    
    // 分包配置（如果 APK 过大）
    splits {
        abi {
            enable true
            reset()
            include 'armeabi-v7a', 'arm64-v8a', 'x86', 'x86_64'
            universalApk true
        }
    }
}
```

---

## 🎨 自定义图标和启动屏

### 1. 准备图标资源

需要准备以下尺寸的图标（PNG 格式）：
- `icon-ldpi.png` - 36x36
- `icon-mdpi.png` - 48x48
- `icon-hdpi.png` - 72x72
- `icon-xhdpi.png` - 96x96
- `icon-xxhdpi.png` - 144x144
- `icon-xxxhdpi.png` - 192x192

或者使用在线工具生成：
- https://icon.kitchen/
- https://romannurik.github.io/AndroidAssetStudio/

### 2. 替换图标

将生成的图标文件放到：
```
android/app/src/main/res/
├── mipmap-ldpi/ic_launcher.png
├── mipmap-mdpi/ic_launcher.png
├── mipmap-hdpi/ic_launcher.png
├── mipmap-xhdpi/ic_launcher.png
├── mipmap-xxhdpi/ic_launcher.png
└── mipmap-xxxhdpi/ic_launcher.png
```

### 3. 配置启动屏

创建 `android/app/src/main/res/drawable/splash.xml`：

```xml
<?xml version="1.0" encoding="utf-8"?>
<layer-list xmlns:android="http://schemas.android.com/apk/res/android">
    <item android:drawable="@color/splash_background"/>
    <item>
        <bitmap
            android:gravity="center"
            android:src="@drawable/splash_icon"/>
    </item>
</layer-list>
```

在 `android/app/src/main/res/values/colors.xml` 中定义颜色：

```xml
<?xml version="1.0" encoding="utf-8"?>
<resources>
    <color name="splash_background">#5e72e4</color>
</resources>
```

将启动图标放到 `android/app/src/main/res/drawable/splash_icon.png`

---

## 🐛 常见问题

### 1. Gradle 同步失败

**问题**：Android Studio 提示 Gradle sync failed

**解决**：
```bash
# 清理 Gradle 缓存
cd android
./gradlew clean

# 或者删除 .gradle 文件夹
rm -rf .gradle
```

### 2. 网络请求失败

**问题**：APP 中 API 请求返回 Network Error

**解决**：
- 检查 `AndroidManifest.xml` 中是否添加了 `INTERNET` 权限
- 检查 `capacitor.config.json` 中的 `cleartext: true` 配置
- 检查 `network_security_config.xml` 是否正确配置

### 3. 音频无法后台播放

**问题**：切换到后台后音乐停止

**解决**：
- 安装 `@capacitor-community/background-mode` 插件
- 在 `AndroidManifest.xml` 中添加 `WAKE_LOCK` 和 `FOREGROUND_SERVICE` 权限
- 使用原生音频播放器而不是 HTML5 Audio

### 4. 白屏问题

**问题**：打开 APP 显示白屏

**解决**：
- 检查 `vite.config.js` 中 `base: './'` 是否配置正确
- 检查 `capacitor.config.json` 中 `webDir` 是否与构建输出目录一致
- 在 Chrome 中打开 `chrome://inspect` 查看 WebView 控制台错误

### 5. 图片加载失败

**问题**：APP 中图片无法显示

**解决**：
- 确保图片 URL 使用完整的 HTTPS 地址
- 检查图片服务器的 CORS 配置
- 使用 Capacitor 的 Filesystem API 缓存图片

### 6. 返回键无响应

**问题**：按返回键无法返回上一页

**解决**：
在 `main.js` 中添加返回键监听：
```javascript
import { App } from '@capacitor/app';

App.addListener('backButton', ({ canGoBack }) => {
  if (!canGoBack) {
    App.exitApp();
  } else {
    window.history.back();
  }
});
```

### 7. 构建速度慢

**解决**：
```bash
# 使用国内镜像
cd android
# 修改 build.gradle 中的仓库地址为阿里云镜像
```

在 `android/build.gradle` 中：
```gradle
allprojects {
    repositories {
        maven { url 'https://maven.aliyun.com/repository/google' }
        maven { url 'https://maven.aliyun.com/repository/public' }
        google()
        mavenCentral()
    }
}
```

---

## 📝 完整构建流程总结

```bash
# 1. 安装依赖
cd frontend
npm install

# 2. 安装 Capacitor
npm install @capacitor/core @capacitor/cli @capacitor/android

# 3. 初始化 Capacitor
npx cap init

# 4. 添加 Android 平台
npx cap add android

# 5. 安装插件
npm install @capacitor/status-bar @capacitor/splash-screen @capacitor/app

# 6. 构建前端
npm run build

# 7. 同步到 Android
npx cap sync android

# 8. 打开 Android Studio
npx cap open android

# 9. 在 Android Studio 中运行或构建 APK
```

---

## 🔄 日常开发流程

每次修改代码后：

```bash
# 1. 构建前端
npm run build

# 2. 同步到 Android（只复制文件，不更新插件）
npx cap copy android

# 3. 刷新 APP（如果已经在运行）
# 在 Android Studio 中点击 Run 按钮
```

如果添加了新插件：

```bash
# 完整同步（包括插件）
npx cap sync android
```

---

## 📚 参考资源

- [Capacitor 官方文档](https://capacitorjs.com/docs)
- [Capacitor Android 配置](https://capacitorjs.com/docs/android/configuration)
- [Capacitor 插件市场](https://capacitorjs.com/docs/plugins)
- [Android 开发者文档](https://developer.android.com/docs)

---

## 📞 技术支持

如遇到问题，可以：
1. 查看 Capacitor 官方文档
2. 在 GitHub Issues 中搜索类似问题
3. 使用 `chrome://inspect` 调试 WebView
4. 查看 Android Studio 的 Logcat 日志

---

**最后更新时间**: 2025-11-07

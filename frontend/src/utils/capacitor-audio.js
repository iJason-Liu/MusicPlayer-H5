import { Capacitor } from '@capacitor/core';

export const setupBackgroundAudio = async () => {
  if (Capacitor.isNativePlatform()) {
    try {
      // 动态导入插件（避免 Web 端报错）
      // const { BackgroundMode } = await import('@capacitor-community/background-mode');
      
      // 启用后台模式
      // await BackgroundMode.enable();
      
      console.log('Background mode enabled');
    } catch (e) {
      console.log('Background mode not available:', e);
    }
  }
};

export const setupMediaSession = async (music) => {
  if (Capacitor.isNativePlatform()) {
    try {
      // 暂时注释，等安装插件后再启用
      // const { MediaSession } = await import('@capacitor-community/media-session');
      
      // await MediaSession.setMetadata({
      //   title: music.name,
      //   artist: music.artist,
      //   album: music.album || '',
      //   artwork: [
      //     { src: music.cover, sizes: '512x512', type: 'image/jpeg' }
      //   ]
      // });
      
      console.log('Media session not installed yet');
    } catch (e) {
      console.log('Media session not available:', e);
    }
  }
};
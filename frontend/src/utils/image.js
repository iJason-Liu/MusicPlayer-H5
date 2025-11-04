/**
 * 图片URL处理工具
 */

const IMG_BASE_URL = 'https://diary.crayon.vip'
const MUSIC_BASE_URL = 'https://diary.crayon.vip/Music'
const DEFAULT_COVER = '/storage/20251103/dxP1762151275usfDcB1.png'

/**
 * 获取完整的图片URL
 * @param {string} path - 图片路径（可以是相对路径或完整URL）
 * @param {string} defaultPath - 默认图片路径
 * @returns {string} 完整的图片URL
 */
export function getImageUrl(path, defaultPath = DEFAULT_COVER) {
  // 如果路径为空，返回默认图片
  if (!path) {
    return IMG_BASE_URL + defaultPath
  }
  
  // 如果已经是完整URL，直接返回
  if (path.startsWith('http://') || path.startsWith('https://')) {
    return path
  }
  
  // 如果是相对路径，拼接基础URL
  // 确保路径以 / 开头
  const normalizedPath = path.startsWith('/') ? path : '/' + path
  return IMG_BASE_URL + normalizedPath
}

/**
 * 获取封面图片URL
 * @param {string} cover - 封面路径
 * @returns {string} 完整的封面URL
 */
export function getCoverUrl(cover) {
  // 如果路径为空，返回默认图片
  if (!cover) {
    return IMG_BASE_URL + DEFAULT_COVER
  }
  
  // 如果已经是完整URL，直接返回
  if (cover.startsWith('http://') || cover.startsWith('https://')) {
    return cover
  }
  
  // 封面存储在 Music 目录下，使用 diary 域名访问
  // 路径格式：covers/202511/xxx.jpg
  const normalizedPath = cover.startsWith('/') ? cover : '/' + cover
  return MUSIC_BASE_URL + normalizedPath
}

/**
 * 获取头像URL
 * @param {string} avatar - 头像路径
 * @returns {string} 完整的头像URL
 */
export function getAvatarUrl(avatar) {
  return getImageUrl(avatar, '/default-avatar.png')
}

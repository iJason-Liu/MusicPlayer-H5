<?php

namespace app\common\service;

use think\facade\Filesystem;

/**
 * 音乐信息识别服务
 * 使用 getID3 库提取音乐文件的元数据和封面
 */
class MusicInfoService
{
    /**
     * 提取音乐文件信息
     * @param string $filePath 音乐文件路径（相对路径或文件名）
     * @return array|false
     */
    public static function extractMusicInfo($filePath)
    {
        try {
            // 音乐文件统一存放在 /www/wwwroot/Music 目录
            $musicBaseDir = '/www/wwwroot/Music/';
            
            // 构建完整路径
            $fullPath = $musicBaseDir . $filePath;
            
            trace('开始提取音乐信息: ' . $fullPath, 'info');
            
            if (!file_exists($fullPath)) {
                trace('文件不存在: ' . $fullPath, 'error');
                return false;
            }
            
            // 检查 getID3 是否已安装
            trace('检查 getID3 类是否存在', 'info');
            
            if (!class_exists('getID3')) {
                trace('getID3 类不存在，尝试手动加载', 'info');
                
                // 如果没有通过 composer 安装，尝试手动加载
                $getid3Path = root_path('vendor/james-heinrich/getid3/getid3/getid3.php');
                trace('getID3 路径: ' . $getid3Path, 'info');
                
                if (file_exists($getid3Path)) {
                    require_once $getid3Path;
                    trace('getID3 手动加载成功', 'info');
                } else {
                    trace('getID3 文件不存在: ' . $getid3Path, 'error');
                    throw new \Exception('getID3 库未安装，路径: ' . $getid3Path);
                }
            } else {
                trace('getID3 类已存在', 'info');
            }
            
            // 初始化 getID3
            trace('初始化 getID3', 'info');
            $getID3 = new \getID3();
            $getID3->setOption(['encoding' => 'UTF-8']);
            trace('getID3 初始化成功', 'info');
            
            // 分析文件
            $fileInfo = $getID3->analyze($fullPath);
            
            // 提取基本信息
            $info = [
                'name' => '',
                'artist' => '',
                'album' => '',
                'duration' => 0,
                'format' => '',
                'bitrate' => 0,
                'cover' => null,
            ];
            
            // 歌曲名称
            if (isset($fileInfo['tags']['id3v2']['title'][0])) {
                $info['name'] = $fileInfo['tags']['id3v2']['title'][0];
            } elseif (isset($fileInfo['tags']['id3v1']['title'][0])) {
                $info['name'] = $fileInfo['tags']['id3v1']['title'][0];
            }
            
            // 艺术家
            if (isset($fileInfo['tags']['id3v2']['artist'][0])) {
                $info['artist'] = $fileInfo['tags']['id3v2']['artist'][0];
            } elseif (isset($fileInfo['tags']['id3v1']['artist'][0])) {
                $info['artist'] = $fileInfo['tags']['id3v1']['artist'][0];
            }
            
            // 专辑
            if (isset($fileInfo['tags']['id3v2']['album'][0])) {
                $info['album'] = $fileInfo['tags']['id3v2']['album'][0];
            } elseif (isset($fileInfo['tags']['id3v1']['album'][0])) {
                $info['album'] = $fileInfo['tags']['id3v1']['album'][0];
            }
            
            // 时长（秒）
            if (isset($fileInfo['playtime_seconds'])) {
                $info['duration'] = (int)$fileInfo['playtime_seconds'];
            }
            
            // 格式
            if (isset($fileInfo['fileformat'])) {
                $info['format'] = $fileInfo['fileformat'];
            }
            
            // 比特率
            if (isset($fileInfo['audio']['bitrate'])) {
                $info['bitrate'] = (int)$fileInfo['audio']['bitrate'];
            }
            
            // 提取歌词
            $lyric = self::extractLyric($fileInfo);
            if ($lyric) {
                $info['lyric'] = $lyric;
            }
            
            // 提取封面
            $coverData = self::extractCover($fileInfo);
            if ($coverData) {
                $info['cover'] = $coverData;
            }
            
            return $info;
            
        } catch (\Exception $e) {
            trace('提取音乐信息失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 提取封面图片
     * @param array $fileInfo getID3 分析结果
     * @return array|null ['data' => 图片二进制数据, 'mime' => MIME类型, 'ext' => 扩展名]
     */
    private static function extractCover($fileInfo)
    {
        try {
            // 尝试从 ID3v2 标签获取封面
            if (isset($fileInfo['comments']['picture'][0])) {
                $picture = $fileInfo['comments']['picture'][0];
                
                return [
                    'data' => $picture['data'],
                    'mime' => $picture['image_mime'] ?? 'image/jpeg',
                    'ext' => self::getMimeExtension($picture['image_mime'] ?? 'image/jpeg'),
                ];
            }
            
            // 尝试从 APE 标签获取封面
            if (isset($fileInfo['ape']['items']['Cover Art (Front)'])) {
                $picture = $fileInfo['ape']['items']['Cover Art (Front)'];
                
                return [
                    'data' => $picture['data'],
                    'mime' => 'image/jpeg',
                    'ext' => 'jpg',
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            trace('提取封面失败: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * 保存封面图片到服务器
     * @param array $coverData 封面数据
     * @param int $musicId 音乐ID
     * @return string|false 返回保存的相对路径
     */
    public static function saveCover($coverData, $musicId)
    {
        try {
            if (!$coverData || !isset($coverData['data'])) {
                return false;
            }
            
            // 生成文件名
            $ext = $coverData['ext'] ?? 'jpg';
            $filename = 'cover_' . $musicId . '_' . time() . '.' . $ext;
            
            // 保存到 Music 目录下的 covers 子目录
            $saveDir = '/www/wwwroot/Music/covers/' . date('Ym');
            
            // 创建目录
            if (!is_dir($saveDir)) {
                mkdir($saveDir, 0755, true);
            }
            
            // 保存文件
            $savePath = $saveDir . '/' . $filename;
            if (file_put_contents($savePath, $coverData['data'])) {
                // 返回相对于 Music 目录的路径
                return 'covers/' . date('Ym') . '/' . $filename;
            }
            
            return false;
            
        } catch (\Exception $e) {
            trace('保存封面失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }
    
    /**
     * 提取歌词
     * @param array $fileInfo getID3 分析结果
     * @return string|null
     */
    private static function extractLyric($fileInfo)
    {
        try {
            // 尝试从 ID3v2 标签获取歌词
            if (isset($fileInfo['tags']['id3v2']['unsynchronised_lyric'][0])) {
                return $fileInfo['tags']['id3v2']['unsynchronised_lyric'][0];
            }
            
            // 尝试从 ID3v2 USLT 标签获取
            if (isset($fileInfo['id3v2']['USLT'][0]['data'])) {
                return $fileInfo['id3v2']['USLT'][0]['data'];
            }
            
            // 尝试从 lyrics3 标签获取
            if (isset($fileInfo['tags']['lyrics3']['lyric'][0])) {
                return $fileInfo['tags']['lyrics3']['lyric'][0];
            }
            
            // 尝试从 APE 标签获取
            if (isset($fileInfo['tags']['ape']['lyrics'][0])) {
                return $fileInfo['tags']['ape']['lyrics'][0];
            }
            
            return null;
            
        } catch (\Exception $e) {
            trace('提取歌词失败: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * 根据 MIME 类型获取文件扩展名
     * @param string $mime
     * @return string
     */
    private static function getMimeExtension($mime)
    {
        $mimeMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/webp' => 'webp',
        ];
        
        return $mimeMap[$mime] ?? 'jpg';
    }
}

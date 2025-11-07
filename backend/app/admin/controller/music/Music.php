<?php

namespace app\admin\controller\music;

use app\common\controller\AdminController;
use app\admin\service\annotation\ControllerAnnotation;
use app\admin\service\annotation\NodeAnnotation;
use think\App;
use app\Request;
use think\facade\Filesystem;
use think\response\Json;

#[ControllerAnnotation(title: '音乐管理')]
class Music extends AdminController
{
    #[NodeAnnotation(ignore: [])]
    protected array $ignoreNode;

    protected array $sort = [
        'id' => 'desc',
    ];

    public function __construct(App $app)
    {
        parent::__construct($app);
        self::$model = \app\admin\model\music\Music::class;
    }

    #[NodeAnnotation(title: '扫描音乐文件', auth: true)]
    public function scan(Request $request): Json
    {
        $this->checkPostRequest();
        
        try {
            // 音乐文件目录
            $musicPath = '/www/wwwroot/Music';
            
            if (!is_dir($musicPath)) {
                return json(['code' => 0, 'msg' => '音乐目录不存在：' . $musicPath]);
            }
            
            $files = scandir($musicPath);
            $count = 0;
            $extensions = ['mp3', 'flac', 'wav', 'm4a'];
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                
                $filePath = $musicPath . '/' . $file;
                
                if (!is_file($filePath)) {
                    continue;
                }
                
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                
                if (!in_array($ext, $extensions)) {
                    continue;
                }
                
                // 检查是否已存在（使用文件名作为路径）
                $exists = self::$model::where('file_path', $file)->find();
                
                if ($exists) {
                    continue;
                }
                
                // 解析文件名
                $name = pathinfo($file, PATHINFO_FILENAME);
                $artist = '';
                
                if (strpos($name, ' - ') !== false) {
                    list($artist, $name) = explode(' - ', $name, 2);
                }
                
                // 添加到数据库（file_path 只存储文件名）
                self::$model::create([
                    'name' => $name,
                    'artist' => $artist,
                    'file_path' => $file,
                    'size' => filesize($filePath),
                    'format' => $ext,
                    'status' => 1,
                ]);
                
                $count++;
            }
            
            return json(['code' => 1, 'msg' => "扫描完成，新增 {$count} 首音乐"]);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '扫描失败：' . $e->getMessage()]);
        }
    }

    #[NodeAnnotation(title: '上传音乐', auth: true)]
    public function upload(Request $request): Json
    {
        if ($request->isPost()) {
            $file = $request->file('file');
            
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择文件']);
            }
            
            try {
                // 验证文件
                validate(['file' => [
                    'fileSize' => 200 * 1024 * 1024, // 200MB
                    'fileExt' => 'mp3,flac,wav,m4a',
                ]])->check(['file' => $file]);
                
                // 音乐文件保存目录
                $musicPath = '/www/wwwroot/Music';
                
                // 确保目录存在
                if (!is_dir($musicPath)) {
                    if (!mkdir($musicPath, 0755, true)) {
                        return json(['code' => 0, 'msg' => '无法创建目录：' . $musicPath]);
                    }
                }
                
                // 检查目录是否可写
                if (!is_writable($musicPath)) {
                    return json(['code' => 0, 'msg' => '目录不可写：' . $musicPath]);
                }
                
                // 获取原始文件名和扩展名
                $originalName = $file->getOriginalName();
                $extension = $file->extension();
                $basename = pathinfo($originalName, PATHINFO_FILENAME);
                
                // 处理文件名重复：如果文件已存在，添加数字后缀
                $finalName = $originalName;
                $counter = 1;
                while (file_exists($musicPath . '/' . $finalName)) {
                    $finalName = $basename . '_' . $counter . '.' . $extension;
                    $counter++;
                }
                
                // 目标文件完整路径
                $targetPath = $musicPath . '/' . $finalName;
                
                // 获取上传文件的临时路径
                $tmpPath = $file->getRealPath();
                
                // ✅ 使用 file_get_contents + file_put_contents，而不是 copy()
                $fileContent = file_get_contents($tmpPath);
                if ($fileContent === false) {
                    return json(['code' => 0, 'msg' => '无法读取上传文件']);
                }
                
                if (file_put_contents($targetPath, $fileContent) === false) {
                    return json(['code' => 0, 'msg' => '文件保存失败，无法写入到：' . $targetPath]);
                }
                
                // 设置文件权限
                @chmod($targetPath, 0755);
                
                // 获取文件信息
                $name = pathinfo($finalName, PATHINFO_FILENAME);
                $filesize = filesize($targetPath);
                
                // 尝试从文件名解析歌手和歌曲名（格式：歌手 - 歌曲名）
                $artist = '';
                if (strpos($name, ' - ') !== false) {
                    list($artist, $name) = explode(' - ', $name, 2);
                }
                
                // 保存到数据库（file_path 只存储文件名）
                $music = self::$model::create([
                    'name' => $name,
                    'artist' => $artist,
                    'file_path' => $finalName,
                    'size' => $filesize,
                    'format' => $extension,
                    'status' => 1,
                ]);
                
                return json([
                    'code' => 1, 
                    'msg' => '上传成功',
                    'data' => [
                        'id' => $music->id,
                        'name' => $name,
                        'artist' => $artist,
                        'size' => $filesize,
                        'format' => $extension,
                        'status' => 1,
                        'file_path' => $finalName,
                    ]
                ]);
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
            }
        }
        
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    #[NodeAnnotation(title: '匹配信息', auth: true)]
    public function match(Request $request): Json
    {
        try {
            $this->checkPostRequest();
            
            $ids = $request->param('ids', '');
            
            if (empty($ids)) {
                return json(['code' => 0, 'msg' => '请选择要匹配的音乐']);
            }
            
            // 检查 getID3 是否可用
            if (!class_exists('getID3')) {
                $getid3Path = root_path('vendor/james-heinrich/getid3/getid3/getid3.php');
                if (!file_exists($getid3Path)) {
                    return json(['code' => 0, 'msg' => 'getID3 库未安装，请先安装：composer require james-heinrich/getid3']);
                }
            }
            
            $idArray = is_array($ids) ? $ids : explode(',', $ids);
            $musicList = self::$model::whereIn('id', $idArray)->select();
            
            if ($musicList->isEmpty()) {
                return json(['code' => 0, 'msg' => '未找到音乐']);
            }
            
            $successCount = 0;
            $failCount = 0;
            $details = [];
            
            foreach ($musicList as $music) {
                try {
                    trace('准备匹配音乐: ' . $music->name . ', 文件路径: ' . $music->file_path, 'info');
                    
                    // 使用 getID3 提取音乐信息（直接传递相对路径）
                    $info = \app\common\service\MusicInfoService::extractMusicInfo($music->file_path);
                
                if ($info) {
                    $updateData = [];
                    
                    // 只更新空字段
                    if (empty($music->name) && !empty($info['title'])) {
                        $updateData['name'] = $info['title'];
                    }
                    
                    if (empty($music->artist) && !empty($info['artist'])) {
                        $updateData['artist'] = $info['artist'];
                    }
                    
                    if (empty($music->album) && !empty($info['album'])) {
                        $updateData['album'] = $info['album'];
                    }
                    
                    // 更新时长
                    if (!empty($info['duration'])) {
                        $updateData['duration'] = $info['duration'];
                    }
                    
                    // 更新歌词（只在为空时更新）
                    if (empty($music->lyric) && !empty($info['lyric'])) {
                        $updateData['lyric'] = $info['lyric'];
                    }
                    
                    // 处理封面
                    if (empty($music->cover) && !empty($info['cover'])) {
                        $coverPath = \app\common\service\MusicInfoService::saveCover($info['cover'], $music->id);
                        if ($coverPath) {
                            $updateData['cover'] = $coverPath;
                        }
                    }
                    
                    // 更新数据库
                    if (!empty($updateData)) {
                        $music->save($updateData);
                        $successCount++;
                        $details[] = "✓ {$music->name}";
                    } else {
                        $details[] = "- {$music->name}（无需更新）";
                    }
                } else {
                    $failCount++;
                    $details[] = "✗ {$music->name}（识别失败）";
                }
            } catch (\Exception $e) {
                $failCount++;
                $details[] = "✗ {$music->name}（错误：{$e->getMessage()}）";
                trace('匹配音乐信息失败: ' . $e->getMessage(), 'error');
            }
        }
        
            $msg = "匹配完成：成功 {$successCount} 首";
            if ($failCount > 0) {
                $msg .= "，失败 {$failCount} 首";
            }
            
            return json([
                'code' => 1, 
                'msg' => $msg,
                'data' => [
                    'success' => $successCount,
                    'fail' => $failCount,
                    'details' => $details
                ]
            ]);
            
        } catch (\Exception $e) {
            trace('匹配操作失败: ' . $e->getMessage(), 'error');
            trace('错误堆栈: ' . $e->getTraceAsString(), 'error');
            return json([
                'code' => 0, 
                'msg' => '匹配失败：' . $e->getMessage()
            ]);
        }
    }

    #[NodeAnnotation(title: '补充信息-搜索', auth: true)]
    public function searchFromApi(Request $request): Json
    {
        try {
            $keywords = $request->param('keywords', '');
            
            if (empty($keywords)) {
                return json(['code' => 0, 'msg' => '请输入搜索关键词']);
            }
            
            // 调用第三方接口搜索
            $apiUrl = 'http://api.melody.crayon.vip/search?keywords=' . urlencode($keywords);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200 || $error) {
                return json(['code' => 0, 'msg' => '搜索失败：' . ($error ?: '接口返回错误')]);
            }
            
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['result']['songs'])) {
                return json(['code' => 0, 'msg' => '搜索结果为空']);
            }
            
            return json([
                'code' => 1,
                'msg' => '搜索成功',
                'data' => $result['result']['songs']
            ]);
            
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '搜索失败：' . $e->getMessage()]);
        }
    }
    
    #[NodeAnnotation(title: '补充信息-应用', auth: true)]
    public function applySupplement(Request $request): Json
    {
        try {
            $this->checkPostRequest();
            
            $musicId = $request->param('music_id', 0);
            $songId = $request->param('song_id', 0);
            
            if (empty($musicId) || empty($songId)) {
                return json(['code' => 0, 'msg' => '参数错误']);
            }
            
            // 查找音乐记录
            $music = self::$model::find($musicId);
            if (!$music) {
                return json(['code' => 0, 'msg' => '音乐不存在']);
            }
            
            // 1. 调用歌曲详情接口
            $detailUrl = 'http://api.melody.crayon.vip/song/detail?ids=' . $songId;
            $detailData = $this->callApi($detailUrl);
            
            if (!$detailData || !isset($detailData['songs'][0])) {
                return json(['code' => 0, 'msg' => '获取歌曲详情失败']);
            }
            
            $songDetail = $detailData['songs'][0];
            
            // 2. 调用歌词接口
            $lyricUrl = 'http://api.melody.crayon.vip/lyric?id=' . $songId;
            $lyricData = $this->callApi($lyricUrl);
            
            $updateData = [];
            
            // 更新歌曲名称
            if (!empty($songDetail['name'])) {
                $updateData['name'] = $songDetail['name'];
            }
            
            // 更新歌手信息
            if (!empty($songDetail['ar']) && is_array($songDetail['ar'])) {
                $artists = array_column($songDetail['ar'], 'name');
                $updateData['artist'] = implode('/', $artists);
            }
            
            // 更新专辑信息
            if (!empty($songDetail['al']['name'])) {
                $updateData['album'] = $songDetail['al']['name'];
            }
            
            // 更新时长（毫秒转秒）
            if (!empty($songDetail['dt'])) {
                $updateData['duration'] = round($songDetail['dt'] / 1000);
            }
            
            // 更新歌词（优先翻译歌词，其次原文歌词）
            if ($lyricData) {
                if (!empty($lyricData['tlyric']['lyric'])) {
                    $updateData['lyric'] = $lyricData['tlyric']['lyric'];
                } elseif (!empty($lyricData['lrc']['lyric'])) {
                    $updateData['lyric'] = $lyricData['lrc']['lyric'];
                }
            }
            
            // 下载并保存封面
            if (!empty($songDetail['al']['picUrl'])) {
                $coverUrl = $songDetail['al']['picUrl'];
                $coverPath = $this->downloadAndSaveCover($coverUrl, $musicId);
                if ($coverPath) {
                    $updateData['cover'] = $coverPath;
                }
            }
            
            // 更新数据库
            if (!empty($updateData)) {
                $music->save($updateData);
                
                // 返回详细信息
                $details = [];
                if (isset($updateData['name'])) $details[] = '歌曲名: ' . $updateData['name'];
                if (isset($updateData['artist'])) $details[] = '歌手: ' . $updateData['artist'];
                if (isset($updateData['album'])) $details[] = '专辑: ' . $updateData['album'];
                if (isset($updateData['duration'])) $details[] = '时长: ' . gmdate('i:s', $updateData['duration']);
                if (isset($updateData['cover'])) $details[] = '封面: 已下载';
                if (isset($updateData['lyric'])) $details[] = '歌词: 已获取';
                
                return json([
                    'code' => 1, 
                    'msg' => '补充信息成功',
                    'data' => [
                        'details' => $details,
                        'updated' => $updateData
                    ]
                ]);
            } else {
                return json(['code' => 0, 'msg' => '没有可更新的信息']);
            }
            
        } catch (\Exception $e) {
            trace('补充信息失败: ' . $e->getMessage(), 'error');
            return json(['code' => 0, 'msg' => '补充信息失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 调用第三方API
     */
    private function callApi($url): ?array
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode !== 200 || $error) {
                trace('API调用失败: ' . $url . ', 错误: ' . $error, 'error');
                return null;
            }
            
            $result = json_decode($response, true);
            return $result ?: null;
            
        } catch (\Exception $e) {
            trace('API调用异常: ' . $e->getMessage(), 'error');
            return null;
        }
    }
    
    /**
     * 下载并保存封面图片
     */
    private function downloadAndSaveCover($url, $musicId): string
    {
        try {
            // 下载图片
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200 || !$imageData) {
                return '';
            }
            
            // 封面保存目录
            $baseDir = '/www/wwwroot/Music/covers';
            $yearMonth = date('Ym');
            $coverPath = $baseDir . '/' . $yearMonth;
            
            // 确保目录存在
            if (!is_dir($coverPath)) {
                mkdir($coverPath, 0755, true);
            }
            
            // 生成文件名
            $extension = 'jpg';
            $finalName = 'music_' . $musicId . '_' . time() . '.' . $extension;
            $targetPath = $coverPath . '/' . $finalName;
            
            // 保存文件
            if (file_put_contents($targetPath, $imageData) === false) {
                return '';
            }
            
            @chmod($targetPath, 0644);
            
            // 返回相对路径
            return 'covers/' . $yearMonth . '/' . $finalName;
            
        } catch (\Exception $e) {
            trace('下载封面失败: ' . $e->getMessage(), 'error');
            return '';
        }
    }

    #[NodeAnnotation(title: '上传封面', auth: true)]
    public function uploadCover(Request $request): Json
    {
        if ($request->isPost()) {
            $file = $request->file('file');
            
            if (!$file) {
                return json(['code' => 0, 'msg' => '请选择文件']);
            }
            
            try {
                // 验证文件
                validate(['file' => [
                    'fileSize' => 5 * 1024 * 1024, // 5MB
                    'fileExt' => 'jpg,jpeg,png,gif,webp',
                ]])->check(['file' => $file]);
                
                // 封面保存基础目录
                $baseDir = '/www/wwwroot/Music/covers';
                
                // 添加年月子目录
                $yearMonth = date('Ym');
                $coverPath = $baseDir . '/' . $yearMonth;
                
                // 确保目录存在
                if (!is_dir($coverPath)) {
                    if (!mkdir($coverPath, 0755, true)) {
                        return json(['code' => 0, 'msg' => '无法创建目录：' . $coverPath]);
                    }
                }
                
                // 检查目录是否可写
                if (!is_writable($coverPath)) {
                    return json(['code' => 0, 'msg' => '目录不可写：' . $coverPath]);
                }
                
                // 获取原始文件名和扩展名
                $originalName = $file->getOriginalName();
                $extension = $file->extension();
                
                // 生成唯一文件名：使用时间戳 + 随机数
                $finalName = date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $extension;
                
                // 目标文件完整路径
                $targetPath = $coverPath . '/' . $finalName;
                
                // 相对路径（用于数据库存储和前端访问）
                $relativePath = 'covers/' . $yearMonth . '/' . $finalName;
                
                // 获取上传文件的临时路径
                $tmpPath = $file->getRealPath();
                
                // 使用 file_get_contents + file_put_contents
                $fileContent = file_get_contents($tmpPath);
                if ($fileContent === false) {
                    return json(['code' => 0, 'msg' => '无法读取上传文件']);
                }
                
                if (file_put_contents($targetPath, $fileContent) === false) {
                    return json(['code' => 0, 'msg' => '文件保存失败']);
                }
                
                // 设置文件权限
                @chmod($targetPath, 0644);
                
                // 返回相对路径和完整URL
                return json([
                    'code' => 1, 
                    'msg' => '上传成功',
                    'data' => [
                        'path' => $relativePath,
                        'url' => domain() . '/Music/' . $relativePath,
                    ]
                ]);
            } catch (\Exception $e) {
                return json(['code' => 0, 'msg' => '上传失败：' . $e->getMessage()]);
            }
        }
        
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }

    #[NodeAnnotation(title: '状态切换', auth: true)]
    public function changeStatus(Request $request): Json
    {
        $this->checkPostRequest();
        
        $ids = $request->param('ids', '');
        $status = $request->param('status', 1);
        
        if (empty($ids)) {
            return json(['code' => 0, 'msg' => '请选择要操作的数据']);
        }
        
        $idArray = is_array($ids) ? $ids : explode(',', $ids);
        
        try {
            self::$model::whereIn('id', $idArray)->update(['status' => $status]);
            return json(['code' => 1, 'msg' => '操作成功']);
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => '操作失败：' . $e->getMessage()]);
        }
    }
}

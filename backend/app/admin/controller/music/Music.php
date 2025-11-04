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
                    'fileSize' => 50 * 1024 * 1024, // 50MB
                    'fileExt' => 'mp3,flac,wav,m4a',
                ]])->check(['file' => $file]);
                
                // 保存文件
                $savename = Filesystem::disk('public')->putFile('music', $file);
                
                if (!$savename) {
                    return json(['code' => 0, 'msg' => '上传失败']);
                }
                
                // 获取文件信息
                $originalName = $file->getOriginalName();
                $name = pathinfo($originalName, PATHINFO_FILENAME);
                $filesize = $file->getSize();
                
                // 尝试从文件名解析歌手和歌曲名（格式：歌手 - 歌曲名）
                $artist = '';
                if (strpos($name, ' - ') !== false) {
                    list($artist, $name) = explode(' - ', $name, 2);
                }
                
                // 保存到数据库
                $music = self::$model::create([
                    'name' => $name,
                    'artist' => $artist,
                    'file_path' => $savename,
                    'size' => $filesize,
                    'format' => $file->extension(),
                    'status' => 1,
                ]);
                
                return json([
                    'code' => 1, 
                    'msg' => '上传成功',
                    'data' => [
                        'id' => $music->id,
                        'name' => $name,
                        'artist' => $artist,
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

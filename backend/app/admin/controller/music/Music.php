<?php

namespace app\admin\controller\music;

use app\common\controller\Backend;
use think\facade\Db;
use think\facade\Cache;
use think\facade\Http;
use think\exception\ValidateException;

/**
 * 音乐管理
 */
class Music extends Backend
{
    protected $model = null;
    protected $musicDir = '';
    
    public function initialize()
    {
        parent::initialize();
        $this->model = new \app\admin\model\music\Music;
        $this->musicDir = root_path() . 'public/wwwroot/alist/music/';
        
        // 确保目录存在
        if (!is_dir($this->musicDir)) {
            mkdir($this->musicDir, 0755, true);
        }
    }
    
    /**
     * 查看列表
     */
    public function index()
    {
        if ($this->request->isAjax()) {
            list($page, $limit, $where, $sort, $order) = $this->buildTableParames();
            
            $count = $this->model->where($where)->count();
            $list = $this->model->where($where)
                ->page($page, $limit)
                ->order($sort, $order)
                ->select();
            
            $result = ['code' => 0, 'msg' => '获取成功', 'count' => $count, 'data' => $list];
            return json($result);
        }
        
        return $this->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (empty($params)) {
                $this->error('参数不能为空');
            }
            
            try {
                $this->model->save($params);
                $this->success('添加成功');
            } catch (ValidateException $e) {
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                $this->error('添加失败');
            }
        }
        
        return $this->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = null)
    {
        $row = $this->model->find($ids);
        if (!$row) {
            $this->error('记录不存在');
        }
        
        if ($this->request->isPost()) {
            $params = $this->request->post('row/a');
            
            if (empty($params)) {
                $this->error('参数不能为空');
            }
            
            try {
                $row->save($params);
                $this->success('修改成功');
            } catch (ValidateException $e) {
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                $this->error('修改失败');
            }
        }
        
        $this->view->assign('row', $row);
        return $this->fetch();
    }
    
    /**
     * 删除
     */
    public function del($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        
        if (empty($ids)) {
            $this->error('参数错误');
        }
        
        $ids = explode(',', $ids);
        $list = $this->model->where('id', 'in', $ids)->select();
        
        try {
            foreach ($list as $item) {
                // 删除文件
                if ($item->file_path && file_exists($this->musicDir . $item->file_path)) {
                    @unlink($this->musicDir . $item->file_path);
                }
                $item->delete();
            }
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error('删除失败');
        }
    }
    
    /**
     * 扫描音乐文件
     */
    public function scan()
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        
        try {
            $files = $this->scanMusicFiles($this->musicDir);
            $count = 0;
            
            foreach ($files as $file) {
                $filename = basename($file);
                $relativePath = str_replace($this->musicDir, '', $file);
                
                // 检查是否已存在
                $exists = $this->model->where('file_path', $relativePath)->find();
                if ($exists) {
                    continue;
                }
                
                // 获取文件信息
                $filesize = filesize($file);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                
                // 保存到数据库
                $this->model->save([
                    'name' => $name,
                    'file_path' => $relativePath,
                    'file_size' => $filesize,
                    'status' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                
                $count++;
            }
            
            $this->success("扫描完成，新增 {$count} 首歌曲");
        } catch (\Exception $e) {
            $this->error('扫描失败：' . $e->getMessage());
        }
    }
    
    /**
     * 匹配音乐信息
     */
    public function match($ids = '')
    {
        if (!$this->request->isPost()) {
            $this->error('非法请求');
        }
        
        if (empty($ids)) {
            $this->error('参数错误');
        }
        
        $ids = explode(',', $ids);
        $list = $this->model->where('id', 'in', $ids)->select();
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($list as $item) {
            $musicInfo = $this->getMusicInfo($item->name);
            
            if (!empty($musicInfo)) {
                $item->save([
                    'artist' => $musicInfo['artist'] ?? '',
                    'album' => $musicInfo['album'] ?? '',
                    'cover' => $musicInfo['cover'] ?? '',
                    'lyric' => $musicInfo['lyric'] ?? '',
                    'duration' => $musicInfo['duration'] ?? 0,
                    'update_time' => time(),
                ]);
                $successCount++;
            } else {
                $failCount++;
            }
            
            // 避免请求过快
            usleep(500000); // 0.5秒
        }
        
        $this->success("匹配完成，成功 {$successCount} 首，失败 {$failCount} 首");
    }
    
    /**
     * 上传音乐
     */
    public function upload()
    {
        if ($this->request->isPost()) {
            $file = $this->request->file('file');
            
            if (!$file) {
                $this->error('请选择文件');
            }
            
            try {
                // 验证文件
                validate(['file' => [
                    'fileSize' => 50 * 1024 * 1024, // 50MB
                    'fileExt' => 'mp3,flac,wav,m4a',
                ]])->check(['file' => $file]);
                
                // 保存文件
                $savename = \think\facade\Filesystem::disk('public')->putFile('wwwroot/alist/music', $file);
                
                if (!$savename) {
                    $this->error('上传失败');
                }
                
                // 获取文件信息
                $filename = basename($savename);
                $name = pathinfo($filename, PATHINFO_FILENAME);
                $filesize = $file->getSize();
                
                // 保存到数据库
                $music = $this->model->save([
                    'name' => $name,
                    'file_path' => $filename,
                    'file_size' => $filesize,
                    'status' => 1,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
                
                $this->success('上传成功', null, ['id' => $this->model->id]);
            } catch (ValidateException $e) {
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                $this->error('上传失败：' . $e->getMessage());
            }
        }
        
        return $this->fetch();
    }
    
    /**
     * 统计数据
     */
    public function statistics()
    {
        $totalCount = $this->model->count();
        $totalSize = $this->model->sum('file_size');
        $totalDuration = $this->model->sum('duration');
        $matchedCount = $this->model->where('artist', '<>', '')->count();
        
        $data = [
            'total_count' => $totalCount,
            'total_size' => $this->formatBytes($totalSize),
            'total_duration' => $this->formatDuration($totalDuration),
            'matched_count' => $matchedCount,
            'match_rate' => $totalCount > 0 ? round($matchedCount / $totalCount * 100, 2) : 0,
        ];
        
        return json(['code' => 1, 'data' => $data]);
    }
    
    /**
     * 扫描音乐文件（递归）
     */
    private function scanMusicFiles($dir)
    {
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            
            $path = $dir . $item;
            
            if (is_dir($path)) {
                $files = array_merge($files, $this->scanMusicFiles($path . '/'));
            } elseif (preg_match('/\.(mp3|flac|wav|m4a)$/i', $item)) {
                $files[] = $path;
            }
        }
        
        return $files;
    }
    
    /**
     * 获取音乐信息
     */
    private function getMusicInfo($keyword)
    {
        $cacheKey = 'music_info_' . md5($keyword);
        $cached = Cache::get($cacheKey);
        
        if ($cached) {
            return $cached;
        }
        
        try {
            // 网易云音乐 API
            $api = "https://api.vvhan.com/api/wyy?type=song&msg=" . urlencode($keyword);
            $res = Http::timeout(5)->get($api)->json();
            
            if (!$res || empty($res['info'])) {
                return [];
            }
            
            $info = $res['info'];
            $data = [
                'title' => $info['title'] ?? '',
                'artist' => $info['author'] ?? '',
                'album' => $info['album'] ?? '',
                'cover' => $info['picurl'] ?? '',
                'lyric' => $info['lrc'] ?? '',
                'duration' => $info['time'] ?? 0
            ];
            
            // 缓存7天
            Cache::set($cacheKey, $data, 86400 * 7);
            
            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * 格式化文件大小
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        
        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    /**
     * 格式化时长
     */
    private function formatDuration($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        
        if ($hours > 0) {
            return $hours . '小时' . $minutes . '分钟';
        }
        
        return $minutes . '分钟';
    }
}

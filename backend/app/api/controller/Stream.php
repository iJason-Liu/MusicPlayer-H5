<?php

namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Db;
use think\Response;

/**
 * 音频流传输控制器
 * 支持 HTTP Range 请求，实现流式传输
 */
class Stream extends Api
{
    protected $noNeedLogin = ['*'];
    protected $noNeedRight = ['*'];
    
    /**
     * 流式传输音频文件
     */
    public function audio()
    {
        $id = $this->request->param('id');
        
        if (empty($id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }
        
        // 获取音乐信息
        $music = Db::name('music')->where('id', $id)->find();
        if (!$music || $music['status'] != 1) {
            return json(['code' => 0, 'msg' => '音乐不存在']);
        }
        
        // 构建文件路径
        $filePath = $this->getFilePath($music['file_path']);
        
        if (!file_exists($filePath)) {
            return json([
                'code' => 0, 
                'msg' => '文件不存在',
                'debug' => [
                    'file_path' => $filePath,
                    'relative_path' => $music['file_path']
                ]
            ]);
        }
        
        // 获取文件信息
        $fileSize = filesize($filePath);
        $mimeType = $this->getMimeType($music['format']);
        
        // 获取 Range 请求头
        $range = $this->request->header('range');
        
        if ($range) {
            // 处理 Range 请求
            return $this->streamRangeResponse($filePath, $fileSize, $mimeType, $range);
        } else {
            // 完整文件响应
            return $this->streamFullResponse($filePath, $fileSize, $mimeType);
        }
    }
    
    /**
     * 处理 Range 请求响应
     */
    private function streamRangeResponse($filePath, $fileSize, $mimeType, $range)
    {
        // 解析 Range 头
        preg_match('/bytes=(\d+)-(\d*)/', $range, $matches);
        $start = intval($matches[1]);
        $end = !empty($matches[2]) ? intval($matches[2]) : $fileSize - 1;
        
        // 确保范围有效
        if ($start > $end || $start >= $fileSize) {
            return response('', 416)
                ->header([
                    'Content-Range' => "bytes */$fileSize"
                ]);
        }
        
        $end = min($end, $fileSize - 1);
        $length = $end - $start + 1;
        
        // 读取文件数据
        $fp = fopen($filePath, 'rb');
        fseek($fp, $start);
        $data = fread($fp, $length);
        fclose($fp);
        
        // 返回 206 Partial Content
        return response($data, 206)
            ->header([
                'Content-Type' => $mimeType,
                'Content-Length' => $length,
                'Content-Range' => "bytes $start-$end/$fileSize",
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Expose-Headers' => 'Content-Length, Content-Range'
            ]);
    }
    
    /**
     * 完整文件响应
     */
    private function streamFullResponse($filePath, $fileSize, $mimeType)
    {
        // 读取文件
        $data = file_get_contents($filePath);
        
        return response($data, 200)
            ->header([
                'Content-Type' => $mimeType,
                'Content-Length' => $fileSize,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'public, max-age=31536000',
                'Access-Control-Allow-Origin' => '*'
            ]);
    }
    
    /**
     * 获取文件路径
     */
    private function getFilePath($relativePath)
    {
        // Music 目录在 /www/wwwroot/Music/
        $basePath = '/www/wwwroot/Music/';
        return $basePath . $relativePath;
    }
    
    /**
     * 获取 MIME 类型
     */
    private function getMimeType($format)
    {
        $mimeTypes = [
            'mp3' => 'audio/mpeg',
            'flac' => 'audio/flac',
            'wav' => 'audio/wav',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac'
        ];
        
        return $mimeTypes[$format] ?? 'audio/mpeg';
    }
}

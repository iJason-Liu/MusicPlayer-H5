<?php

namespace app\common\service;

use think\facade\Db;
use think\facade\Cache;

/**
 * Token 服务类
 */
class TokenService
{
    // Token 过期时间（秒）- 7天
    const TOKEN_EXPIRE = 7 * 24 * 3600;
    
    // Token 前缀
    const TOKEN_PREFIX = 'user_token:';
    
    /**
     * 生成 Token
     * @param int $userId 用户ID
     * @param array $extra 额外信息
     * @return string
     */
    public static function createToken($userId, $extra = [])
    {
        // 生成随机 token
        $token = self::generateRandomToken();
        
        // Token 数据
        $tokenData = [
            'user_id' => $userId,
            'create_time' => time(),
            'expire_time' => time() + self::TOKEN_EXPIRE,
            'extra' => $extra
        ];
        
        // 同时保存到缓存和数据库，确保数据持久化
        try {
            Cache::set(self::TOKEN_PREFIX . $token, $tokenData, self::TOKEN_EXPIRE);
        } catch (\Exception $e) {
            // 缓存失败不影响主流程
        }
        
        // 始终保存到数据库
        self::saveTokenToDb($token, $tokenData);
        
        return $token;
    }
    
    /**
     * 验证 Token 并获取用户ID
     * @param string $token
     * @return int|false 返回用户ID或false
     */
    public static function getUserIdByToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // 移除 Bearer 前缀
        $token = str_replace('Bearer ', '', $token);
        
        // 优先从缓存获取（快速）
        try {
            $tokenData = Cache::get(self::TOKEN_PREFIX . $token);
            if ($tokenData && isset($tokenData['user_id'])) {
                // 检查是否过期
                if ($tokenData['expire_time'] > time()) {
                    // 刷新过期时间（缓存和数据库都刷新）
                    Cache::set(self::TOKEN_PREFIX . $token, $tokenData, self::TOKEN_EXPIRE);
                    Db::name('user_token')
                        ->where('token', $token)
                        ->update(['expire_time' => time() + self::TOKEN_EXPIRE]);
                    return $tokenData['user_id'];
                }
            }
        } catch (\Exception $e) {
            // 缓存失败不影响主流程
        }
        
        // 从数据库获取（缓存未命中或过期）
        return self::getUserIdFromDb($token);
    }
    
    /**
     * 删除 Token（退出登录）
     * @param string $token
     * @return bool
     */
    public static function deleteToken($token)
    {
        if (empty($token)) {
            return false;
        }
        
        // 移除 Bearer 前缀
        $token = str_replace('Bearer ', '', $token);
        
        // 从缓存删除
        try {
            Cache::delete(self::TOKEN_PREFIX . $token);
        } catch (\Exception $e) {
            // 缓存失败不影响主流程
        }
        
        // 从数据库删除
        self::deleteTokenFromDb($token);
        
        return true;
    }
    
    /**
     * 刷新 Token
     * @param string $oldToken
     * @return string|false 返回新token或false
     */
    public static function refreshToken($oldToken)
    {
        $userId = self::getUserIdByToken($oldToken);
        if ($userId) {
            // 删除旧 token
            self::deleteToken($oldToken);
            // 创建新 token
            return self::createToken($userId);
        }
        return false;
    }
    
    /**
     * 生成随机 Token
     * @return string
     */
    private static function generateRandomToken()
    {
        return md5(uniqid() . time() . rand(1000, 9999));
    }
    
    /**
     * 保存 Token 到数据库
     * @param string $token
     * @param array $tokenData
     */
    private static function saveTokenToDb($token, $tokenData)
    {
        // 先删除该用户的旧 token
        Db::name('user_token')
            ->where('user_id', $tokenData['user_id'])
            ->delete();
        
        // 插入新 token
        Db::name('user_token')->insert([
            'user_id' => $tokenData['user_id'],
            'token' => $token,
            'create_time' => $tokenData['create_time'],
            'expire_time' => $tokenData['expire_time'],
            'extra' => json_encode($tokenData['extra'])
        ]);
    }
    
    /**
     * 从数据库获取用户ID
     * @param string $token
     * @return int|false
     */
    private static function getUserIdFromDb($token)
    {
        $tokenData = Db::name('user_token')
            ->where('token', $token)
            ->where('expire_time', '>', time())
            ->find();
        
        if ($tokenData) {
            // 刷新过期时间
            Db::name('user_token')
                ->where('token', $token)
                ->update(['expire_time' => time() + self::TOKEN_EXPIRE]);
            
            return $tokenData['user_id'];
        }
        
        return false;
    }
    
    /**
     * 从数据库删除 Token
     * @param string $token
     */
    private static function deleteTokenFromDb($token)
    {
        Db::name('user_token')->where('token', $token)->delete();
    }
    
    /**
     * 清理过期 Token（定时任务调用）
     */
    public static function clearExpiredTokens()
    {
        Db::name('user_token')
            ->where('expire_time', '<', time())
            ->delete();
    }
}

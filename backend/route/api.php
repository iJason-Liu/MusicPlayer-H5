<?php
use think\facade\Route;

/**
 * API 应用路由配置
 * 文件名 api.php 会自动对应 api 应用
 * 访问路径：/api/xxx
 */

// 用户相关
Route::group('user', function () {
    Route::post('login', 'User/login');           // 登录
    Route::get('info', 'User/info');              // 获取用户信息
    Route::get('statistics', 'User/statistics');  // 统计信息
    Route::post('update', 'User/updateProfile');  // 更新用户信息
    Route::post('password', 'User/changePassword'); // 修改密码
    Route::post('logout', 'User/logout');         // 退出登录
});

// 音乐相关
Route::group('music', function () {
    Route::get('list', 'Music/list');             // 音乐列表
    Route::get('search', 'Music/search');         // 搜索音乐
    Route::get('detail', 'Music/detail');         // 音乐详情
    Route::get('recommend', 'Music/recommend');   // 推荐音乐
    Route::get('hot', 'Music/hot');               // 热门音乐
});

// 播放历史
Route::group('history', function () {
    Route::get('list', 'History/list');          // 播放历史列表
    Route::post('add', 'History/add');            // 添加播放记录
    Route::post('delete', 'History/delete');      // 删除播放记录
    Route::post('clear', 'History/clear');        // 清空播放历史
});

// 收藏相关
Route::group('favorite', function () {
    Route::get('list', 'Favorite/list');         // 收藏列表
    Route::post('add', 'Favorite/add');           // 添加收藏
    Route::post('remove', 'Favorite/remove');     // 取消收藏
    Route::get('check', 'Favorite/check');        // 检查是否收藏
});

// 播放列表
Route::group('playlist', function () {
    Route::get('list', 'Playlist/list');         // 播放列表
    Route::post('create', 'Playlist/create');     // 创建播放列表
    Route::post('update', 'Playlist/update');     // 更新播放列表
    Route::post('delete', 'Playlist/delete');     // 删除播放列表
    Route::get('detail', 'Playlist/detail');      // 播放列表详情
    Route::post('addMusic', 'Playlist/addMusic');     // 添加音乐到列表
    Route::post('removeMusic', 'Playlist/removeMusic'); // 从列表移除音乐
    Route::get('getQueue', 'Playlist/getQueue');     // 获取当前播放队列
    Route::post('saveQueue', 'Playlist/saveQueue');   // 保存当前播放队列
});

// 音频流传输
Route::get('stream/audio', 'Stream/audio');      // 流式传输音频

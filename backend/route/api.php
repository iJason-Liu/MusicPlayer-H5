<?php
use think\facade\Route;

/**
 * API 路由配置
 */

// 用户相关
Route::group('api/user', function () {
    Route::post('login', 'api.User/login');           // 登录
    Route::get('info', 'api.User/info');              // 获取用户信息
    Route::get('statistics', 'api.User/statistics');  // 统计信息
    Route::post('update', 'api.User/updateProfile');  // 更新用户信息
    Route::post('password', 'api.User/changePassword'); // 修改密码
    Route::post('logout', 'api.User/logout');         // 退出登录
});

// 音乐相关
Route::group('api/music', function () {
    Route::get('list', 'api.Music/index');            // 音乐列表
    Route::get('search', 'api.Music/search');         // 搜索音乐
    Route::get('detail', 'api.Music/detail');         // 音乐详情
    Route::get('recommend', 'api.Music/recommend');   // 推荐音乐
    Route::get('hot', 'api.Music/hot');               // 热门音乐
});

// 播放历史
Route::group('api/history', function () {
    Route::get('list', 'api.History/index');          // 播放历史列表
    Route::post('add', 'api.History/add');            // 添加播放记录
    Route::post('delete', 'api.History/delete');      // 删除播放记录
    Route::post('clear', 'api.History/clear');        // 清空播放历史
});

// 收藏相关
Route::group('api/favorite', function () {
    Route::get('list', 'api.Favorite/index');         // 收藏列表
    Route::post('add', 'api.Favorite/add');           // 添加收藏
    Route::post('remove', 'api.Favorite/remove');     // 取消收藏
    Route::get('check', 'api.Favorite/check');        // 检查是否收藏
});

// 播放列表
Route::group('api/playlist', function () {
    Route::get('list', 'api.Playlist/index');         // 播放列表
    Route::post('create', 'api.Playlist/create');     // 创建播放列表
    Route::post('update', 'api.Playlist/update');     // 更新播放列表
    Route::post('delete', 'api.Playlist/delete');     // 删除播放列表
    Route::get('detail', 'api.Playlist/detail');      // 播放列表详情
    Route::post('add-music', 'api.Playlist/addMusic');     // 添加音乐到列表
    Route::post('remove-music', 'api.Playlist/removeMusic'); // 从列表移除音乐
});

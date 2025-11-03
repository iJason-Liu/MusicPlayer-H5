-- 添加音乐模块菜单
-- 执行此 SQL 将音乐模块添加到后台管理菜单中
-- 
-- 重要提示：
-- 1. 如果已经执行过此 SQL，需要先删除旧的菜单数据
-- 2. 表名前缀为 ea_（根据你的系统配置）
-- 3. href 使用点号分隔多级控制器，如 music.user/index

SET NAMES utf8mb4;

-- 删除旧的音乐模块菜单（如果存在）
DELETE FROM `mu_system_menu` WHERE `title` = '音乐模块';

-- 添加音乐模块主菜单（一级菜单，pid=0 表示顶级菜单）
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (0, '音乐模块', 'fa fa-music', '', '_self', 13, 1, '音乐播放器管理模块', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 获取刚插入的音乐模块 ID
SET @music_module_id = LAST_INSERT_ID();

-- 添加用户管理菜单
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (@music_module_id, '用户管理', 'fa fa-users', 'music.user/index', '_self', 1, 1, '音乐用户管理', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 添加音乐管理菜单
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (@music_module_id, '音乐管理', 'fa fa-music', 'music.music/index', '_self', 2, 1, '音乐文件管理', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 添加播放历史菜单
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (@music_module_id, '播放历史', 'fa fa-history', 'music.history/index', '_self', 3, 1, '用户播放历史', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 添加收藏管理菜单
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (@music_module_id, '收藏管理', 'fa fa-heart', 'music.favorite/index', '_self', 4, 1, '用户收藏管理', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 添加统计分析菜单
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
VALUES (@music_module_id, '统计分析', 'fa fa-bar-chart', 'music.statistics/index', '_self', 5, 1, '数据统计分析', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL);

-- 添加播放列表菜单（如果不存在）
INSERT INTO `mu_system_menu` (`pid`, `title`, `icon`, `href`, `target`, `sort`, `status`, `remark`, `create_time`, `update_time`, `delete_time`) 
SELECT @music_module_id, '播放列表', 'fa fa-list', 'music.playlist/index', '_self', 5, 1, '用户播放列表管理', UNIX_TIMESTAMP(), UNIX_TIMESTAMP(), NULL
WHERE NOT EXISTS (
    SELECT 1 FROM mu_system_menu WHERE title = '播放列表' AND pid = @music_module_id
);

-- 执行完成后的操作：
-- 1. 刷新后台页面
-- 2. 如果菜单没有更新，清除浏览器缓存
-- 3. 如果还是看不到，检查角色权限配置

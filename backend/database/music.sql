-- 音乐播放器数据库表结构
-- 数据库: music

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 用户表
-- ----------------------------
DROP TABLE IF EXISTS `mu_user`;
CREATE TABLE `mu_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username` varchar(50) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(255) NOT NULL DEFAULT '' COMMENT '密码',
  `nickname` varchar(50) NOT NULL DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像',
  `email` varchar(100) DEFAULT '' COMMENT '邮箱',
  `mobile` varchar(20) DEFAULT '' COMMENT '手机号',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 插入测试用户
INSERT INTO `mu_user` VALUES (1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '管理员', '', 'admin@example.com', '', 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ----------------------------
-- 音乐表
-- ----------------------------
DROP TABLE IF EXISTS `mu_music`;
CREATE TABLE `mu_music` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '音乐ID',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '歌曲名称',
  `artist` varchar(255) DEFAULT '' COMMENT '艺术家',
  `album` varchar(255) DEFAULT '' COMMENT '专辑',
  `cover` varchar(500) DEFAULT '' COMMENT '封面图片',
  `file_path` varchar(500) NOT NULL DEFAULT '' COMMENT '文件路径',
  `duration` int(11) DEFAULT '0' COMMENT '时长（秒）',
  `size` bigint(20) DEFAULT '0' COMMENT '文件大小（字节）',
  `format` varchar(20) DEFAULT '' COMMENT '文件格式',
  `lyric` text COMMENT '歌词',
  `play_count` int(11) DEFAULT '0' COMMENT '播放次数',
  `favorite_count` int(11) DEFAULT '0' COMMENT '收藏次数',
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '状态：1正常 0禁用',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_name` (`name`),
  KEY `idx_artist` (`artist`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='音乐表';

-- ----------------------------
-- 播放历史表
-- ----------------------------
DROP TABLE IF EXISTS `mu_play_history`;
CREATE TABLE `mu_play_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '历史ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `music_id` int(11) NOT NULL COMMENT '音乐ID',
  `play_time` int(11) DEFAULT NULL COMMENT '播放时间',
  `play_duration` int(11) DEFAULT '0' COMMENT '播放时长（秒）',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_music_id` (`music_id`),
  KEY `idx_play_time` (`play_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='播放历史表';

-- ----------------------------
-- 收藏表
-- ----------------------------
DROP TABLE IF EXISTS `mu_favorite`;
CREATE TABLE `mu_favorite` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `music_id` int(11) NOT NULL COMMENT '音乐ID',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_music` (`user_id`,`music_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_music_id` (`music_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='收藏表';

-- ----------------------------
-- 播放列表表
-- ----------------------------
DROP TABLE IF EXISTS `mu_playlist`;
CREATE TABLE `mu_playlist` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '列表ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '列表名称',
  `cover` varchar(500) DEFAULT '' COMMENT '封面图片',
  `description` varchar(500) DEFAULT '' COMMENT '描述',
  `music_count` int(11) DEFAULT '0' COMMENT '音乐数量',
  `play_count` int(11) DEFAULT '0' COMMENT '播放次数',
  `is_public` tinyint(1) DEFAULT '1' COMMENT '是否公开：1公开 0私密',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='播放列表表';

-- ----------------------------
-- 播放列表音乐关联表
-- ----------------------------
DROP TABLE IF EXISTS `mu_playlist_music`;
CREATE TABLE `mu_playlist_music` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `playlist_id` int(11) NOT NULL COMMENT '播放列表ID',
  `music_id` int(11) NOT NULL COMMENT '音乐ID',
  `sort` int(11) DEFAULT '0' COMMENT '排序',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_playlist_music` (`playlist_id`,`music_id`),
  KEY `idx_playlist_id` (`playlist_id`),
  KEY `idx_music_id` (`music_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='播放列表音乐关联表';

-- ----------------------------
-- 用户配置表
-- ----------------------------
DROP TABLE IF EXISTS `mu_user_config`;
CREATE TABLE `mu_user_config` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `user_id` int(11) NOT NULL COMMENT '用户ID',
  `config_key` varchar(50) NOT NULL DEFAULT '' COMMENT '配置键',
  `config_value` text COMMENT '配置值',
  `create_time` int(11) DEFAULT NULL COMMENT '创建时间',
  `update_time` int(11) DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_key` (`user_id`,`config_key`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户配置表';

SET FOREIGN_KEY_CHECKS = 1;

-- 音乐播放器示例数据
-- 用于测试和演示

SET NAMES utf8mb4;

-- 插入示例音乐数据
INSERT INTO `mu_music` (`name`, `artist`, `album`, `cover`, `file_path`, `duration`, `size`, `format`, `lyric`, `play_count`, `favorite_count`, `status`, `create_time`, `update_time`) VALUES
('晴天', '周杰伦', '叶惠美', 'https://p1.music.126.net/6y-UleORITEDbvrOLV0Q8A==/5639395138885805.jpg', 'music/202411/qingtian.mp3', 269, 4320000, 'mp3', '', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('七里香', '周杰伦', '七里香', 'https://p1.music.126.net/jo2IYId88zXNHT-6C7WqIQ==/109951163076136658.jpg', 'music/202411/qilixiang.mp3', 300, 4800000, 'mp3', '', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('稻香', '周杰伦', '魔杰座', 'https://p1.music.126.net/6y-UleORITEDbvrOLV0Q8A==/5639395138885805.jpg', 'music/202411/daoxiang.mp3', 223, 3568000, 'mp3', '', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('告白气球', '周杰伦', '周杰伦的床边故事', 'https://p1.music.126.net/6y-UleORITEDbvrOLV0Q8A==/5639395138885805.jpg', 'music/202411/gaobai.mp3', 210, 3360000, 'mp3', '', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
('夜曲', '周杰伦', '十一月的萧邦', 'https://p1.music.126.net/6y-UleORITEDbvrOLV0Q8A==/5639395138885805.jpg', 'music/202411/yequ.mp3', 240, 3840000, 'mp3', '', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 插入示例播放列表
INSERT INTO `mu_playlist` (`user_id`, `name`, `cover`, `description`, `music_count`, `play_count`, `is_public`, `create_time`, `update_time`) VALUES
(1, '我喜欢的音乐', '', '收藏的好听的歌曲', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(1, '周杰伦精选', '', '周杰伦的经典歌曲', 0, 0, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 注意：实际使用时需要将音乐文件放到对应的路径
-- 或者修改 file_path 为实际的文件路径

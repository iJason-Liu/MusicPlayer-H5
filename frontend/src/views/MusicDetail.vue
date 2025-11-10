<template>
	<div class="music-detail-page">
		<div class="background" :style="{ backgroundImage: `url(${getCoverUrl(musicDetail?.cover)})` }"></div>

		<div class="detail-content">
			<!-- 顶部导航栏 -->
			<div class="top-bar">
				<i class="fas fa-chevron-left" @click="$router.back()"></i>
				<span class="title">歌曲详情</span>
				<div style="width: 40px;"></div>
			</div>

			<!-- 封面 -->
			<div class="cover-section">
				<div class="cover-wrapper">
					<img :src="getCoverUrl(musicDetail?.cover)" />
				</div>
			</div>

			<!-- 歌曲信息 -->
			<div class="info-section" v-if="musicDetail">
				<div class="info-item">
					<span class="label">歌曲名称</span>
					<span class="value">{{ musicDetail.name || '未知' }}</span>
				</div>
				<div class="info-item">
					<span class="label">艺术家</span>
					<span class="value">{{ musicDetail.artist || '未知' }}</span>
				</div>
				<div class="info-item">
					<span class="label">专辑</span>
					<span class="value">{{ musicDetail.album || '未知' }}</span>
				</div>
				<div class="info-item">
					<span class="label">时长</span>
					<span class="value">{{ formatDuration(musicDetail.duration) }}</span>
				</div>
				<div class="info-item">
					<span class="label">格式</span>
					<span class="value">{{ musicDetail.format?.toUpperCase() || '未知' }}</span>
				</div>
				<div class="info-item">
					<span class="label">文件大小</span>
					<span class="value">{{ formatSize(musicDetail.size) }}</span>
				</div>
				<div class="info-item" v-if="musicDetail.play_count">
					<span class="label">播放次数</span>
					<span class="value">{{ musicDetail.play_count }} 次</span>
				</div>
			</div>

			<!-- 操作按钮 -->
			<div class="actions-section" v-if="musicDetail">
				<div class="action-btn" @click="handleFavorite">
					<i class="fas fa-heart" :style="{ color: isFavorite ? '#ff4757' : '#fff' }"></i>
					<span>{{ isFavorite ? '已收藏' : '收藏' }}</span>
				</div>
				<div class="action-btn" @click="addToPlaylist">
					<i class="fas fa-step-forward"></i>
					<span>下一首播放</span>
				</div>
				<div class="action-btn" @click="shareMusic">
					<i class="fas fa-share-alt"></i>
					<span>分享</span>
				</div>
			</div>
		</div>
		
		<!-- 分享海报弹窗 -->
		<SharePoster v-model:show="showSharePoster" :music="musicDetail" />
	</div>
</template>

<script>
export default {
	name: 'MusicDetail'
}
</script>

<script setup>
	import { ref, computed, onMounted, watch } from 'vue';
	import { useRoute, useRouter } from 'vue-router';
	import { useMusicStore } from '@/stores/music';
	import { getMusicDetail } from '@/api';
	import { showToast } from 'vant';
	import { getCoverUrl } from '@/utils/image';
	import SharePoster from '@/components/SharePoster.vue';

	const route = useRoute();
	const router = useRouter();
	const musicStore = useMusicStore();

	const musicDetail = ref(null);
	const musicId = computed(() => route.params.id);
	const isFavorite = computed(() => (musicDetail.value ? musicStore.isFavorite(musicDetail.value.id) : false));
	const showSharePoster = ref(false);

	// 加载歌曲详情
	const loadMusicDetail = async () => {
		try {
			console.log('加载音乐详情，ID:', musicId.value);
			const res = await getMusicDetail(musicId.value);
			if (res.data) {
				musicDetail.value = res.data;
				console.log('音乐详情加载成功:', res.data);
			}
		} catch (error) {
			console.error('获取歌曲详情失败:', error);
			showToast('加载失败');
			router.back();
		}
	};

	// 格式化文件大小
	const formatSize = (bytes) => {
		if (!bytes) return '未知';
		const mb = bytes / (1024 * 1024);
		return mb.toFixed(2) + ' MB';
	};

	// 格式化时长
	const formatDuration = (seconds) => {
		if (!seconds) return '00:00';
		const mins = Math.floor(seconds / 60);
		const secs = Math.floor(seconds % 60);
		return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
	};

	// 收藏/取消收藏
	const handleFavorite = async () => {
		if (musicDetail.value) {
			const added = await musicStore.toggleFavorite(musicDetail.value);
			showToast(added ? '已添加到我的喜欢' : '已取消喜欢');
		}
	};

	// 添加到播放列表
	const addToPlaylist = () => {
		if (musicDetail.value) {
			musicStore.addToPlaylist(musicDetail.value);
			showToast('已添加到播放列表');
		}
	};

	// 分享音乐
	const shareMusic = () => {
		if (musicDetail.value) {
			showSharePoster.value = true;
		}
	};

	// 监听路由参数变化，重新加载详情
	watch(musicId, (newId, oldId) => {
		console.log('音乐ID变化:', oldId, '->', newId);
		if (newId && newId !== oldId) {
			loadMusicDetail();
		}
	});

	// 监听当前播放音乐变化（如果是从播放器进入详情页）
	watch(() => musicStore.currentMusic, (newMusic) => {
		if (newMusic && newMusic.id && newMusic.id.toString() === musicId.value) {
			console.log('当前播放音乐变化，更新详情');
			musicDetail.value = newMusic;
		}
	}, { deep: true });

	onMounted(() => {
		console.log('MusicDetail 页面挂载，音乐ID:', musicId.value);
		loadMusicDetail();
	});
</script>

<style lang="scss" scoped>
	.music-detail-page {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		z-index: 98;

		.background {
			position: absolute;
			top: 0;
			left: 0;
			right: 0;
			bottom: 0;
			background-size: cover;
			background-position: center;
			filter: blur(50px);
			opacity: 0.6;
			transform: scale(1.2);
		}

		.detail-content {
			position: relative;
			height: 100%;
			display: flex;
			flex-direction: column;
			padding: 0 20px;
			color: #fff;
			overflow-y: auto;

			.top-bar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding-top: calc(20px + env(safe-area-inset-top));
				padding-top: calc(20px + constant(safe-area-inset-top)); /* iOS 11.0 */
				margin-bottom: 30px;

				i {
					font-size: 20px;
					cursor: pointer;
					padding: 10px;
				}

				.title {
					font-size: 18px;
					font-weight: 600;
				}
			}

			.cover-section {
				display: flex;
				justify-content: center;
				margin-bottom: 30px;

				.cover-wrapper {
					width: 180px;
					height: 180px;
					border-radius: 12px;
					overflow: hidden;
					box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);

					img {
						width: 100%;
						height: 100%;
						object-fit: cover;
					}
				}
			}

			.info-section {
				background: rgba(255, 255, 255, 0.1);
				backdrop-filter: blur(20px);
				border-radius: 16px;
				padding: 20px;
				margin-bottom: 20px;

				.info-item {
					display: flex;
					justify-content: space-between;
					align-items: center;
					padding: 12px 0;
					border-bottom: 1px solid rgba(255, 255, 255, 0.1);

					&:last-child {
						border-bottom: none;
					}

					.label {
						font-size: 15px;
						opacity: 0.8;
					}

					.value {
						font-size: 15px;
						font-weight: 500;
						text-align: right;
						max-width: 60%;
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
					}
				}
			}

			.actions-section {
				display: grid;
				grid-template-columns: repeat(3, 1fr);
				gap: 12px;

				.action-btn {
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: center;
					padding: 16px 8px;
					background: rgba(255, 255, 255, 0.1);
					backdrop-filter: blur(20px);
					border-radius: 16px;
					cursor: pointer;
					transition: all 0.3s;

					&:active {
						transform: scale(0.95);
						background: rgba(255, 255, 255, 0.2);
					}

					i {
						font-size: 24px;
						margin-bottom: 10px;
					}

					span {
						font-size: 13px;
						text-align: center;
					}
				}
			}

			&::-webkit-scrollbar {
				width: 4px;
			}

			&::-webkit-scrollbar-thumb {
				background: rgba(255, 255, 255, 0.3);
				border-radius: 2px;
			}
		}
	}
</style>

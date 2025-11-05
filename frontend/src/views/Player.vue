<template>
	<div class="player-page">
		<div class="background" :style="{ backgroundImage: `url(${getCoverUrl(currentMusic?.cover)})` }"></div>

		<div class="player-content">
			<!-- 顶部操作栏 -->
			<div class="top-bar">
				<i class="fas fa-chevron-down" @click="$router.back()"></i>
				<div class="song-info">
					<div class="name">{{ currentMusic?.name || "暂无播放" }}</div>
					<div class="artist">{{ currentMusic?.artist || "" }}</div>
				</div>
				<!-- 显示当前播放歌曲的操作菜单 -->
				<i class="fas fa-ellipsis-v" @click="showMenu"></i>
			</div>

			<!-- 封面 -->
			<div class="cover-container">
				<div class="cover-wrapper">
					<img :src="getCoverUrl(currentMusic?.cover)" class="cover rotating" :class="{ paused: !isPlaying }" />
				</div>
			</div>

			<!-- 歌词 -->
			<div class="lyric-container" v-if="currentMusic?.lyric">
				<div class="lyric-line" v-for="(line, index) in parsedLyrics" :key="index">
					{{ line.text }}
				</div>
			</div>
			<div class="lyric-container" v-else>
				<div class="no-lyric">暂无歌词</div>
			</div>

			<!-- 进度条 -->
			<div class="progress-container">
				<span class="time">{{ formatTime(isDragging ? (dragValue / 100) * duration : currentTime) }}</span>
				<van-slider 
					v-model="sliderValue" 
					@update:model-value="handleDragging"
					@change="handleSeek"
					@drag-start="handleDragStart"
					:bar-height="4" 
					:button-size="16"
					active-color="#fff" 
					inactive-color="rgba(255,255,255,0.3)" 
				/>
				<span class="time">{{ formatTime(duration) }}</span>
			</div>

			<!-- 控制按钮 -->
			<div class="controls">
				<i class="fas control-icon" :class="playModeIcon" @click="toggleMode"></i>
				<i class="fas fa-step-backward control-icon" @click="playPrev"></i>
				<i class="fas control-icon play-btn" :class="isPlaying ? 'fa-pause-circle' : 'fa-play-circle'" @click="togglePlay"></i>
				<i class="fas fa-step-forward control-icon" @click="playNext"></i>
				<i class="fas control-icon" :class="isFavorite ? 'fa-heart' : 'fa-heart'" :style="{ color: isFavorite ? '#ff4757' : '#fff' }" @click="handleFavorite"></i>
			</div>
		</div>

		<!-- 操作菜单 -->
		<MusicActionSheet 
			v-model:show="showActionSheet" 
			:music="currentMusic"
		/>
	</div>
</template>

<script>
export default {
  name: 'Player'
}
</script>

<script setup>
	import { ref, computed, watch } from "vue";
	import { useRouter } from "vue-router";
	import { useMusicStore } from "@/stores/music";
	import { showToast } from "vant";
	import { getCoverUrl } from "@/utils/image";
	import MusicActionSheet from "@/components/MusicActionSheet.vue";

	const router = useRouter();
	const musicStore = useMusicStore();
	const currentMusic = computed(() => musicStore.currentMusic);
	const isPlaying = computed(() => musicStore.isPlaying);
	const currentTime = computed(() => musicStore.currentTime);
	const duration = computed(() => musicStore.duration);
	const playMode = computed(() => musicStore.playMode);
	const isFavorite = computed(() => (currentMusic.value ? musicStore.isFavorite(currentMusic.value.id) : false));

	const sliderValue = ref(0);
	const parsedLyrics = ref([]);
	const isDragging = ref(false); // 是否正在拖动
	const dragValue = ref(0); // 拖动时的临时值
	
	// 使用 store 中的 showActionSheet 状态
	const showActionSheet = computed({
		get: () => musicStore.showActionSheet,
		set: (val) => musicStore.showActionSheet = val
	});

	// 监听播放时间变化，只在非拖动状态下更新进度条
	watch(currentTime, (val) => {
		if (!isDragging.value && duration.value > 0) {
			sliderValue.value = (val / duration.value) * 100;
		}
	});

	watch(
		() => currentMusic.value?.lyric,
		(lyric) => {
			if (lyric) {
				parsedLyrics.value = parseLyric(lyric);
			}
		}
	);

	const playModeIcon = computed(() => {
		const icons = {
			loop: "fa-repeat",
			random: "fa-random",
			single: "fa-redo",
		};
		return icons[playMode.value];
	});

	const togglePlay = () => {
		musicStore.togglePlay();
	};

	const playNext = () => {
		musicStore.playNext();
	};

	const playPrev = () => {
		musicStore.playPrev();
	};

	// 开始拖动
	const handleDragStart = () => {
		isDragging.value = true;
		dragValue.value = sliderValue.value;
	};

	// 拖动中
	const handleDragging = (value) => {
		if (isDragging.value) {
			dragValue.value = value;
			sliderValue.value = value;
		}
	};

	// 拖动结束
	const handleSeek = (value) => {
		isDragging.value = false;
		const time = (value / 100) * duration.value;
		musicStore.seek(time);
		sliderValue.value = value;
	};

	const toggleMode = () => {
		const mode = musicStore.togglePlayMode();
		const modeText = {
			loop: "列表循环",
			random: "随机播放",
			single: "单曲循环",
		};
		showToast(modeText[mode]);
	};

	const handleFavorite = () => {
		if (currentMusic.value) {
			const added = musicStore.toggleFavorite(currentMusic.value);
			showToast(added ? "已添加到我的喜欢" : "已取消喜欢");
		}
	};

	const formatTime = (seconds) => {
		const mins = Math.floor(seconds / 60);
		const secs = Math.floor(seconds % 60);
		return `${mins}:${secs.toString().padStart(2, "0")}`;
	};

	const parseLyric = (lyricStr) => {
		const lines = lyricStr.split("\n");
		return lines
			.map((line) => {
				const match = line.match(/\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/);
				if (match) {
					return {
						time: parseInt(match[1]) * 60 + parseInt(match[2]) + parseInt(match[3]) / 1000,
						text: match[4].trim(),
					};
				}
				return { time: 0, text: line };
			})
			.filter((item) => item.text);
	};

	// 显示操作菜单
	const showMenu = () => {
		if (!currentMusic.value) {
			showToast('当前没有播放歌曲');
			return;
		}
		
		showActionSheet.value = true;
	};
</script>

<style lang="scss" scoped>
	.player-page {
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

		.player-content {
			position: relative;
			height: 100%;
			display: flex;
			flex-direction: column;
			padding: 20px 20px 100px 20px;
			color: #fff;
			overflow-y: auto;

			.top-bar {
				display: flex;
				align-items: center;
				justify-content: space-between;
				margin-bottom: 30px;

				i {
					font-size: 20px;
					cursor: pointer;
					padding: 10px;
				}

				.song-info {
					flex: 1;
					text-align: center;

					.name {
						font-size: 16px;
						font-weight: 600;
						margin-bottom: 4px;
					}

					.artist {
						font-size: 13px;
						opacity: 0.8;
					}
				}
			}

			.cover-container {
				flex: 1;
				display: flex;
				align-items: center;
				justify-content: center;
				margin-bottom: 20px;

				.cover-wrapper {
					width: 280px;
					height: 280px;
					border-radius: 50%;
					padding: 20px;
					background: rgba(255, 255, 255, 0.1);
					backdrop-filter: blur(20px);
					box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);

					.cover {
						width: 100%;
						height: 100%;
						border-radius: 50%;
						object-fit: cover;

						&.rotating {
							animation: rotate 20s linear infinite;
						}

						&.paused {
							animation-play-state: paused;
						}
					}
				}
			}

			.lyric-container {
				height: 80px;
				overflow: hidden;
				text-align: center;
				margin-bottom: 20px;

				.lyric-line {
					font-size: 14px;
					line-height: 1.8;
					opacity: 0.7;
				}

				.no-lyric {
					font-size: 14px;
					opacity: 0.5;
					padding-top: 20px;
				}
			}

			.progress-container {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 30px;

				.time {
					font-size: 12px;
					opacity: 0.8;
					min-width: 40px;
					font-variant-numeric: tabular-nums;
				}

				:deep(.van-slider) {
					flex: 1;
					
					.van-slider__bar {
						transition: width 0.1s linear;
					}
					
					.van-slider__button {
						width: 16px;
						height: 16px;
						box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
						transition: transform 0.2s;
						
						&:active {
							transform: scale(1.3);
						}
					}
					
					.van-slider__button-wrapper {
						transition: left 0.1s linear;
					}
				}
			}

			.controls {
				display: flex;
				align-items: center;
				justify-content: space-around;
				padding: 0 20px;
				margin-bottom: 0;

				.control-icon {
					font-size: 28px;
					cursor: pointer;
					transition: all 0.2s;

					&:active {
						transform: scale(0.9);
					}

					&.play-btn {
						font-size: 60px;
					}
				}
			}
		}
	}

	@keyframes rotate {
		from {
			transform: rotate(0deg);
		}
		to {
			transform: rotate(360deg);
		}
	}
</style>

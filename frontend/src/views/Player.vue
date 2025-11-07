<template>
	<div class="player-page">
		<div class="background" :style="{ backgroundImage: `url(${getCoverUrl(currentMusic?.cover)})` }"></div>

		<div class="player-content">
			<!-- 顶部操作栏 -->
			<div class="top-bar">
				<i class="fas fa-chevron-down" @click="$router.back()"></i>
				<div class="song-info">
					<div class="name" ref="nameRef">
						<span class="text-content" :class="{ 'scroll-text': isNameOverflow }">
							{{ currentMusic?.name || "暂无播放" }}
						</span>
					</div>
					<div class="artist" ref="artistRef">
						<span class="text-content" :class="{ 'scroll-text': isArtistOverflow }">
							{{ currentMusic?.artist || "" }}
						</span>
					</div>
				</div>
				<i class="fas fa-ellipsis-v" @click="showMenu"></i>
			</div>

			<!-- 内容区域 -->
			<div class="content-box">
				<!-- 封面区域（包含歌词预览） -->
				<div class="cover-area" v-show="!showFullLyric" @click="toggleLyricMode">
					<div class="cover-wrapper">
						<img :src="getCoverUrl(currentMusic?.cover)" class="cover rotating" :class="{ paused: !isPlaying }" />
					</div>
					
					<!-- 歌词预览 -->
					<div class="lyric-preview">
						<div 
							v-for="(line, index) in parsedLyrics.slice(Math.max(0, currentLyricIndex - 1), currentLyricIndex + 2)" 
							:key="`preview-${index}`"
							class="lyric-line"
							:class="{ active: parsedLyrics[currentLyricIndex] === line }"
						>
							{{ line.text }}
						</div>
						<div v-if="parsedLyrics.length === 0" class="lyric-line">
							{{ currentMusic?.lyric ? '歌词加载中...' : '暂无歌词' }}
						</div>
					</div>
				</div>

				<!-- 全屏歌词区域 -->
				<div class="lyric-full" v-show="showFullLyric" @click="toggleLyricMode">
					<div 
						class="lyric-scroll" 
						ref="lyricScrollRef"
						v-if="parsedLyrics.length > 0"
					>
						<div 
							v-for="(line, index) in parsedLyrics" 
							:key="`lyric-${index}`"
							class="lyric-line"
							:class="{ 
								active: currentLyricIndex === index,
								'plain-text': !isLrcFormat
							}"
							:data-index="index"
						>
							{{ line.text }}
						</div>
					</div>
					<div class="no-lyric" v-else>
						{{ currentMusic?.lyric ? '歌词加载中...' : '暂无歌词' }}
					</div>
				</div>
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
	import { ref, computed, watch, nextTick } from "vue";
	import { useMusicStore } from "@/stores/music";
	import { showToast } from "vant";
	import { getCoverUrl } from "@/utils/image";
	import MusicActionSheet from "@/components/MusicActionSheet.vue";

	const musicStore = useMusicStore();
	const currentMusic = computed(() => musicStore.currentMusic);
	const isPlaying = computed(() => musicStore.isPlaying);
	const currentTime = computed(() => musicStore.currentTime);
	const duration = computed(() => musicStore.duration);
	const playMode = computed(() => musicStore.playMode);
	const isFavorite = computed(() => (currentMusic.value ? musicStore.isFavorite(currentMusic.value.id) : false));

	const sliderValue = ref(0);
	const parsedLyrics = ref([]);
	const isDragging = ref(false);
	const dragValue = ref(0);
	const showFullLyric = ref(false);
	const currentLyricIndex = ref(0);
	const isLrcFormat = ref(true);
	const lyricScrollRef = ref(null);
	const nameRef = ref(null);
	const artistRef = ref(null);
	const isNameOverflow = ref(false);
	const isArtistOverflow = ref(false);
	
	const showActionSheet = computed({
		get: () => musicStore.showActionSheet,
		set: (val) => musicStore.showActionSheet = val
	});

	/**
	 * 解析LRC格式歌词
	 */
	const parseLyric = (lyricStr) => {
		if (!lyricStr) return { lyrics: [], isLrc: false };
		
		let normalizedLyric = lyricStr.replace(/\\n/g, '\n');
		const lines = normalizedLyric.split('\n');
		const lrcPattern = /\[(\d{2}):(\d{2})\.(\d{2,3})\](.*)/;
		const hasLrcFormat = lines.some(line => lrcPattern.test(line));
		
		if (hasLrcFormat) {
			const lyrics = [];
			lines.forEach((line) => {
				const match = line.match(lrcPattern);
				if (match) {
					const minutes = parseInt(match[1], 10);
					const seconds = parseInt(match[2], 10);
					const milliseconds = parseInt(match[3], 10);
					const text = match[4].trim();
					
					if (text && text.length > 0) {
						const time = minutes * 60 + seconds + milliseconds / (match[3].length === 2 ? 100 : 1000);
						lyrics.push({ time, text });
					}
				}
			});
			lyrics.sort((a, b) => a.time - b.time);
			return { lyrics, isLrc: true };
		} else {
			const lyrics = lines
				.filter(line => line.trim())
				.map((line, index) => ({ time: index, text: line.trim() }));
			return { lyrics, isLrc: false };
		}
	};
	
	/**
	 * 更新当前歌词行
	 */
	const updateCurrentLyric = (time) => {
		if (!isLrcFormat.value || parsedLyrics.value.length === 0) return;
		
		for (let i = parsedLyrics.value.length - 1; i >= 0; i--) {
			if (time >= parsedLyrics.value[i].time) {
				if (currentLyricIndex.value !== i) {
					currentLyricIndex.value = i;
					scrollToCurrentLyric();
				}
				return;
			}
		}
		
		if (currentLyricIndex.value !== -1) {
			currentLyricIndex.value = -1;
		}
	};
	
	/**
	 * 滚动到当前歌词
	 */
	const scrollToCurrentLyric = () => {
		nextTick(() => {
			if (!lyricScrollRef.value) return;
			
			const container = lyricScrollRef.value;
			const activeLine = container.querySelector('.lyric-line.active');
			
			if (activeLine) {
				const containerHeight = container.clientHeight;
				const lineOffsetTop = activeLine.offsetTop;
				const lineHeight = activeLine.clientHeight;
				const scrollTop = lineOffsetTop - containerHeight / 2 + lineHeight / 2;
				
				container.scrollTo({
					top: scrollTop,
					behavior: 'smooth'
				});
			}
		});
	};

	// 监听播放时间
	watch(currentTime, (val) => {
		if (!isDragging.value && duration.value > 0) {
			sliderValue.value = (val / duration.value) * 100;
		}
		updateCurrentLyric(val);
	});

	// 监听歌词变化
	watch(
		() => currentMusic.value?.lyric,
		(lyric) => {
			if (lyric) {
				try {
					const result = parseLyric(lyric);
					parsedLyrics.value = result.lyrics;
					isLrcFormat.value = result.isLrc;
					currentLyricIndex.value = 0;
				} catch (error) {
					console.error('歌词解析错误:', error);
					parsedLyrics.value = [];
					isLrcFormat.value = true;
					currentLyricIndex.value = 0;
				}
			} else {
				parsedLyrics.value = [];
				isLrcFormat.value = true;
				currentLyricIndex.value = 0;
			}
		},
		{ immediate: true }
	);

	// 监听歌曲变化
	watch(
		() => currentMusic.value,
		() => {
			nextTick(() => {
				checkTextOverflow();
			});
		},
		{ immediate: true }
	);

	// 检查文字溢出
	const checkTextOverflow = () => {
		if (nameRef.value) {
			const container = nameRef.value;
			const content = container.querySelector('.text-content');
			if (content) {
				isNameOverflow.value = content.scrollWidth > container.clientWidth;
			}
		}
		
		if (artistRef.value) {
			const container = artistRef.value;
			const content = container.querySelector('.text-content');
			if (content) {
				isArtistOverflow.value = content.scrollWidth > container.clientWidth;
			}
		}
	};

	const formatTime = (seconds) => {
		const mins = Math.floor(seconds / 60);
		const secs = Math.floor(seconds % 60);
		return `${mins}:${secs.toString().padStart(2, "0")}`;
	};

	const playModeIcon = computed(() => {
		const icons = {
			loop: "fa-repeat",
			random: "fa-random",
			single: "fa-redo",
		};
		return icons[playMode.value];
	});

	const togglePlay = () => musicStore.togglePlay();
	const playNext = () => musicStore.playNext();
	const playPrev = () => musicStore.playPrev();

	const handleDragStart = () => {
		isDragging.value = true;
		dragValue.value = sliderValue.value;
	};

	const handleDragging = (value) => {
		if (isDragging.value) {
			dragValue.value = value;
			sliderValue.value = value;
		}
	};

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
	
	const toggleLyricMode = () => {
		if (currentMusic.value?.lyric) {
			showFullLyric.value = !showFullLyric.value;
			if (showFullLyric.value) {
				nextTick(() => scrollToCurrentLyric());
			}
		}
	};

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
			background-repeat: no-repeat;
			filter: blur(50px);
			opacity: 0.6;
			transform: scale(1.2);
		}

		.player-content {
			position: relative;
			height: 100%;
			color: #fff;
			
			.top-bar {
				position: fixed;
				top: 0;
				left: 0;
				right: 0;
				display: flex;
				align-items: center;
				justify-content: space-between;
				padding: 20px;
				z-index: 10;

				i {
					font-size: 20px;
					cursor: pointer;
					padding: 10px;
					flex-shrink: 0;
				}

				.song-info {
					flex: 1;
					text-align: center;
					padding: 0 10px;
					max-width: calc(100% - 120px);
					overflow: hidden;

					.name, .artist {
						overflow: hidden;
						white-space: nowrap;
						
						.text-content {
							display: inline-block;
							
							&.scroll-text {
								padding-right: 50px;
								animation: marquee 12s linear infinite;
							}
						}
					}

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
			
			.content-box {
				.cover-area {
					position: fixed;
					top: 100px;
					left: 0;
					right: 0;
					height: 420px;
					display: flex;
					flex-direction: column;
					align-items: center;
					justify-content: flex-start;
					z-index: 1;
					cursor: pointer;
					
					.cover-wrapper {
						width: 280px;
						height: 280px;
						border-radius: 50%;
						padding: 20px;
						overflow: hidden;
						background: rgba(255, 255, 255, 0.1);
						backdrop-filter: blur(20px);
						box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
						margin-bottom: 20px;

						.cover {
							width: 100%;
							height: 100%;
							border-radius: 50%;
							object-fit: cover;
							object-position: center;

							&.rotating {
								animation: rotate 20s linear infinite;
							}

							&.paused {
								animation-play-state: paused;
							}
						}
					}
					
					.lyric-preview {
						width: 100%;
						height: 120px;
						padding: 0 40px;
						display: flex;
						flex-direction: column;
						align-items: center;
						justify-content: center;
						
						.lyric-line {
							font-size: 13px;
							line-height: 2;
							opacity: 0.5;
							transition: all 0.3s ease;
							white-space: nowrap;
							overflow: hidden;
							text-overflow: ellipsis;
							
							&.active {
								opacity: 1;
								font-size: 16px;
								font-weight: 600;
								color: #fff;
								text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
							}
						}
					}
				}

				.lyric-full {
					position: fixed;
					top: 100px;
					left: 0;
					right: 0;
					height: 420px;
					overflow: hidden;
					text-align: center;
					z-index: 2;
					cursor: pointer;

					.lyric-scroll {
						height: 100%;
						overflow-y: auto;
						padding: 60px 20px;
						
						&::-webkit-scrollbar {
							display: none;
						}
						scrollbar-width: none;
						-ms-overflow-style: none;
					}

					.lyric-line {
						font-size: 15px;
						line-height: 2.4;
						opacity: 0.5;
						transition: all 0.3s ease;
						padding: 6px 0;
						
						&.active {
							opacity: 1;
							font-size: 18px;
							font-weight: 600;
							color: #fff;
							text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
						}
						
						&.plain-text {
							opacity: 0.7;
							font-size: 16px;
							line-height: 2.2;
							
							&.active {
								opacity: 0.7;
								font-size: 16px;
								font-weight: normal;
							}
						}
					}

					.no-lyric {
						height: 100%;
						display: flex;
						align-items: center;
						justify-content: center;
						font-size: 16px;
						opacity: 0.5;
					}
				}
			}

			.progress-container {
				position: fixed;
				bottom: 180px;
				left: 20px;
				right: 20px;
				display: flex;
				align-items: center;
				gap: 10px;
				z-index: 10;
				padding: 10px 0;

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
				position: fixed;
				bottom: 100px;
				left: 0;
				right: 0;
				display: flex;
				align-items: center;
				justify-content: space-around;
				padding: 0 40px;
				z-index: 10;

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
	
	@keyframes marquee {
		0%, 10% {
			transform: translateX(0);
		}
		90%, 100% {
			transform: translateX(-50%);
		}
	}
</style>

<template>
	<van-popup
		v-model:show="visible"
		position="bottom"
		:style="{ height: '60%', borderRadius: '20px 20px 0 0' }"
		:close-on-click-overlay="true"
		@closed="onClosed"
	>
		<div class="playlist-sheet">
			<!-- 头部 -->
			<div class="header">
				<div class="title">
					<i class="fas fa-list-ul"></i>
					<span>播放列表 ({{ playlist.length }})</span>
				</div>
				<div class="actions">
					<span class="mode-text" @click="toggleMode">
						<i class="fas" :class="playModeIcon"></i>
						{{ modeText }}
					</span>
					<i class="fas fa-trash-alt" @click="clearPlaylist"></i>
				</div>
			</div>

			<!-- 播放列表 -->
			<div class="list-container" ref="listContainerRef">
				<div 
					v-for="(item, index) in playlist" 
					:key="`${item.id}-${index}`"
					class="music-item"
					:class="{ active: index === currentIndex }"
					:data-index="index"
					@click="playMusic(item, index)"
				>
					<div class="music-info">
            <!-- 序号 -->
            <div class="index">{{ index + 1 }}</div>
						<i v-if="index === currentIndex" class="fas fa-volume-up playing-icon"></i>
						<!-- 封面 -->
						<img :src="getCoverUrl(item.cover)" class="cover" />
						<div class="text-content">
							<div class="name">{{ item.name }}</div>
							<div class="artist">{{ item.artist }}</div>
						</div>
					</div>
					<i class="fas fa-times delete-icon" @click.stop="removeMusic(index)"></i>
				</div>
				<div v-if="playlist.length === 0" class="empty-text">
					播放列表为空
				</div>
			</div>
		</div>
	</van-popup>
</template>

<script setup>
	import { computed, ref, watch, nextTick } from 'vue';
	import { useMusicStore } from '@/stores/music';
	import { showToast, showConfirmDialog } from 'vant';
	import { getCoverUrl } from '@/utils/image';

	const props = defineProps({
		show: {
			type: Boolean,
			default: false
		}
	});

	const emit = defineEmits(['update:show']);

	const musicStore = useMusicStore();
	const listContainerRef = ref(null);

	const visible = computed({
		get: () => props.show,
		set: (val) => emit('update:show', val)
	});

	const playlist = computed(() => musicStore.playlist);
	const currentIndex = computed(() => musicStore.currentIndex);
	const playMode = computed(() => musicStore.playMode);

	const playModeIcon = computed(() => {
		const icons = {
			loop: 'fa-repeat',
			random: 'fa-random',
			single: 'fa-redo'
		};
		return icons[playMode.value];
	});

	const modeText = computed(() => {
		const texts = {
			loop: '列表循环',
			random: '随机播放',
			single: '单曲循环'
		};
		return texts[playMode.value];
	});

	const playMusic = async (music, index) => {
		await musicStore.playMusic(music, index);
	};

	const removeMusic = async (index) => {
		if (playlist.value.length === 1) {
			showToast('至少保留一首歌曲');
			return;
		}
		musicStore.removeFromPlaylist(index);
		showToast('已移除');
	};

	const clearPlaylist = async () => {
		if (playlist.value.length === 0) {
			return;
		}
		
		try {
			await showConfirmDialog({
				title: '提示',
				message: '确定清空播放列表吗？',
				confirmButtonText: '清空',
				cancelButtonText: '取消'
			});
			musicStore.clearPlaylist();
			visible.value = false;
			showToast('已清空播放列表');
		} catch {
			// 取消操作
		}
	};

	const toggleMode = () => {
		const mode = musicStore.togglePlayMode();
		showToast(modeText.value);
	};

	const onClosed = () => {
		visible.value = false;
	};

	// 滚动到当前播放的音乐，让其显示在合适的位置
	const scrollToCurrentMusic = () => {
		if (!listContainerRef.value || currentIndex.value < 0 || playlist.value.length === 0) {
			return;
		}

		nextTick(() => {
			const container = listContainerRef.value;
			const activeItem = container.querySelector(`[data-index="${currentIndex.value}"]`);
			
			if (activeItem) {
				let scrollTop;
				
				// 如果当前播放的是第一首，则滚动到顶部
				if (currentIndex.value === 0) {
					scrollTop = 0;
				} else {
					// 否则让当前播放的歌曲显示在第二个位置（上面显示一首）
					const itemHeight = activeItem.offsetHeight;
					scrollTop = activeItem.offsetTop - itemHeight;
				}
				
				container.scrollTo({
					top: Math.max(0, scrollTop), // 确保不会滚动到负值
					behavior: 'smooth'
				});
			}
		});
	};

	// 监听弹窗显示状态，打开时滚动到当前播放位置
	watch(visible, (newVal) => {
		if (newVal) {
			scrollToCurrentMusic();
		}
	});
</script>

<style lang="scss" scoped>
	.playlist-sheet {
		height: 100%;
		display: flex;
		flex-direction: column;
		background: #fff;

		.header {
			flex-shrink: 0;
			display: flex;
			justify-content: space-between;
			align-items: center;
			padding: 20px;
			border-bottom: 1px solid #f0f0f0;

			.title {
				display: flex;
				align-items: center;
				gap: 8px;
				font-size: 16px;
				font-weight: 600;

				i {
					color: #1989fa;
				}
			}

			.actions {
				display: flex;
				align-items: center;
				gap: 20px;
				font-size: 14px;
				color: #666;

				.mode-text {
					display: flex;
					align-items: center;
					gap: 5px;
					cursor: pointer;
					transition: color 0.2s;

					&:active {
						color: #1989fa;
					}
				}

				.fa-trash-alt {
					cursor: pointer;
					font-size: 16px;
					transition: color 0.2s;

					&:active {
						color: #ff4757;
					}
				}
			}
		}

		.list-container {
			flex: 1;
			overflow-y: auto;
      padding-bottom: 20px;
			
			&::-webkit-scrollbar {
				width: 4px;
			}
			
			&::-webkit-scrollbar-thumb {
				background: #ddd;
				border-radius: 2px;
			}
			
			&::-webkit-scrollbar-track {
				background: transparent;
			}

			.music-item {
				display: flex;
				justify-content: space-between;
				align-items: center;
				padding: 10px;
				transition: all 0.2s;
				cursor: pointer;
        border-bottom: 1px solid #f5f5f5;

				&:active {
					background: #f7f8fa;
					transform: scale(0.98);
				}

				&.active {
					background: #f0f8ff;

					.music-info {
						.index {
							color: #1989fa;
						}
						
						.text-content {
							.name {
								color: #1989fa;
								font-weight: 600;
							}
						}
					}
				}

				.music-info {
					flex: 1;
					display: flex;
					align-items: center;
					gap: 8px;
					overflow: hidden;

          .index {
            width: 24px;
            text-align: center;
            font-size: 13px;
            color: #999;
            font-weight: 600;
            flex-shrink: 0;
          }

					.playing-icon {
						color: #1989fa;
						font-size: 14px;
						flex-shrink: 0;
						animation: pulse 1.5s ease-in-out infinite;
						margin-left: -4px;
						margin-right: 4px;
					}

					.cover {
						width: 45px;
						height: 45px;
						border-radius: 8px;
						object-fit: cover;
						flex-shrink: 0;
					}

					.text-content {
						flex: 1;
						overflow: hidden;
						min-width: 0;

						.name {
							font-size: 14px;
							font-weight: 500;
							margin-bottom: 4px;
							overflow: hidden;
							text-overflow: ellipsis;
							white-space: nowrap;
						}

						.artist {
							font-size: 12px;
							color: #999;
							overflow: hidden;
							text-overflow: ellipsis;
							white-space: nowrap;
						}
					}
				}

				.delete-icon {
					flex-shrink: 0;
					color: #ccc;
					font-size: 18px;
					padding: 8px;
          margin-left: 10px;
					transition: all 0.2s;

					&:active {
						color: #ff4757;
						transform: scale(0.9);
					}
				}
			}

			.empty-text {
				text-align: center;
				padding: 60px 20px;
				color: #999;
				font-size: 14px;
			}
		}

    // 隐藏滚动条
    ::-webkit-scrollbar {
      display: none;
    }
    ::-webkit-scrollbar-thumb {
      display: none;
    }
    ::-webkit-scrollbar-track {
      display: none;
    }
	}

	@keyframes pulse {
		0%, 100% {
			opacity: 1;
		}
		50% {
			opacity: 0.5;
		}
	}
</style>


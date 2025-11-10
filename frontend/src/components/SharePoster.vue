<template>
	<van-popup :show="show" @closed="handleClose" @update:show="handleUpdateShow" :style="{ background: 'transparent' }">
		<div class="share-poster-container">
			<div class="poster-wrapper" ref="posterRef">
				<div class="poster-content">
					<!-- 背景模糊层 -->
					<!-- <div class="poster-bg" :style="{ backgroundImage: `url(${getCoverUrl(music?.cover)})` }"></div> -->
					<div class="poster-bg"></div>
					
					<!-- 主要内容 -->
					<div class="poster-main">
						<!-- 封面 -->
						<div class="poster-cover">
							<img :src="getCoverUrl(music?.cover)" alt="封面" />
						</div>
						
						<!-- 歌曲信息 -->
						<div class="poster-info">
							<div class="music-name">{{ music?.name || '未知歌曲' }}</div>
							<div class="music-artist">{{ music?.artist || '未知艺术家' }} {{ music?.album ? ' - ' + music?.album : '' }}</div>
						</div>
						
						<!-- 底部区域 -->
						<div class="poster-bottom">
							<!-- 左侧Logo和提示 -->
							<div class="poster-footer">
								<div class="logo-box">
									<i class="fas fa-music"></i>
									<span>Music Player</span>
								</div>
								<div class="tip-text">长按识别可播放歌曲</div>
							</div>
							
							<!-- 右侧二维码 -->
							<div class="qrcode-section">
								<div class="qrcode-wrapper">
									<canvas ref="qrcodeCanvas"></canvas>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<!-- 操作按钮 -->
			<div class="action-buttons">
				<div class="action-btn" @click="saveImage">
					<i class="fas fa-download"></i>
					<span>保存图片</span>
				</div>
				<div class="action-btn" @click="handleClose">
					<i class="fas fa-times"></i>
					<span>关闭</span>
				</div>
			</div>
		</div>
	</van-popup>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
import QRCode from 'qrcode';
import { getCoverUrl } from '@/utils/image';
import { showToast } from 'vant';
import html2canvas from 'html2canvas';

const props = defineProps({
	show: {
		type: Boolean,
		default: false
	},
	music: {
		type: Object,
		default: null
	}
});

const emit = defineEmits(['update:show']);

const posterRef = ref(null);
const qrcodeCanvas = ref(null);

// 生成二维码
const generateQRCode = async () => {
	if (!props.music || !qrcodeCanvas.value) return;
	
	try {
		// 生成分享链接，包含歌曲ID
		const baseUrl = window.location.origin + window.location.pathname;
		const shareUrl = `${baseUrl}#/player?musicId=${props.music.id}`;
		
		// 生成二维码（更小的尺寸）
		await QRCode.toCanvas(qrcodeCanvas.value, shareUrl, {
			width: 80,
			margin: 0,
			color: {
				dark: '#000000',
				light: '#ffffff'
			}
		});
	} catch (error) {
		console.error('生成二维码失败:', error);
		showToast('生成二维码失败');
	}
};

// 监听弹窗显示状态
watch(() => props.show, async (newVal) => {
	if (newVal && props.music) {
		await nextTick();
		await generateQRCode();
	}
});

// 保存图片
const saveImage = async () => {
	if (!posterRef.value) return;
	
	try {
		showToast('正在生成图片...');
		
		// 使用html2canvas生成图片
		const canvas = await html2canvas(posterRef.value, {
			backgroundColor: null,
			scale: 2,
			useCORS: true,
			allowTaint: true
		});
		
		// 转换为blob并下载
		canvas.toBlob((blob) => {
			const url = URL.createObjectURL(blob);
			const link = document.createElement('a');
			link.href = url;
			link.download = `${props.music?.name || 'music'}-分享海报.png`;
			link.click();
			URL.revokeObjectURL(url);
			showToast('图片已保存');
		}, 'image/png');
	} catch (error) {
		console.error('保存图片失败:', error);
		showToast('保存图片失败');
	}
};

const handleClose = () => {
	emit('update:show', false);
};

const handleUpdateShow = (value) => {
	emit('update:show', value);
};
</script>

<style lang="scss" scoped>
.share-poster-container {
	padding: 20px;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 20px;
	
	.poster-wrapper {
		width: 300px;
		border-radius: 24px;
		overflow: hidden;
		box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
		
		.poster-content {
			position: relative;
			width: 100%;
			min-height: 480px;
			padding: 30px 25px 25px;
			background: #fff;
			display: flex;
			flex-direction: column;
			
			.poster-bg {
				position: absolute;
				top: 0;
				left: 0;
				right: 0;
				bottom: 0;
				background: #764ba2;
				// background-size: cover;
				// background-position: center;
				filter: blur(40px) brightness(0.8);
				opacity: 0.7;
				transform: scale(1.2);
			}
			
			.poster-main {
				position: relative;
				display: flex;
				flex-direction: column;
				align-items: center;
				flex: 1;
				
				.poster-cover {
					width: 240px;
					height: 240px;
					border-radius: 16px;
					overflow: hidden;
					box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
					margin-bottom: 24px;
					
					img {
						width: 100%;
						height: 100%;
						object-fit: cover;
					}
				}
				
				.poster-info {
					width: 100%;
					text-align: center;
					margin-bottom: auto;
					padding-bottom: 30px;
					
					.music-name {
						font-size: 20px;
						font-weight: 700;
						color: #ffffff;
						text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
						margin-bottom: 10px;
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
						padding: 0 15px;
					}
					
					.music-artist {
						font-size: 14px;
						color: rgba(255, 255, 255, 0.85);
						text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
						overflow: hidden;
						text-overflow: ellipsis;
						white-space: nowrap;
						padding: 0 15px;
					}
				}
				
				.poster-bottom {
					width: 100%;
					display: flex;
					align-items: flex-end;
					justify-content: space-between;
					gap: 15px;
					
					.poster-footer {
						display: flex;
						flex-direction: column;
						gap: 6px;
						flex: 1;
						
						.logo-box {
							display: flex;
							align-items: center;
							gap: 6px;
							color: #ffffff;
							font-size: 14px;
							font-weight: 600;
							text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
							
							i {
								font-size: 16px;
								color: #764ba2;
								filter: drop-shadow(0 1px 3px rgba(0, 0, 0, 0.2));
							}
						}
						
						.tip-text {
							font-size: 11px;
							color: rgba(255, 255, 255, 0.7);
							text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
							line-height: 1.4;
						}
					}
					
					.qrcode-section {
						flex-shrink: 0;
						
						.qrcode-wrapper {
							padding: 6px;
							background: #ffffff;
							border-radius: 10px;
							box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
							
							canvas {
								display: block;
								border-radius: 6px;
								width: 75px !important;
								height: 75px !important;
							}
						}
					}
				}
			}
		}
	}
	
	.action-buttons {
		display: flex;
		gap: 15px;
		
		.action-btn {
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 8px;
			padding: 12px 24px;
			background: rgba(255, 255, 255, 0.95);
			backdrop-filter: blur(10px);
			border-radius: 12px;
			cursor: pointer;
      opacity: 0.8;
			transition: all 0.3s;
			box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
			
			&:active {
				transform: scale(0.95);
			}
			
			i {
				font-size: 20px;
				color: #764ba2;
			}
			
			span {
				font-size: 12px;
				color: #333;
				font-weight: 500;
			}
			
			&:last-child i {
				color: #666;
			}
		}
	}
}

:deep(.van-popup) {
	background: transparent !important;
}
</style>


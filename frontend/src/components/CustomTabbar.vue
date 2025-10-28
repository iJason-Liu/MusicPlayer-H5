<template>
	<div class="custom-tabbar">
		<!-- 滑动背景指示器 -->
		<div class="active-indicator" :style="{ transform: `translateY(-50%) translateX(${activeIndex * 100}%)` }"></div>

		<div v-for="(item, index) in tabs" :key="item.path" class="tab-item" :class="{ active: isActive(item.path) }" @click="handleClick(item.path, index)">
			<i :class="item.icon"></i>
			<span class="tab-text">{{ item.label }}</span>
		</div>
	</div>
</template>

<script setup>
	import { ref, watch } from "vue";
	import { useRouter, useRoute } from "vue-router";

	const router = useRouter();
	const route = useRoute();
	const activeIndex = ref(0);

	const tabs = [
		{ path: "/home", icon: "fas fa-music", label: "音乐库" },
		{ path: "/player", icon: "fas fa-play-circle", label: "播放" },
		{ path: "/mine", icon: "fas fa-user", label: "我的" },
	];

	const isActive = (path) => {
		return route.path.startsWith(path);
	};

	const handleClick = (path, index) => {
		activeIndex.value = index;
		router.push(path);
	};

	// 监听路由变化，更新 activeIndex
	watch(
		() => route.path,
		(newPath) => {
			const index = tabs.findIndex((tab) => newPath.startsWith(tab.path));
			if (index !== -1) {
				activeIndex.value = index;
			}
		},
		{ immediate: true }
	);
</script>

<style lang="scss" scoped>
	.custom-tabbar {
		position: fixed;
		bottom: 0;
		left: 10%;
		right: 10%;
		width: 80%;
		height: 60px;
		border-radius: 60px;
		background: rgba(255, 255, 255, 0.7);
		backdrop-filter: blur(40px) saturate(180%);
		-webkit-backdrop-filter: blur(40px) saturate(180%);
		display: flex;
		align-items: center;
		justify-content: space-around;
		padding: 0 8px;
		box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
		z-index: 1000;
		position: relative;

		// 滑动背景指示器
		.active-indicator {
			position: absolute;
			left: 8px;
			top: 50%;
			width: calc(33.333% - 5.33px);
			height: 53px;
			border-radius: 53px;
			background: rgba(255, 255, 255, 0.5);
			transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
			box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
			z-index: 0;
		}

		.tab-item {
			flex: 1;
			display: flex;
			flex-direction: column;
			align-items: center;
			justify-content: center;
			gap: 6px;
			cursor: pointer;
			transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
			padding: 8px 0;
			color: rgba(100, 100, 120, 0.6);
			position: relative;
			z-index: 1;

			i {
				font-size: 20px;
				transition: all 0.3s ease;
			}

			.tab-text {
				font-size: 10px;
				font-weight: 400;
				letter-spacing: 0.2px;
				transition: all 0.3s ease;
			}

			&.active {
				color: #666;

				i {
					transform: scale(1.05);
					font-weight: 500;
				}

				.tab-text {
					font-weight: 500;
				}
			}

			&:active {
				transform: scale(0.95);
			}
		}
	}
</style>

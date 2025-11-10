<template>
	<div id="app" class="app-container">
		<div class="page-content" :class="{ 'has-tabbar': showTabbar }">
			<router-view v-slot="{ Component }">
				<transition name="fade" mode="out-in">
					<keep-alive :exclude="['Player']">
						<component :is="Component" />
					</keep-alive>
				</transition>
			</router-view>
		</div>

		<!-- 迷你播放器 - 只在主页显示 -->
		<MiniPlayer v-if="currentMusic && (showTabbar && route.path !== '/player')" />

		<!-- 底部导航栏 - 只在主页面显示，且弹窗未显示时显示 -->
		<CustomTabbar v-if="showTabbar && !showAnySheet" />
	</div>
</template>

<script setup>
	import { computed, onMounted } from "vue";
	import { useRoute } from "vue-router";
	import { useMusicStore } from "@/stores/music";
	import MiniPlayer from "@/components/MiniPlayer.vue";
	import CustomTabbar from "@/components/CustomTabbar.vue";

	const route = useRoute();
	const musicStore = useMusicStore();
	const currentMusic = computed(() => musicStore.currentMusic);

	// 只在主页面显示 Tabbar
	const showTabbar = computed(() => {
		const mainPages = ["/home", "/player", "/mine"];
		return mainPages.includes(route.path);
	});

	// 检查是否有任何弹窗显示
	const showAnySheet = computed(() => {
		return musicStore.showActionSheet || musicStore.showPlaylistSheet;
	});

	onMounted(() => {
		console.log('=== App.vue 已挂载 ===');
		console.log('当前路由:', route.path);
		console.log('显示 Tabbar:', showTabbar.value);
		console.log('当前音乐:', currentMusic.value);
	});
</script>

<style lang="scss">
	* {
		margin: 0;
		padding: 0;
		box-sizing: border-box;
	}

	html, body {
		width: 100%;
		height: 100%;
		overflow: hidden;
	}

	.app-container {
		width: 100%;
		min-height: 100vh;
		height: 100vh;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		position: relative;
		overflow: hidden;
		/* 顶部安全区域适配 */
		padding-top: env(safe-area-inset-top);
		padding-top: constant(safe-area-inset-top); /* iOS 11.0 */
	}

	// 页面内容区域
	.page-content {
		width: 100%;
		height: calc(100vh - env(safe-area-inset-top));
		height: calc(100vh - constant(safe-area-inset-top)); /* iOS 11.0 */
		overflow: hidden;
		position: relative;

		// 当显示 tabbar 时，调整高度
		&.has-tabbar {
			height: calc(100vh - 80px - env(safe-area-inset-top));
			height: calc(100vh - 80px - constant(safe-area-inset-top)); /* iOS 11.0 */
		}
	}

	.fade-enter-active,
	.fade-leave-active {
		transition: opacity 0.3s ease;
	}

	.fade-enter-from,
	.fade-leave-to {
		opacity: 0;
	}
</style>

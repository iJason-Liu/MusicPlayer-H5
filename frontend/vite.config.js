import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import { resolve } from "path";

export default defineConfig(({ mode }) => ({
  // 打包使用的模式 - 生产环境使用相对路径
  base: './',
	plugins: [vue()],
	resolve: {
		alias: {
			"@": resolve(__dirname, "src"),
		},
	},
	build: {
		outDir: "h5",
		sourcemap: false,
		// 生产环境移除 console 和 debugger
		terserOptions: {
			compress: {
				drop_console: false,
				drop_debugger: false,
			},
		},
	},
	server: {
    // host: '0.0.0.0', // ✅ 允许通过局域网 IP 访问
		port: 3000,
		open: false,
		proxy: {
			"/api": {
				target: "https://diary.crayon.vip",
				changeOrigin: true,
				rewrite: (path) => path.replace(/^\/api/, ""),
			},
		},
	},
}));

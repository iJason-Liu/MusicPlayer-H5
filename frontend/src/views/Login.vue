<template>
	<div class="login-page">
		<div class="login-container">
			<div class="logo">
				<i class="fas fa-music"></i>
			</div>
			<h1 class="title">小新的音乐库</h1>
			<p class="subtitle">享受音乐，享受生活</p>

			<div class="form">
				<div class="input-group">
					<i class="fas fa-user"></i>
					<input type="text" placeholder="用户名" v-model="username" @keyup.enter="handleLogin" />
				</div>
				<div class="input-group">
					<i class="fas fa-lock"></i>
					<input type="password" placeholder="密码" v-model="password" @keyup.enter="handleLogin" />
				</div>

				<button class="login-btn" @click="handleLogin" :disabled="loading">
					{{ loading ? "登录中..." : "登录" }}
				</button>

				<div class="dev-mode" v-if="userStore.DEV_MODE">
					<p class="dev-tip">🔧 开发模式：可直接访问页面</p>
				</div>
			</div>
		</div>
	</div>
</template>

<script setup>
	import { ref } from "vue";
	import { useRouter, useRoute } from "vue-router";
	import { login } from "@/api/user";
	import { useUserStore } from "@/stores/user";
	import { showToast } from "vant";

	const router = useRouter();
	const route = useRoute();
	const userStore = useUserStore();

	const username = ref("");
	const password = ref("");
	const loading = ref(false);

	const handleLogin = async () => {
		if (!username.value || !password.value) {
			showToast("请输入用户名和密码");
			return;
		}

		loading.value = true;

		try {
			const res = await userStore.loginUser(username.value, password.value);
			if (res.code === 1) {
				showToast(res.msg || "登录成功");
				localStorage.setItem("token", res.data.token);
				localStorage.setItem("userInfo", JSON.stringify(res.data.user));
				userStore.isLoggedIn = true;
				router.push("/home");
				loading.value = false;
			} else {
				showToast(res.msg || "登录失败");
				loading.value = false;
			}
		} catch (error) {
			console.error("登录失败:", error);
			showToast(error.message || "登录失败");
			loading.value = false;
		}
	};
</script>

<style lang="scss" scoped>
	.login-page {
		min-height: 100vh;
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;

		.login-container {
			width: 100%;
			max-width: 400px;
			text-align: center;

			.logo {
				width: 80px;
				height: 80px;
				margin: 0 auto 20px;
				background: rgba(255, 255, 255, 0.2);
				backdrop-filter: blur(20px);
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;

				i {
					font-size: 36px;
					color: #fff;
				}
			}

			.title {
				font-size: 28px;
				font-weight: 700;
				color: #fff;
				margin-bottom: 10px;
			}

			.subtitle {
				font-size: 14px;
				color: rgba(255, 255, 255, 0.7);
				margin-bottom: 40px;
			}

			.form {
				.input-group {
					display: flex;
					align-items: center;
					background: rgba(255, 255, 255, 0.15);
					backdrop-filter: blur(20px);
					border-radius: 12px;
					padding: 15px 20px;
					margin-bottom: 15px;
					border: 1px solid rgba(255, 255, 255, 0.2);

					i {
						font-size: 18px;
						color: rgba(255, 255, 255, 0.7);
						margin-right: 12px;
					}

					input {
						flex: 1;
						background: transparent;
						border: none;
						outline: none;
						color: #fff;
						font-size: 15px;

						&::placeholder {
							color: rgba(255, 255, 255, 0.5);
						}
					}
				}

				.login-btn,
				.guest-btn {
					width: 100%;
					padding: 15px;
					border: none;
					border-radius: 12px;
					font-size: 16px;
					font-weight: 600;
					cursor: pointer;
					transition: all 0.3s;
					margin-bottom: 12px;
				}

				.login-btn {
					background: #fff;
					color: #667eea;

					&:active {
						transform: scale(0.98);
					}
				}

				.guest-btn {
					background: rgba(255, 255, 255, 0.15);
					backdrop-filter: blur(20px);
					color: #fff;
					border: 1px solid rgba(255, 255, 255, 0.3);

					&:active {
						transform: scale(0.98);
					}
				}

				.dev-mode {
					font-size: 12px;
					color: rgba(255, 255, 255, 0.7);
					margin-top: 10px;
				}

				.dev-tip {
					font-size: 12px;
					color: rgba(255, 255, 255, 0.7);
					margin-top: 10px;
				}
			}
		}
	}
</style>

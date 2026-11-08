<template>
	<view class="profile-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="协议" :autoBack="true" bgColor="#ffffff" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
		</uv-navbar>
		<view class="guide-content">
			<uv-parse :content="agreementContent" :tag-style="tagStyle"
				container-style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;" />
		</view>
	</view>
</template>

<script>
	import api from "../../api";
	import uvParse from "../../uni_modules/uv-parse/components/uv-parse/uv-parse.vue";
	export default {
		data() {
			return {
				agreementContent: "",
				tagStyle: {
					p: "font-size:14px;color:#333;line-height:1.8em;margin-bottom:12px;",
					h1: "font-size:22px;font-weight:700;margin:24px 0 16px;",
					h2: "font-size:20px;font-weight:700;margin:20px 0 14px;",
					h3: "font-size:18px;font-weight:600;margin:18px 0 12px;",
					img: "max-width:100%;border-radius:6px;",
					ul: "padding-left:20px;margin:12px 0;",
					li: "margin-bottom:8px;list-style-type:disc;",
					a: "color:#2979ff;text-decoration:underline;",
				},
			}
		},
		components: {
			uvParse,
		},
		onLoad() {
			this.getUserAgreement();
		},
		methods: {
			// 获取用户协议
			async getUserAgreement() {
				try {
					const result = await api.user.getUserAgreement();
					if (result.code === 200) {
						this.agreementContent = result.data.content;
					}
				} catch (error) {
					console.error('获取用户协议失败:', error);
				}
			},
		}
	}
</script>

<style>
	.agreement-container {
		min-height: 100vh;
		background-color: #f0f6fb;
	}

	.agreement-content {
		padding: 20px;
	}
	.agreement-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 20px;
		border-bottom: 1px solid #f0f0f0;
	}

	.agreement-title {
		font-size: 18px;
		font-weight: 600;
		color: #333;
	}

	.agreement-close {
		width: 32px;
		height: 32px;
		display: flex;
		align-items: center;
		justify-content: center;
		border-radius: 50%;
		background-color: #f5f5f5;
	}

	.agreement-content {
		flex: 1;
		padding: 20px;
		overflow-y: auto;
		line-height: 1.6;
		font-size: 14px;
		color: #333;
	}
</style>
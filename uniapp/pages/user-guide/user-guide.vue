<template>
	<view class="user-guide-page">
		<!-- 导航栏 -->
		<uv-navbar title="使用教程" bgColor="#f8f9fa" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44"
			@leftClick="goBack" />

		<!-- 教程内容（未加载到后端 HTML 时显示静态版） -->
		<view class="guide-content" v-if="!guideHtml">
			<!-- 基础使用 -->
			<view class="guide-section">
				<view class="section-title">
					<text class="title-text">📝 基础使用</text>
				</view>
				<view class="guide-item">
					<text class="step-number">1</text>
					<view class="step-content">
						<text class="step-title">发布投稿</text>
						<text class="step-desc">在首页填写标题、内容，选择标签，上传图片后点击发布</text>
					</view>
				</view>
				<view class="guide-item">
					<text class="step-number">2</text>
					<view class="step-content">
						<text class="step-title">查看投稿</text>
						<text class="step-desc">在"我的投稿"中可以查看自己的投稿状态和历史记录</text>
					</view>
				</view>
				<view class="guide-item">
					<text class="step-number">3</text>
					<view class="step-content">
						<text class="step-title">评论互动</text>
						<text class="step-desc">在评论区可以与其他用户进行互动交流</text>
					</view>
				</view>
			</view>

			<!-- 墙主功能 -->
			<view class="guide-section">
				<view class="section-title">
					<text class="title-text">👑 墙主功能</text>
				</view>
				<view class="guide-item">
					<text class="step-number">1</text>
					<view class="step-content">
						<text class="step-title">申请墙主</text>
						<text class="step-desc">在个人中心点击"申请墙主"，等待审核通过</text>
					</view>
				</view>
				<view class="guide-item">
					<text class="step-number">2</text>
					<view class="step-content">
						<text class="step-title">审核投稿</text>
						<text class="step-desc">在投稿列表中审核用户提交的投稿内容</text>
					</view>
				</view>
				<view class="guide-item">
					<text class="step-number">3</text>
					<view class="step-content">
						<text class="step-title">墙主设置</text>
						<text class="step-desc">可以设置墙的名称、描述、主题色等个性化配置</text>
					</view>
				</view>
				<view class="guide-item">
					<text class="step-number">4</text>
					<view class="step-content">
						<text class="step-title">群聊设置</text>
						<text class="step-desc">可以创建和管理群聊信息，包括群聊头像、二维码等</text>
					</view>
				</view>
			</view>

			<!-- 注意事项 -->
			<view class="guide-section">
				<view class="section-title">
					<text class="title-text">⚠️ 注意事项</text>
				</view>
				<view class="notice-item">
					<text class="notice-text">• 请遵守学校规范，不发布违法违规内容</text>
				</view>
				<view class="notice-item">
					<text class="notice-text">• 投稿内容需要墙主审核后才能公开显示</text>
				</view>
				<view class="notice-item">
					<text class="notice-text">• 匿名评论会使用随机头像和用户名</text>
				</view>
				<view class="notice-item">
					<text class="notice-text">• 如有问题可以通过个人中心联系我们</text>
				</view>
			</view>

			<!-- 常见问题 -->
			<view class="guide-section">
				<view class="section-title">
					<text class="title-text">❓ 常见问题</text>
				</view>
				<view class="faq-item">
					<text class="faq-question">Q: 投稿为什么没有显示？</text>
					<text class="faq-answer">A: 投稿需要墙主审核通过后才会公开显示，请耐心等待。</text>
				</view>
				<view class="faq-item">
					<text class="faq-question">Q: 如何成为墙主？</text>
					<text class="faq-answer">A: 在个人中心点击"申请墙主"，提交申请后等待管理员审核。</text>
				</view>
				<view class="faq-item">
					<text class="faq-question">Q: 可以删除自己的投稿吗？</text>
					<text class="faq-answer">A: 可以在"我的投稿"中删除自己发布的内容。</text>
				</view>
			</view>
		</view>

		<!-- 教程内容（后端 HTML 富文本） -->
		<view v-else class="guide-content">
			<uv-parse :content="guideHtml" :tag-style="tagStyle"
				container-style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'PingFang SC', 'Hiragino Sans GB', 'Microsoft YaHei', sans-serif;" />
		</view>
	</view>
</template>

<script>
	import uvParse from "../../uni_modules/uv-parse/components/uv-parse/uv-parse.vue";
	import api from "../../api";

	export default {
		components: {
			uvParse,
		},
		data() {
			return {
				guideHtml: "",
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
			};
		},

		async onLoad() {
			try {
				uni.showLoading({
					title: "加载中..."
				});
				const res = await api.guide.getUserGuide();
				if (res.code === 200 && res.data) {
					// 假设后端字段为 html 或 content
					this.guideHtml = res.data.html || res.data.content || "";
				}
			} catch (error) {
				console.error("获取使用教程失败", error);
				uni.showToast({
					title: "加载教程失败",
					icon: "none",
				});
			} finally {
				uni.hideLoading();
			}
		},

		methods: {
			// 返回上一页
			goBack() {
				uni.navigateBack();
			},
		},
	};
</script>

<style scoped>
	.user-guide-page {
		min-height: 100vh;
		background-color: #f8f9fa;
	}

	.guide-content {
		padding: 20px;
	}

	.guide-section {
		background-color: white;
		border-radius: 12px;
		padding: 20px;
		margin-bottom: 20px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
	}

	.section-title {
		margin-bottom: 20px;
		padding-bottom: 12px;
		border-bottom: 2px solid #f0f0f0;
	}

	.title-text {
		font-size: 18px;
		font-weight: bold;
		color: #333;
	}

	.guide-item {
		display: flex;
		align-items: flex-start;
		margin-bottom: 16px;
	}

	.guide-item:last-child {
		margin-bottom: 0;
	}

	.step-number {
		width: 24px;
		height: 24px;
		background-color: #2979ff;
		color: white;
		border-radius: 12px;
		display: flex;
		align-items: center;
		justify-content: center;
		font-size: 12px;
		font-weight: bold;
		margin-right: 12px;
		flex-shrink: 0;
	}

	.step-content {
		flex: 1;
	}

	.step-title {
		display: block;
		font-size: 16px;
		font-weight: 500;
		color: #333;
		margin-bottom: 4px;
	}

	.step-desc {
		font-size: 14px;
		color: #666;
		line-height: 1.5;
	}

	.notice-item {
		margin-bottom: 12px;
	}

	.notice-item:last-child {
		margin-bottom: 0;
	}

	.notice-text {
		font-size: 14px;
		color: #666;
		line-height: 1.6;
	}

	.faq-item {
		margin-bottom: 16px;
		padding: 16px;
		background-color: #f8f9fa;
		border-radius: 8px;
	}

	.faq-item:last-child {
		margin-bottom: 0;
	}

	.faq-question {
		display: block;
		font-size: 15px;
		font-weight: 500;
		color: #333;
		margin-bottom: 8px;
	}

	.faq-answer {
		font-size: 14px;
		color: #666;
		line-height: 1.5;
	}
</style>

<!-- 在同文件追加全局深度样式 -->
<style>
	/* 深度作用域，覆盖 uv-parse 默认样式 */
	.user-guide-page>>>._root {
		color: #333;
	}

	.user-guide-page>>>img {
		max-width: 100%;
		display: block;
		margin: 12px 0;
	}

	.user-guide-page>>>table {
		width: 100%;
		border-collapse: collapse;
	}

	.user-guide-page>>>table th,
	.user-guide-page>>>table td {
		border: 1px solid #e5e5e5;
		padding: 8px 12px;
	}
</style>
<template>
	<view class="personal-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="个人中心" :autoBack="true" bgColor="white" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
		</uv-navbar>

		<!-- 顶部通知栏 -->
		<view class="notice-banner" v-if="latestNotifications.content">
			<uv-notice-bar :text="latestNotifications.content" mode="horizontal" :scrollable="true" color="#ffffff"
				bgColor="#ff6b6b" :speed="80" :fontSize="14" icon="volume"></uv-notice-bar>
		</view>

		<!-- 用户信息区 -->
		<view class="user-info">
			<image class="avatar" v-if="userInfo.avatar" :src="userInfo.avatar || '/static/avatar.png'"></image>
			<image class="avatar" v-else @click="()=>showAuthModal = true"
				:src="userInfo.avatar || '/static/avatar.png'"></image>
			<text class="username" v-if="userInfo.nickname">{{ userInfo.nickname || "未登录" }}</text>
			<text class="username" v-else @click="()=>showAuthModal = true">{{ userInfo.nickname || "未登录" }}</text>
		</view>

		<!-- 功能菜单 -->
		<view class="menu-container">
			<!-- 墙主数据统计卡片 -->
			<view class="stats-overview" v-if="isWallOwner">
				<view class="overview-title">数据概览</view>
				<view class="overview-stats">
					<view class="overview-item">
						<text class="overview-value">{{ withdrawPreview.yesterday_post_count || 0 }}</text>
						<text class="overview-label">昨日投稿</text>
					</view>
					<view class="overview-item">
						<text class="overview-value">{{ withdrawPreview.today_post_count || 0 }}</text>
						<text class="overview-label">今日投稿</text>
					</view>
					<view class="overview-item">
						<text class="overview-value">{{ withdrawPreview.post_count_ring || 0 }}%</text>
						<text class="overview-label">环比增长</text>
					</view>
					<view class="overview-item highlight"
						@click="navigateTo('/pages/commission-detail/commission-detail?withdrawable_amount='+withdrawPreview.withdrawable_amount)">
						<text class="overview-value">¥{{ withdrawPreview.withdrawable_amount || 0 }}</text>
						<view class="overview-label"
							style="display: flex;align-items: flex-end;justify-content: center;margin-top: 9px;">
							<text class="">可提现</text>
							<uv-icon name="arrow-right" size="12" color="#b2b2b2"></uv-icon>
						</view>
					</view>
				</view>
			</view>

			<!-- 墙主专属功能  -->
			<view class="menu-section" v-if="isWallOwner">
				<view class="section-title">
					<uv-icon name="star" size="16" color="#2979ff"></uv-icon>
					<text class="section-title-text">墙主专属</text>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/withdraw-history/withdraw-history')">
					<view class="menu-icon">
						<uni-icons type="wallet" size="22" color="#2979ff"></uni-icons>
					</view>
					<view class="menu-content">
						<text class="menu-title">提现管理</text>
						<text class="menu-subtitle">查看提现记录</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/submission-list/submission-list')">
					<view class="menu-icon">
						<uv-icon name="checkbox-mark" size="24" color="#2979ff"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">投稿审核</text>
						<text class="menu-subtitle">管理用户投稿</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/setting/setting?wall_id='+wall_id)">
					<view class="menu-icon">
						<uv-icon name="setting" size="24" color="#2979ff"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">墙主设置</text>
						<text class="menu-subtitle">管理表白墙设置</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateToGroupSettings">
					<view class="menu-icon">
						<uv-icon name="chat" size="24" color="#2979ff"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">群聊设置</text>
						<text class="menu-subtitle">管理群聊配置</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>
			</view>

			<!-- 通用功能 -->
			<view class="menu-section">
				<view class="section-title">
					<uv-icon name="grid" size="16" color="#666"></uv-icon>
					<text class="section-title-text">我的功能</text>
				</view>

				<view class="menu-item" @click="handleUserInfoClick" v-if="!isWallOwner">
					<view class="menu-icon">
						<uni-icons type="compose" size="22" color="#333333"></uni-icons>
					</view>
					<view class="menu-content">
						<text class="menu-title">信息编辑</text>
						<text class="menu-subtitle">修改个人信息</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/my-posts/my-posts')">
					<view class="menu-icon">
						<uv-icon name="file-text" size="24" color="#333333"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">我的投稿</text>
						<text class="menu-subtitle">查看我的所有投稿</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/user-guide/user-guide')">
					<view class="menu-icon">
						<uv-icon name="info-circle" size="24" color="#333333"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">使用教程</text>
						<text class="menu-subtitle">了解如何使用应用</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>

				<view class="menu-item" @click="navigateTo('/pages/user-agreemen/user-agreemen')">
					<view class="menu-icon">
						<uv-icon name="file-text" size="24" color="#333333"></uv-icon>
					</view>
					<view class="menu-content">
						<text class="menu-title">{{ isWallOwner ? '墙主协议' : '用户协议' }}</text>
						<text class="menu-subtitle">查看服务条款</text>
					</view>
					<view class="menu-arrow">
						<uv-icon name="arrow-right" size="18" color="#b2b2b2"></uv-icon>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部操作按钮 -->
		<view class="footer-actions">
			<!-- 联系我们 -->
			<button open-type="contact" class="footer-btn contact-btn">
				<view class="btn-icon">
					<uv-icon name="email" size="24" color="#666"></uv-icon>
				</view>
				<text class="footer-btn-text">联系我们</text>
			</button>

      <button open-type="log_out" class="footer-btn contact-btn"  @click="logOut">
        <view class="btn-icon">
          <uv-icon name="home" size="24" color="#666"></uv-icon>
        </view>
        <text class="footer-btn-text">退出登录</text>
      </button>
			<!-- 申请墙主 -->
			<view class="footer-btn" @click="handleWallOwner" v-if="!isWallOwner">
				<view class="btn-icon">
					<uv-icon name="home" size="24" color="#2979ff"></uv-icon>
				</view>
				<text class="footer-btn-text apply-text">申请墙主</text>
			</view>
		</view>

		<!-- 授权弹窗 -->
		<user-auth-modal v-if="showAuthModal" @confirm="handleAuthConfirm" @cancel="handleAuthCancel"></user-auth-modal>
	</view>
</template>

<script>
	import api from "../../api";
	import userAuthModal from "@/components/user-auth-modal.vue";

	import {
		doLogin,
		checkAuth
	} from "../../api/auth";

	export default {
		components: {
			userAuthModal,
		},
		data() {
			return {
				userInfo: {
					avatar: "",
					nickname: "",
					isWallMaster: "",
				},
				isWallOwner: false,
				latestNotifications: {},
				showAuthModal: false,
				needAuth: false,
				pendingAction: null,
				pendingUrl: null,
				withdrawPreview: {
					yesterday_post_count: 0,
					today_post_count: 0,
					post_count_ring: 0,
					withdrawable_amount: 0,
				},
				wall_id: null,
			};
		},

		onLoad() {
			this.checkAuthStatus();
		},

		onShow() {
			console.log("onShow");
			this.getUserInfo();
			this.getLatestNotifications();
			this.checkAuthStatus();
			this.getwithdrawPreview()
		},

		methods: {
			// 检查授权状态
			checkAuthStatus() {
				const userInfo = uni.getStorageSync("userInfo");
				this.needAuth = !userInfo || !userInfo.avatar;
				console.log("授权状态检查:", this.needAuth ? "未授权" : "已授权");

        const platform = uni.getSystemInfoSync().platform;
        console.log("platform:", platform);
        if (platform === 'android') {
          if (empty(userInfo)) {
            uni.reLaunch({
              url: '/pages/login/index'
            });
          }
        }
			},

			getwithdrawPreview() {
				api.withdraw.preview().then(result => {
					this.withdrawPreview = result.data
				}).catch(error => {
					console.error('获取佣金预览失败：', error)
				})
			},

			// 处理头像或昵称点击
			handleUserInfoClick() {
				if (this.needAuth) {
					this.pendingAction = 'editProfile';
					this.pendingUrl = null;
					this.showAuthModal = true;
				} else {
					this.navigateToProfile();
				}
			},

			// 跳转到个人信息编辑页面
			navigateToProfile() {
				uni.navigateTo({
					url: '/pages/user-profile/user-profile'
				});
			},

			// 处理授权确认
			handleAuthConfirm(userInfo) {
				this.showAuthModal = false;
				this.needAuth = false;
				this.getUserInfo();

				if (this.pendingAction === 'editProfile') {
					this.navigateToProfile();
				} else if (this.pendingUrl) {
					uni.navigateTo({
						url: this.pendingUrl
					});
					this.pendingUrl = null;
				} else if (this.pendingAction) {
					if (this.pendingAction === 'groupSettings') {
						uni.navigateTo({
							url: `/pages/group-settings/group-settings?wallOwnerId=${this.userInfo.userId}`
						});
					} else if (this.pendingAction === 'applyWallOwner') {
						this.showApplyWallOwnerConfirm();
					}
					this.pendingAction = null;
				}
			},

			// 处理授权取消
			handleAuthCancel() {
				this.showAuthModal = false;
			},

			// 获取最新的通知
			async getLatestNotifications() {
				try {
					const result = await api.notification.getLatestNotifications(1, 1);
					console.log(result, "result111111");
					this.latestNotifications = result.data;
					console.log(this.latestNotifications, "this.latestNotifications");
				} catch (error) {
					console.error("加载通知失败:", error);
				}
			},

			// 获取用户信息
			async getUserInfo() {
				const res = await api.user.getUserInfo();
				this.userInfo = res.data;
				this.wall_id = res.data.wall_id
				console.log(this.userInfo);
				uni.setStorageSync('userInfo', this.userInfo);
				this.checkWallOwnerStatus();
			},

			// 检查墙主身份
			checkWallOwnerStatus() {
				this.isWallOwner = this.userInfo.isWallMaster;
				console.log(this.isWallOwner, "this.isWallOwner");
			},

			// 墙主中心/申请墙主点击处理
			handleWallOwner() {
				if (this.needAuth) {
					this.pendingAction = 'applyWallOwner';
					this.pendingUrl = null;
					this.showAuthModal = true;
					return;
				}
				this.showApplyWallOwnerConfirm();
			},

      logOut() {
        uni.removeStorageSync('userInfo');
        uni.reLaunch({
          url: '/pages/login/index'
        });
      },

			// 显示申请墙主确认框
			showApplyWallOwnerConfirm() {
				uni.showModal({
					title: "提示",
					content: "您确定要申请成为墙主吗？",
					success: async function(res) {
						if (!res.confirm) return;

						console.log("申请成为墙主");
						const result = await api.wallowner.applyWallOwner().catch((error) => {
							console.log(error, "error");
							uni.showToast({
								icon: "none",
								title: error.message || "申请失败",
								duration: 2000,
							});
						});

						if (result && result.code === 200) {
							uni.showToast({
								icon: "none",
								title: "申请成功,请等待审核!",
								duration: 2000,
							});
						}
					},
				});
			},

			// 页面导航
			navigateTo(url) {
				if (this.needAuth) {
					this.pendingUrl = url;
					this.pendingAction = null;
					this.showAuthModal = true;
					return;
				}
				uni.navigateTo({
					url: url,
				});
			},

			// 导航到群聊设置
			navigateToGroupSettings() {
				if (this.needAuth) {
					this.pendingAction = 'groupSettings';
					this.pendingUrl = null;
					this.showAuthModal = true;
					return;
				}
				uni.navigateTo({
					url: `/pages/group-settings/group-settings?wallOwnerId=${this.userInfo.userId}`,
				});
			},

			// 处理更多按钮点击
			handleMoreClick() {
				uni.showActionSheet({
					itemList: ["设置", "关于"],
					success: (res) => {
						if (res.tapIndex === 0) {
							uni.showToast({
								title: "设置功能开发中",
								icon: "none",
							});
						} else if (res.tapIndex === 1) {
							uni.showToast({
								title: "关于功能开发中",
								icon: "none",
							});
						}
					},
				});
			},

			// 联系我们
			contactUs() {
				// 打开微信客服
			},
		},
	};
</script>

<style>
	.personal-container {
		min-height: 100vh;
		background-color: #f8fafe;
		display: flex;
		flex-direction: column;
	}

	.notice-banner {
		background-color: #ff6b6b;
		display: flex;
		align-items: center;
		color: #ffffff;
	}

	/* 用户信息区域 */
	.user-section {
		padding: 15px;
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
		margin-bottom: 15px;
	}

	.user-info {
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-bottom: 15px;
	}

	.avatar {
		width: 80px;
		height: 80px;
		border-radius: 50%;
		background-color: #f0f0f0;
		margin-bottom: 10px;
		border: 2px solid #fff;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
	}

	.username {
		font-size: 18px;
		font-weight: bold;
		color: #333;
		margin-bottom: 5px;
	}

	.user-details {
		flex: 1;
	}

	/* .username {
		font-size: 18px;
		font-weight: bold;
		color: white;
		display: block;
		margin-bottom: 8px;
	} */

	.user-meta {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
		gap: 8px;
	}

	.user-role {
		font-size: 11px;
		background-color: rgba(255, 255, 255, 0.2);
		padding: 2px 6px;
		border-radius: 8px;
		color: white;
	}

	.user-role-normal {
		background-color: rgba(255, 255, 255, 0.1);
	}

	.quick-stats {
		display: flex;
		gap: 10px;
		margin-left: auto;
	}

	.quick-stat {
		font-size: 12px;
		background: rgba(255, 255, 255, 0.15);
		padding: 3px 8px;
		border-radius: 10px;
		color: rgba(255, 255, 255, 0.9);
	}

	/* 菜单容器 */
	.menu-container {
		flex: 1;
		margin: 0 15px;
		margin-bottom: 100px;
	}

	/* 数据概览卡片 */
	.stats-overview {
		background-color: #ffffff;
		border-radius: 12px;
		margin-bottom: 15px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
		overflow: hidden;
	}

	.overview-title {
		padding: 15px;
		background-color: #f8f9fa;
		font-size: 14px;
		font-weight: 500;
		color: #333;
		border-bottom: 1px solid #f0f0f0;
	}

	.overview-stats {
		display: flex;
		padding: 0;
	}

	.overview-item {
		flex: 1;
		padding: 15px 10px;
		text-align: center;
		position: relative;
	}

	.overview-item:not(:last-child)::after {
		content: "";
		position: absolute;
		right: 0;
		top: 20%;
		bottom: 20%;
		width: 1px;
		background-color: #f0f0f0;
	}

	.overview-item.highlight {
		background-color: #fff7e6;
	}

	.overview-value {
		display: block;
		font-size: 16px;
		font-weight: bold;
		color: #333;
		margin-bottom: 5px;
	}

	.overview-item.highlight .overview-value {
		color: #ff8c00;
	}

	.overview-label {
		font-size: 12px;
		color: #666;
	}

	.menu-section {
		background-color: #ffffff;
		border-radius: 12px;
		margin-bottom: 15px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
		overflow: hidden;
	}

	.section-title {
		display: flex;
		align-items: center;
		padding: 15px;
		background-color: #f8f9fa;
		border-bottom: 1px solid #f0f0f0;
	}

	.section-title-text {
		font-size: 14px;
		color: #666;
		font-weight: 500;
		margin-left: 8px;
	}

	.menu-item {
		display: flex;
		align-items: center;
		padding: 15px;
		position: relative;
		transition: background-color 0.3s;
	}

	.menu-item:active {
		background-color: #f8f9fa;
	}

	.menu-item:not(:last-child)::after {
		content: "";
		position: absolute;
		left: 60px;
		right: 15px;
		bottom: 0;
		height: 1px;
		background-color: #f5f5f5;
	}

	.menu-icon {
		width: 40px;
		display: flex;
		justify-content: center;
		align-items: center;
		margin-right: 15px;
	}

	.menu-content {
		flex: 1;
	}

	.menu-title {
		font-size: 16px;
		color: #333;
		font-weight: 500;
		display: block;
		margin-bottom: 2px;
	}

	.menu-subtitle {
		font-size: 12px;
		color: #999;
	}

	.menu-arrow {
		width: 20px;
		display: flex;
		justify-content: center;
		align-items: center;
	}

	/* 底部操作区域 */
	.footer-actions {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background: white;
		display: flex;
		justify-content: space-around;
		padding: 15px 20px;
		padding-bottom: calc(15px + env(safe-area-inset-bottom));
		border-top: 1px solid #f0f0f0;
		box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.05);
	}

	.footer-btn {
		display: flex;
		flex-direction: column;
		align-items: center;
		transition: transform 0.2s;
	}

	.footer-btn:active {
		transform: scale(0.95);
	}

	.contact-btn {
		background: none;
		border: none;
		padding: 0;
		margin: 0;
		line-height: normal;
		outline: none;
		box-sizing: border-box;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.contact-btn::after {
		border: none;
	}

	.btn-icon {
		margin-bottom: 5px;
	}

	.footer-btn-text {
		font-size: 12px;
		color: #666;
	}

	.apply-text {
		color: #2979ff;
		font-weight: 500;
	}

	.navbar-right {
		display: flex;
		align-items: center;
		padding-right: 10px;
	}

	.navbar-icon {
		padding: 0 5px;
	}
</style>
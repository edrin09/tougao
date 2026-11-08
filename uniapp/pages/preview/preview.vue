<template>
	<view class="preview-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="投稿预览" :autoBack="true" bgColor="white" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" fixed placeholder>
			<!-- 左侧 -->
			<template v-slot:left>
				<uv-icon name="arrow-left" size="19" color="#333333" @click="handleBack"></uv-icon>
			</template>
			<!-- 右侧 -->
			<template v-slot:right>
				<view class="right-btns">
					<uv-icon name="more-dot-fill" size="20" color="#333333" @click="showMore"></uv-icon>
				</view>
			</template>
		</uv-navbar>

		<!-- 海报预览区域 -->
		<scroll-view class="poster-preview-area" scroll-y>
			<image :src="posterUrl" mode="widthFix" class="poster-image" @click="previewPoster"></image>
		</scroll-view>

		<!-- 背景色选择 -->
		<view class="bg-colors">
			<view v-for="(color, index) in bgColors" :key="index" class="color-item"
				:class="{ 'color-active': selectedBgColor === color }" :style="{ backgroundColor: color }"
				@click="selectBgColor(color)"></view>
		</view>
		<text class="color-tip">点击选择喜欢的颜色作为背景</text>

		<!-- 渠道选择区域 -->
		<view class="channel-section" v-if="channelList.length">

      <view class="channel-header">
				<view class="channel-header-content">
					<text class="channel-title">发布渠道</text>
					<view class="select-all-container">
						<text class="select-all-text">一键全选</text>
						<uv-switch v-model="selectAll" @change="handleSelectAll" size="24"></uv-switch>
					</view>
				</view>
				<view class="color-tip" style=" ;padding: 5px 0;text-align: left;font-size: 14px;margin-bottom: 0;color: red;font-weight: bold">
					单独额外投放(群发免费，不用选，直接点发布即可)
				</view>
			</view>

			<view class="channel-list">
				<view class="channel-item" v-for="(channel, index) in channelList" :key="index">
					<view class="channel-info">
						<view class="channel-name">{{ getChannelDisplayName(channel) }}       <span style="font-size: 16px;color: red;font-weight: bold">（额外单发）</span></view>
						<view class="channel-details">
							<text class="channel-fans">粉丝：{{ channel.channel_fans_numbers }}人</text>
							<view class="channel-account-container">
								<text class="channel-account">账号：{{ channel.channel_account }}</text>
								<view class="copy-btn" @click="copyChannelAccount(channel.channel_account, index)">
									<image style="width: 20px;height: 20px;" src="/static/copy.png" mode=""></image>
								</view>
							</view>
						</view>
					</view>
					<view class="channel-controls">
						<view class="channel-switch">
							<uv-switch v-model="channel.selected" @change="handleChannelSelect(index)" size="20">
							</uv-switch>
						</view>
						<view class="channel-price">
							<text class="price-symbol">￥</text>
							<text class="price-value">{{ channel.channel_fans_costs }}</text>
						</view>
					</view>
				</view>
			</view>
			<view v-if="hasSelectedChannels" class="cost-summary">
				<view class="cost-summary-content">
					<text class="cost-label">预计费用：</text>
					<text class="cost-amount">￥{{ formattedTotalAmount }}</text>
				</view>
			</view>
		</view>

		<!-- 底部发布按钮 -->
		<view class="bottom-area">
			<button class="publish-btn" :disabled="isSubmitting" @click="submitPost">
				{{ isSubmitting ? "发布中..." : "确认发布" }}
			</button>
			<text class="publish-tip">发布前,请点击图片预览投稿,确认无误后发布</text>
		</view>
	</view>
</template>

<script>
	import {
		createPost
	} from "../../api/post";
	import {
		uploadImage
	} from "../../api/upload";
	import api from "../../api";

	export default {
		data() {
			return {
				postData: {
					title: "",
					content: "",
					tag: "",
					images: [],
				},
				currentTime: "",
				selectedBgColor: "#e3f1fc", // 默认背景色
				bgColors: [
					"#e3f1fc", // 淡蓝色
					"#ffeef5", // 淡粉色
					"#e8f6e8", // 淡绿色
					"#fff6ef", // 淡橙色
					"#f0eeff", // 淡紫色
					"#eeeeee", // 淡灰色
				],
				posterUrl: "", // 存储生成的海报路径
				// 墙主信息
				wallOwnerSettings: {},
				// 渠道相关数据
				channelList: [], // 渠道列表
				selectAll: false, // 一键全选状态
				// 防止重复提交
				isSubmitting: false,
				copyingStates: {}, // 记录每个渠道的复制状态
			};
		},

		computed: {
			// 是否有选中的渠道
			hasSelectedChannels() {
				return this.channelList.some(channel => channel.selected);
			},

			// 选中的渠道列表
			selectedChannels() {
				return this.channelList.filter(channel => channel.selected);
			},
			// 计算总费用
			totalAmount() {
				return this.selectedChannels.reduce((total, channel) => {
					return total + (parseFloat(channel.channel_fans_costs) || 0);
				}, 0);
			},

			// 格式化总费用显示
			formattedTotalAmount() {
				return this.totalAmount.toFixed(2);
			}
		},

		async onLoad(options) {
			// 获取当前时间
			this.setCurrentTime();
			let wallOwnerId = '';
			// 从参数中获取投稿数据
			if (options.postData) {
				try {
					this.postData = JSON.parse(decodeURIComponent(options.postData));
					console.log("接收到的投稿数据:", this.postData);
					wallOwnerId = this.postData.wallOwnerId
				} catch (e) {
					console.error("解析投稿数据失败:", e);
				}
			}

			// 从参数中获取海报URL
			if (options.posterUrl) {
				try {
					this.posterUrl = decodeURIComponent(options.posterUrl);
					console.log("接收到的海报URL:", this.posterUrl);
					wallOwnerId = this.posterUrl.wallOwnerId
				} catch (e) {
					console.error("解析海报URL失败:", e);
				}
			}

			// 获取墙主设置和渠道数据
			if (wallOwnerId) {
				try {
					const wallOwnerSettings = await api.wallowner.getWallSettings(wallOwnerId);
					this.wallOwnerSettings = wallOwnerSettings.data.settings;

					// 处理渠道数据
					if (wallOwnerSettings.data.channels) {
						this.initChannelList(wallOwnerSettings.data.channels);
					}
				} catch (error) {
					console.error("获取墙主设置失败:", error);
				}
			}

			// 立即生成海报预览，使用默认背景色
			this.$nextTick(() => {
				this.generatePoster();
			});
		},

		methods: {
			// 初始化渠道列表
			initChannelList(channels) {
				this.channelList = channels.map(channel => ({
					...channel,
					selected: false, // 默认不选中
				}));
				console.log("初始化渠道列表:", this.channelList);
			},

			// 获取渠道显示名称
			getChannelDisplayName(channel) {
        const channelName = channel.channels?.channels_name || '';
        return `${channelName}`;

				// const channelName = channel.channels?.channels_name || '未知渠道';
				// 可以根据需要添加更多的显示逻辑
				// const suffixMap = {
				// 	'微信': '学校墙朋友圈',
				// 	'抖音': '学校群',
				// 	'QQ': '学校墙空间',
				// };
        //
				// const suffix = suffixMap[channelName] || '学校墙';
				// return `${channelName}${suffix}`;
			},

			// 处理渠道选择
			handleChannelSelect(index) {
				// 检查是否所有渠道都被选中
				const allSelected = this.channelList.every(channel => channel.selected);
				const noneSelected = this.channelList.every(channel => !channel.selected);

				if (allSelected) {
					this.selectAll = true;
				} else if (noneSelected) {
					this.selectAll = false;
				} else {
					this.selectAll = false; // 部分选中状态
				}
			},

			// 处理一键全选
			handleSelectAll(value) {
				this.channelList.forEach(channel => {
					channel.selected = value;
				});
			},

			// 返回首页
			handleBack() {
				uni.navigateBack({
					delta: 1,
				});
			},

			// 设置当前时间
			setCurrentTime() {
				const now = new Date();
				const year = now.getFullYear();
				const month = String(now.getMonth() + 1).padStart(2, "0");
				const day = String(now.getDate()).padStart(2, "0");
				const hours = String(now.getHours()).padStart(2, "0");
				const minutes = String(now.getMinutes()).padStart(2, "0");
				const seconds = String(now.getSeconds()).padStart(2, "0");

				this.currentTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
			},

			// 显示更多选项
			showMore() {
				uni.showActionSheet({
					itemList: ["分享", "举报"],
					success: (res) => {
						if (res.tapIndex === 0) {
							// 分享功能
							uni.showToast({
								title: "分享功能开发中",
								icon: "none",
							});
						} else if (res.tapIndex === 1) {
							// 举报功能
							uni.showToast({
								title: "举报功能开发中",
								icon: "none",
							});
						}
					},
				});
			},

			// 提交投稿
			async submitPost() {
				// 如果正在提交，直接返回，防止重复点击
				if (this.isSubmitting) {
					return;
				}

				const userInfo = uni.getStorageSync("userInfo");
				if (!userInfo || !userInfo.openId) {
					uni.showToast({
						title: "请先登录",
						icon: "none",
					});
					return;
				}

				// 标记开始提交，防止重复点击
				this.isSubmitting = true;

				try {
					// 检查是否有选择渠道
					if (this.hasSelectedChannels) {
						// 有选择渠道，需要支付流程
						const totalAmount = this.totalAmount;

						// 显示费用确认弹窗
						const confirmResult = await this.showPaymentConfirm();
						if (!confirmResult) {
							this.isSubmitting = false;
							return;
						}

						if (totalAmount > 0) {
							// 执行支付流程
							await this.processPayment();
						} else {
							// 总费用为0，直接发布
							await this.directPublish();
						}
					} else {
						// 没有选择渠道，直接发布投稿（无需确认弹窗和统一下单）
						await this.directPublish();
					}

				} catch (error) {
					console.error("提交失败:", error);
					uni.showToast({
						title: error.message || "操作失败",
						icon: "none",
					});
					this.isSubmitting = false;
				}
			},

			// 显示支付确认弹窗
			showPaymentConfirm() {
				return new Promise((resolve) => {
					// 只有选择了渠道才需要显示确认弹窗
					const channelNames = this.selectedChannels.map(channel =>
						this.getChannelDisplayName(channel)
					).join('、');

					if (this.totalAmount > 0) {
						// 有费用，显示支付确认
						uni.showModal({
							title: '确认支付 (额外单发)',
							content: `选择渠道：${channelNames}\n\n总费用：¥${this.formattedTotalAmount}\n\n确认支付并发布投稿？`,
							confirmText: '确认支付',
							cancelText: '取消',
							success: (res) => {
								resolve(res.confirm);
							},
							fail: () => {
								resolve(false);
							}
						});
					} else {
						// 无费用，显示免费发布确认
						uni.showModal({
							title: '确认发布',
							content: `选择渠道：${channelNames}\n\n免费发布投稿？`,
							confirmText: '确认发布',
							cancelText: '取消',
							success: (res) => {
								resolve(res.confirm);
							},
							fail: () => {
								resolve(false);
							}
						});
					}
				});
			},

			// 处理支付流程
			async processPayment() {
        const channelNames = this.selectedChannels.map(
            (channel) => this.getChannelDisplayName(channel)
        ).join("、");
				try {
					uni.showLoading({
						title: "正在创建订单..."
					});

					// 1. 调用统一下单API
					const unifyParams = {
						amount: this.totalAmount.toFixed(2) * 100,
						// desc: `投稿发布费用-${this.selectedChannels.length}个渠道`,
            desc: `投稿发布费用-${channelNames}渠道`,
						out_trade_no: 'post_' + Math.random().toString(36).substr(2, 9).toUpperCase(),
					};

					const unifyResult = await api.withdraw.saveUnify(unifyParams);

					if (unifyResult.code !== 200) {
						throw new Error(unifyResult.message || "创建订单失败");
					}

					// 2. 获取支付jssdk配置
					const jssdkResult = await api.withdraw.jssdkConfig({
						prepay_id: unifyResult.data.res.prepay_id
					});

					if (jssdkResult.code !== 200) {
						throw new Error(jssdkResult.message || "获取支付配置失败");
					}

					uni.hideLoading();

					// 3. 调起微信支付
					await this.invokeWeChatPay(jssdkResult.data.jssdk, unifyParams.out_trade_no);

				} catch (error) {
					uni.hideLoading();
					throw error;
				}
			},

			// 调起微信支付
			async invokeWeChatPay(jssdkConfig, outTradeNo) {
				return new Promise((resolve, reject) => {
					uni.showLoading({
						title: "正在调起支付..."
					});

					// 使用微信支付
					uni.requestPayment({
						provider: 'wxpay',
						timeStamp: jssdkConfig.timeStamp,
						nonceStr: jssdkConfig.nonceStr,
						package: jssdkConfig.package,
						signType: jssdkConfig.signType || 'MD5',
						paySign: jssdkConfig.paySign,
						success: async (res) => {
							uni.hideLoading();
							console.log('支付成功:', res);

							// 支付成功后执行发布逻辑
							try {
								await this.handlePaymentSuccess(outTradeNo);
								resolve(res);
							} catch (error) {
								console.error('发布失败:', error);
								// 支付成功但发布失败的处理
								await this.handlePublishAfterPaymentFailure(outTradeNo, error);
								reject(error);
							}
						},
						fail: (err) => {
							uni.hideLoading();
							console.error('支付失败:', err);

							if (err.errMsg.includes('cancel')) {
								uni.showToast({
									title: '支付已取消',
									icon: 'none'
								});
							} else {
								uni.showToast({
									title: '支付失败，请重试',
									icon: 'none'
								});
							}

							this.isSubmitting = false;
							reject(new Error('支付失败'));
						}
					});
				});
			},

			// 处理支付成功
			async handlePaymentSuccess(outTradeNo) {
				try {
					uni.showLoading({
						title: "正在发布投稿..."
					});

					// 执行发布逻辑
					await this.executePublish(outTradeNo);

					uni.hideLoading();

					// 显示成功提示并跳转
					uni.showToast({
						title: '支付成功，投稿已发布',
						icon: 'success',
						duration: 2000
					});

					// 延时跳转，确保用户看到成功提示
					setTimeout(() => {
						uni.navigateTo({
							url: `/pages/my-posts/my-posts`,
						});
					}, 2000);

				} catch (error) {
					uni.hideLoading();
					throw error;
				} finally {
					this.isSubmitting = false;
				}
			},

			// 支付成功但发布失败的处理
			async handlePublishAfterPaymentFailure(outTradeNo, error) {
				console.error("支付成功但发布失败:", error);

				uni.showModal({
					title: '提示',
					content: '支付成功，但投稿发布遇到问题。我们会尽快处理，请联系客服或稍后重试。',
					showCancel: false,
					confirmText: '知道了',
					success: () => {
						// 可以跳转到订单页面或联系客服页面
						uni.navigateTo({
							url: `/pages/my-posts/my-posts`, // 仍然跳转到我的投稿，用户可以查看状态
						});
					}
				});

				this.isSubmitting = false;
			},

			// 免费直接发布
			async directPublish() {
				try {
					uni.showLoading({
						title: "正在发布投稿..."
					});

					await this.executePublish();

					uni.hideLoading();

					// 显示成功提示
					uni.showToast({
						title: '发布成功',
						icon: 'success',
						duration: 2000
					});

					// 延时跳转
					setTimeout(() => {
						uni.navigateTo({
							url: `/pages/my-posts/my-posts`,
						});
					}, 2000);

				} catch (error) {
					uni.hideLoading();
					console.error("发布失败:", error);
					uni.showToast({
						title: error.message || "发布失败",
						icon: "none",
					});
				} finally {
					this.isSubmitting = false;
				}
			},

			// 执行发布逻辑（原submitPost的核心逻辑）
			async executePublish(outTradeNo = null) {
				// 如果没有传入订单号，生成一个随机订单号
				if (!outTradeNo) {
					outTradeNo = 'post_' + Math.random().toString(36).substr(2, 9).toUpperCase();
				}

				// 上传海报
				let posterImageUrl = "";
				if (this.posterUrl) {
					try {
						console.log("开始上传海报:", this.posterUrl);
						const uploadResult = await uploadImage(this.posterUrl);

						if (uploadResult.code === 200) {
							posterImageUrl = uploadResult.data.url;
							console.log("海报上传成功:", posterImageUrl);
						} else {
							console.warn("海报上传失败:", uploadResult.message);
						}
					} catch (uploadError) {
						console.error("海报上传出错:", uploadError);
					}
				}

				// 提交帖子数据
				const userInfo = uni.getStorageSync("userInfo");
				const submitData = {
					title: this.postData.title || "",
					content: this.postData.content || "",
					contact: this.postData.contact || "",
					openid: userInfo.openId,
					wallOwnerId: this.wallOwnerSettings.wall_owner_id,
					images: this.postData.images,
					posterUrl: posterImageUrl || this.posterUrl,
					tag: this.postData.tag || "",
					isAnonymous: this.postData.isAnonymous || false,
					userAvatar: userInfo.avatar || "",
					selectedChannels: this.selectedChannels.map(channel => ({
						channels_id: channel.channels_id,
						channel_account: channel.channel_account,
						channel_fans_costs: channel.channel_fans_costs
					})),
					outTradeNo: outTradeNo, // 关联订单号（无渠道时也会有）
					totalAmount: this.totalAmount.toFixed(2), // 支付金额
					channels_id: this.selectedChannels.map(channel => channel.channels_id).join(','),
					is_single: this.hasSelectedChannels ? 1 : 0
				};

				console.log("提交的数据:", submitData);

				// 1. 先执行createPost
				const result = await createPost(submitData);

				if (result.code !== 200) {
					throw new Error(result.message || "发布失败");
				}

				// 2. 发布成功后，获取并订阅消息模板
				try {
					const templateResult = await api.wallowner.getReviewResultTemplate();
					console.log("模板id:", templateResult);

					if (templateResult?.data?.template_id) {
						// 订阅消息（不影响主流程）
						await this.requestSubscribeMessage(templateResult.data.template_id);
					}
				} catch (err) {
					console.error("订阅消息流程失败:", err);
					// 订阅失败不影响发布成功的状态
				}

				return result;
			},

			// 请求订阅消息
			async requestSubscribeMessage(templateId) {
				return new Promise((resolve) => {
					uni.requestSubscribeMessage({
						tmplIds: [templateId],
						success: (res) => {
							console.log("订阅模板消息成功:", res);

							// 检查订阅结果
							if (res[templateId] === 'accept') {
								console.log("用户同意订阅消息");
							} else if (res[templateId] === 'reject') {
								console.log("用户拒绝订阅消息");
							} else if (res[templateId] === 'ban') {
								console.log("用户永久拒绝订阅消息");
							}

							resolve(res);
						},
						fail: (err) => {
							console.error("订阅模板消息失败:", err);
							// 订阅失败不影响主流程
							resolve(null);
						}
					});
				});
			},

			// 复制渠道账号
			async copyChannelAccount(account, channelIndex) {
				if (!account) {
					uni.showToast({
						title: '账号为空，无法复制',
						icon: 'none',
						duration: 1500
					});
					return;
				}
				try {
					// 设置复制状态，防止重复点击
					this.$set(this.copyingStates, channelIndex, true);

					// 使用uni-app的复制到剪贴板API
					await uni.setClipboardData({
						data: account,
						success: () => {
							// 显示复制成功提示
							uni.showToast({
								// title: `账号 ${account} 已复制`,
								title: `账号已复制`,
								icon: 'success',
								duration: 2000
							});

							// 可选：震动反馈
							// if (uni.vibrateShort) {
							// 	uni.vibrateShort({
							// 		type: 'light'
							// 	});
							// }
						},
						fail: (error) => {
							console.error('复制失败:', error);
							uni.showToast({
								title: '复制失败，请手动复制',
								icon: 'none',
								duration: 2000
							});
						}
					});

					// 延时恢复按钮状态
					setTimeout(() => {
						this.$set(this.copyingStates, channelIndex, false);
					}, 1000);

				} catch (error) {
					console.error('复制操作出错:', error);

					// 恢复按钮状态
					this.$set(this.copyingStates, channelIndex, false);

					// 显示错误提示
					uni.showToast({
						title: '复制功能暂不可用',
						icon: 'none',
						duration: 1500
					});
				}
			},

			// 批量复制所有选中渠道账号（额外功能）
			copySelectedAccounts() {
				const selectedAccounts = this.selectedChannels
					.map(channel => channel.channel_account)
					.filter(account => account) // 过滤掉空账号
					.join('\n'); // 用换行符分隔

				if (!selectedAccounts) {
					uni.showToast({
						title: '没有可复制的账号',
						icon: 'none'
					});
					return;
				}

				uni.setClipboardData({
					data: selectedAccounts,
					success: () => {
						uni.showToast({
							title: `已复制 ${this.selectedChannels.length} 个账号`,
							icon: 'success'
						});
					},
					fail: () => {
						uni.showToast({
							title: '批量复制失败',
							icon: 'none'
						});
					}
				});
			},

			// 一键复制渠道信息摘要（额外功能）
			copyChannelSummary(channel) {
				const summary =
					`渠道：${this.getChannelDisplayName(channel)}\n账号：${channel.channel_account}\n粉丝：${channel.channel_fans_numbers}人\n费用：¥${channel.channel_fans_costs}`;

				uni.setClipboardData({
					data: summary,
					success: () => {
						uni.showToast({
							title: '渠道信息已复制',
							icon: 'success'
						});
					}
				});
			},

			// 统一下单
			createOrder() {

			},

			// 生成海报
			async generatePoster() {
				try {
					uni.showLoading({
						title: "正在生成预览..."
					});

					// 获取用户信息
					const userInfo = uni.getStorageSync("userInfo") || {};

					// 构建海报数据
					const posterData = {
						title: this.postData.title,
						content: this.postData.content,
						tag: this.postData.tag,
						contact: this.postData.contact || "",
						images: this.postData.images || [], // 传递实际图片数据
						wallOwnerId: this.wallOwnerSettings.wall_owner_id,
						backgroundColor: this.selectedBgColor,
						// 墙主回复相关
						submission_reply: this.wallOwnerSettings.submission_reply,
						owner_avatar: this.wallOwnerSettings.owner_avatar,
						// 自定义背景图片
						custom_bg_image: this.wallOwnerSettings.custom_bg_image,
						// 匿名发布选项
						isAnonymous: this.postData.isAnonymous || false,
						// 用户头像
						userAvatar: userInfo.avatar || "",
					};

					console.log("开始生成海报，数据:", posterData);

					// 使用后端API生成海报
					const result = await api.post.generatePoster(posterData);

					if (result.code !== 200) {
						throw new Error(result.message || "生成海报失败");
					}

					// 保存海报路径
					this.posterUrl = result.data.url;

					console.log("海报生成成功:", this.posterUrl);
				} catch (error) {
					console.error("生成海报失败:", error);
					uni.showToast({
						title: error.message || "生成海报失败",
						icon: "none",
					});
				} finally {
					// 无论成功与否，都重置提交状态
					this.isSubmitting = false;
					uni.hideLoading();
				}
			},

			// 选择背景色
			async selectBgColor(color) {
				if (this.wallOwnerSettings.custom_bg_image) {
					uni.showToast({
						title: "墙主已设置自定义背景图,无法选择背景色",
						icon: "none",
					});
					return;
				}

				if (this.selectedBgColor === color) {
					return; // 如果选择的是当前颜色，不需要重新生成
				}

				this.selectedBgColor = color;
				// 重新生成海报
				await this.generatePoster();
			},

			// 预览海报
			previewPoster() {
				if (this.posterUrl) {
					uni.previewImage({
						urls: [this.posterUrl],
						current: this.posterUrl
					});
				}
			},
		},
	};
</script>

<style>
	.preview-container {
		min-height: 100vh;
		background-color: #f0f6fb;
		display: flex;
		flex-direction: column;
	}

	/* 海报预览区域 */
	.poster-preview-area {
		border-radius: 12px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
		height: 200px;
		width: 90%;
		margin: 0 auto;
	}

	.poster-image {
		width: 100%;
		border-radius: 8px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}

	/* 背景色选择 */
	.bg-colors {
		display: flex;
		justify-content: center;
		margin-bottom: 10px;
		padding: 10px 20px;
	}

	.color-item {
		width: 40px;
		height: 40px;
		border-radius: 5px;
		margin: 0 5px;
		box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
	}

	.color-active {
		border: 2px solid #2979ff;
	}

	.color-tip {
		font-size: 14px;
		color: #999;
		margin-bottom: 20px;
		padding: 0 20px;
		text-align: center;
	}

	/* 渠道选择区域 */
	.channel-section {
		margin: 20px 15px;
		background-color: #ffffff;
		border-radius: 12px;
		padding: 20px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
	}

	.channel-header {
		margin-bottom: 20px;
		padding-bottom: 15px;
		border-bottom: 1px solid #f0f0f0;
	}
	.channel-header-content{
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.channel-title {
		font-size: 18px;
		font-weight: 600;
		color: #333333;
	}

	.select-all-container {
		display: flex;
		align-items: center;
	}

	.select-all-text {
		font-size: 14px;
		color: #666666;
		margin-right: 10px;
	}

	.channel-list {
		/* 渠道列表容器 */
	}

	.channel-item {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 0;
		border-bottom: 1px solid #f5f5f5;
	}

	.channel-item:last-child {
		border-bottom: none;
	}

	.channel-info {
		flex: 1;
	}

	.channel-name {
		font-size: 16px;
		color: #333333;
		margin-bottom: 5px;
		font-weight: 500;
	}

	.channel-details {
		display: flex;
		align-items: center;
	}

	.channel-fans {
		font-size: 14px;
		color: #666666;
		margin-right: 15px;
	}

	.channel-account {
		font-size: 14px;
		color: #999999;
	}

	.channel-controls {
		display: flex;
		align-items: center;
	}

	.channel-switch {
		margin-right: 20px;
	}

	.channel-price {
		display: flex;
		align-items: baseline;
		min-width: 60px;
		justify-content: flex-end;
	}

	.price-symbol {
		font-size: 14px;
		color: #ff6b35;
		margin-right: 2px;
	}

	.price-value {
		font-size: 18px;
		font-weight: 600;
		color: #ff6b35;
	}

	/* 底部发布区域 */
	.bottom-area {
		padding: 20px;
		display: flex;
		flex-direction: column;
		align-items: center;
		margin-top: auto;
	}

	.publish-btn {
		width: 90%;
		height: 45px;
		line-height: 45px;
		background-color: #2979ff;
		color: #fff;
		border-radius: 22px;
		font-size: 16px;
		font-weight: bold;
		margin-bottom: 10px;
		border: none;
	}

	/* 禁用状态样式 */
	.publish-btn[disabled] {
		opacity: 0.6;
		background-color: #9bbcff;
	}

	.publish-tip {
		font-size: 12px;
		color: #999;
		text-align: center;
	}

	.right-btns {
		display: flex;
		align-items: center;
		padding-right: 10px;
	}

	/* 渠道账号容器样式 */
	.channel-account-container {
		display: flex;
		align-items: center;
		background-color: #f8f9fa;
		border-radius: 4px;
		padding: 2px 6px;
		margin-left: 8px;
	}

	.channel-account {
		font-size: 14px;
		color: #999999;
		margin-right: 6px;
		flex-shrink: 0;
	}

	/* 复制按钮样式 */
	.copy-btn {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 2px;
		border-radius: 3px;
		background-color: rgba(41, 121, 255, 0.1);
		transition: all 0.2s ease;
		cursor: pointer;
	}

	.copy-btn:hover {
		background-color: rgba(41, 121, 255, 0.2);
	}

	.copy-btn:active {
		transform: scale(0.95);
		background-color: rgba(41, 121, 255, 0.3);
	}

	/* 复制中状态样式 */
	.copy-btn.copying {
		opacity: 0.6;
		transform: scale(0.9);
	}

	/* 优化渠道详情布局 */
	.channel-details {
		display: flex;
		align-items: center;
		flex-wrap: wrap;
	}

	.channel-fans {
		font-size: 14px;
		color: #666666;
		margin-right: 8px;
		white-space: nowrap;
	}

	/* 响应式调整 */
	@media screen and (max-width: 480px) {
		.channel-details {
			flex-direction: column;
			align-items: flex-start;
		}

		.channel-account-container {
			margin-left: 0;
			margin-top: 4px;
		}

		.channel-fans {
			margin-right: 0;
			margin-bottom: 4px;
		}
	}

	/* 高亮动画效果 */
	@keyframes copy-success {
		0% {
			background-color: rgba(25, 190, 107, 0.2);
		}

		100% {
			background-color: rgba(41, 121, 255, 0.1);
		}
	}

	.copy-btn.success {
		animation: copy-success 0.6s ease-out;
	}

	.cost-summary {
		margin-top: 15px;
		padding-top: 15px;
		border-top: 1px solid #f0f0f0;
	}

	.cost-summary-content {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.cost-label {
		font-size: 16px;
		color: #333333;
		font-weight: 500;
	}

	.cost-amount {
		font-size: 20px;
		color: #ff6b35;
		font-weight: 600;
	}
</style>
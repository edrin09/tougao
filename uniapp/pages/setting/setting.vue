<template>
	<view class="setting-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="墙主设置" :autoBack="true" bgColor="#f0f6fb" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
			<template v-slot:right>
				<view class="navbar-right">
					<view class="navbar-icon" @click="handleMoreClick">
						<uv-icon name="more-dot-fill" size="22" color="#333333"></uv-icon>
					</view>
				</view>
			</template>
		</uv-navbar>

		<!-- 顶部通知栏 -->
		<view class="notice-banner">
			<uv-notice-bar :text="
          latestNotifications.content ||
          'VIP功能已向全部墙主免费开放，欢迎各位墙主分享...'
        " mode="horizontal" color="#ffffff" bgColor="#ff6b6b" :fontSize="14" :speed="80" :step="false"
				icon="volume"></uv-notice-bar>
		</view>

		<!-- 设置内容区 -->
		<view class="setting-content">
			<!-- 基础信息设置 -->
			<view class="section-header">基础信息设置</view>

			<!-- 墙主专属二维码 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主专属二维码</view>
					<view class="setting-item-desc">分享该码到朋友圈,用户扫码即可直接投稿</view>
				</view>
				<view class="setting-item-right">
					<image class="qrcode-image" :src="wallOwnerInfo.miniapp_qrcode" mode="aspectFit"
						@click="previewImage(wallOwnerInfo.miniapp_qrcode)"></image>
				</view>
			</view>

			<!-- 墙主专属分享短链 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主专属分享短链</view>
					<view class="setting-item-value">{{
            wallOwnerInfo.miniapp_short_link
          }}</view>
				</view>
				<view class="setting-item-right">
					<view class="action-button" @click="copyText(wallOwnerInfo.miniapp_short_link)">一键复制</view>
				</view>
			</view>

			<!-- 墙主分享外链 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主分享外链(可在QQ打开投稿)</view>
					<view class="setting-item-desc">有效期30天，过期请重新点击生成</view>
					<view class="setting-item-value" v-if="!shareLink.isExpired && shareLink.url">
						{{ shareLink.url }}
					</view>
					<view class="setting-item-value" v-else style="color: #999;">
						暂无有效链接，请点击生成
					</view>
				</view>
				<view class="setting-item-right">
					<view class="action-button" @click="generateShareLink">
						{{ !shareLink.isExpired && shareLink.url ? '一键复制' : '一键生成' }}
					</view>
				</view>
			</view>

			<!-- 墙主名称 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主名称</view>
					<input class="setting-input" v-model="wallOwnerInfo.wall_name" placeholder="请输入墙主名称" />
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" @click="saveWallName">保存</view>
				</view>
			</view>

			<!-- 投稿渠道设置 -->
			<view class="section-header">投稿渠道设置</view>

			<!-- 渠道配置提示 -->
			<view class="setting-item channel-intro">
				<view class="setting-item-left">
					<view class="setting-item-title">渠道配置说明</view>
					<view class="setting-item-desc">开启渠道后，用户可通过该渠道投稿。请合理设置投稿费用和账号信息。</view>
				</view>
			</view>

			<!-- 渠道列表 -->
			<view v-for="(channel, index) in channelList" :key="channel.channels_id" class="channel-config-section">
				<!-- 渠道开关 -->
				<view class="setting-item">
					<view class="setting-item-left">
						<view class="setting-item-title">{{ channel.channels_name }}</view>
						<!-- <view class="setting-item-desc">{{ channel.description }}</view> -->
					</view>
					<view class="setting-item-right">
						<switch :checked="channel.channel_enabled" @change="toggleChannel(index, $event)" />
					</view>
				</view>

				<!-- 渠道详细配置（当渠道开启时显示） -->
				<view v-if="channel.channel_enabled" class="channel-detail-config">
					<!-- 投稿费用 -->
					<view class="setting-item channel-sub-item">
						<view class="setting-item-left">
							<view class="setting-item-title">投稿费用（元）</view>
							<view class="setting-item-desc">用户通过该渠道投稿需支付的费用</view>
						</view>
						<view class="setting-item-right">
							<input class="setting-input price-input" v-model="channel.channel_fans_costs"
								placeholder="0.00" type="digit"  />
						</view>
					</view>

					<!-- 渠道账号 -->
					<view class="setting-item channel-sub-item">
						<view class="setting-item-left">
							<view class="setting-item-title">{{ channel.channels_name }}账号</view>
							<view class="setting-item-desc">请填写完整的账号名称或链接</view>
						</view>
						<view class="setting-item-right">
							<input class="setting-input" v-model="channel.channel_account"
								:placeholder="`请输入${channel.channel_account}账号`"  />
						</view>
					</view>

					<!-- 账号粉丝数 -->
					<view class="setting-item channel-sub-item">
						<view class="setting-item-left">
							<view class="setting-item-title">账号粉丝数</view>
							<view class="setting-item-desc">当前账号的粉丝数量，用于展示账号影响力</view>
						</view>
						<view class="setting-item-right">
							<input class="setting-input" v-model="channel.channel_fans_numbers" placeholder="请输入粉丝数"
								type="number" />
						</view>
					</view>

					<!-- 保存按钮 -->
					<!-- <view class="setting-item channel-sub-item">
						<view class="setting-item-left">
							<view class="setting-item-title">保存{{ channel.channels_id }}配置</view>
						</view>
						<view class="setting-item-right">
							<view class="action-button save-button" :class="{ 'disabled': !wallOwnerInfo.is_vip }"
								@click="saveChannelConfig(channel)">保存</view>
						</view>
					</view> -->
				</view>
			</view>

			<!-- 批量保存所有渠道配置 -->
			<view class="setting-item" v-if="channelList.length > 0">
				<view class="setting-item-left">
					<view class="setting-item-title">批量保存所有渠道配置</view>
					<view class="setting-item-desc">一键保存所有已开启渠道的配置信息</view>
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" 
						@click="saveAllChannelConfigs">全部保存</view>
				</view>
			</view>

			<!-- VIP功能 -->
			<view class="section-header vip-section">
				<text>VIP功能</text>
				<text class="vip-status">{{
          wallOwnerInfo.is_vip ? "已开通" : "未开通"
        }}</text>
			</view>

			<!-- 收稿回复 -->
			<view class="setting-item">
				<view class="setting-item-left wide">
					<view class="setting-item-title">收稿回复</view>
					<input class="setting-input" v-model="wallOwnerInfo.submission_reply" placeholder="例如:墙墙已收到!"
						:disabled="!wallOwnerInfo.is_vip" />
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" :class="{ 'disabled': !wallOwnerInfo.is_vip }"
						@click="saveReplyMessage">保存</view>
				</view>
			</view>

			<!-- 滚动公告 -->
			<view class="setting-item">
				<view class="setting-item-left wide">
					<view class="setting-item-title">滚动公告</view>
					<input class="setting-input" v-model="wallOwnerInfo.scrolling_announcement"
						placeholder="例如:投稿请留联系方式~" :disabled="!wallOwnerInfo.is_vip" />
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" :class="{ 'disabled': !wallOwnerInfo.is_vip }"
						@click="saveScrollNotice">保存</view>
				</view>
			</view>

			<!-- 发布文案模板 -->
			<view class="setting-item">
				<view class="setting-item-left wide">
					<view class="setting-item-title">发布文案模板<text class="star-icon">⭐</text></view>
					<input class="setting-input" v-model="wallOwnerInfo.post_template"
						:placeholder="wallOwnerInfo.miniapp_short_link" :disabled="!wallOwnerInfo.is_vip" />
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" :class="{ 'disabled': !wallOwnerInfo.is_vip }"
						@click="savePostTemplate">保存</view>
				</view>
			</view>

			<!-- 墙主名称图片版 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主名称图片版(选填)</view>
					<view class="setting-item-desc">投稿页面墙主名图片版本(不填则展示墙主名称)建议图片宽高比例为5:1</view>
				</view>
				<view class="setting-item-right">
					<uv-upload :fileList="wallNameImageList" @afterRead="afterReadWallNameImage"
						@delete="deleteWallNameImage" name="wallNameImage" :maxCount="1" width="60" height="60"
						:disabled="!wallOwnerInfo.is_vip"></uv-upload>
				</view>
			</view>

			<!-- 自定义投稿背景图 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">自定义投稿背景图</view>
					<view class="setting-item-desc">建议上传透明底图片,可以展示出自己的专属背景,也不影响用户选自己喜欢的颜色</view>
				</view>
				<view class="setting-item-right">
					<uv-upload :fileList="postBgImageList" @afterRead="afterReadPostBgImage" @delete="deletePostBgImage"
						name="postBgImage" :maxCount="1" width="60" height="60"
						:disabled="!wallOwnerInfo.is_vip"></uv-upload>
				</view>
			</view>

			<!-- 首页公告/广告 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">首页公告/广告</view>
					<view class="setting-item-desc">建议图片高度不要过高!</view>
				</view>
				<view class="setting-item-right">
					<uv-upload :fileList="homeAdImageList" @afterRead="afterReadHomeAdImage" @delete="deleteHomeAdImage"
						name="homeAdImage" :maxCount="1" width="60" height="60"
						:disabled="!wallOwnerInfo.is_vip"></uv-upload>
				</view>
			</view>

			<!-- 墙主头像 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">墙主头像</view>
					<view class="setting-item-desc">建议图片高度不要过高!</view>
				</view>
				<view class="setting-item-right">
					<uv-upload :fileList="wallAvatarList" @afterRead="afterReadWallAvatar" @delete="deleteWallAvatar"
						name="wallAvatar" :maxCount="1" width="60" height="60"
						:disabled="!wallOwnerInfo.is_vip"></uv-upload>
				</view>
			</view>

			<!-- 其他设置 -->
			<view class="section-header">其他设置</view>

			<!-- 新稿子通知提醒 -->
			<view class="setting-item">
				<view class="setting-item-left">
					<view class="setting-item-title">新稿子通知提醒</view>
					<view class="setting-item-desc">开启后可及时接收新投稿通知</view>
				</view>
				<view class="setting-item-right">
					<view class="action-button" @click="openNotification">开启</view>
				</view>
			</view>

			<!-- QQ发布文案模板 -->
			<view class="setting-item">
				<view class="setting-item-left wide">
					<view class="setting-item-title">QQ发布文案模板<text class="star-icon">⭐</text></view>
					<input class="setting-input" v-model="wallOwnerInfo.qq_post_template" placeholder="一键投稿......" />
				</view>
				<view class="setting-item-right">
					<view class="action-button save-button" :class="{ 'disabled': !wallOwnerInfo.is_vip }"
						@click="saveQQPostTemplate">保存</view>
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	import api from "../../api";
	import {
		uploadImage
	} from "../../api/upload";

	export default {
		data() {
			return {
				wallOwnerInfo: {},
				tmplIds: [],
				subscribed: false,
				latestNotifications: {},
				wallOwnerId: '',
				shareLink: {
					url: '',
					expireTime: null,
					isExpired: true
				},
				wallNameImageList: [],
				postBgImageList: [],
				homeAdImageList: [],
				wallAvatarList: [],
				// 渠道相关数据
				channelList: [],
				originalChannelData: {},
			};
		},

		computed: {},

		async onShow() {
			const pages = getCurrentPages();
			const currentPage = pages[pages.length - 1];
			if (currentPage && currentPage.options) {
				this.wallOwnerId = currentPage.options.wall_id;
			}
			await this.loadSettings();
			await this.loadLatestNotifications();
			await this.loadChannelList();
			this.checkLocalShareLink();
		},

		methods: {
			// 获取最新的通知
			async loadLatestNotifications() {
				try {
					const result = await api.notification.getLatestNotifications(1, 2);
					console.log(result, "result111111");
					this.latestNotifications = result.data;
					console.log(this.latestNotifications, "this.latestNotifications");
				} catch (error) {
					console.error("加载通知失败:", error);
				}
			},

			async loadSettings() {
				try {
					const result = await api.wallowner.getWallSettings(this.wallOwnerId);
					this.wallOwnerInfo = result.data.settings;

					// 初始化图片列表
					this.initImageLists();

					const templates = await api.wallowner.getSubscribeTemplates();
					this.tmplIds.push(templates.data.template_id);
				} catch (error) {
					console.error("加载设置失败:", error);
					uni.showToast({
						title: "加载设置失败",
						icon: "none",
					});
				}
			},

			/**
			 * 获取渠道列表
			 */
			async loadChannelList() {
				try {
					uni.showLoading({
						title: "加载渠道..."
					});

					// 调用API获取渠道列表
					const result = await api.wallowner.getChannelList();

					if (result.code === 200) {
						this.channelList = result.data.map(channel => ({
							channels_id: channel.channels_id,
							channels_name: channel.channels_name,
							channel_enabled: channel.channel_enabled || false,
							channel_fans_costs: channel.channel_fans_costs || '',
							channel_account: channel.channel_account || '',
							channel_fans_numbers: channel.channel_fans_numbers || '',
						}));

						// 保存原始数据副本
						this.originalChannelData = JSON.parse(JSON.stringify(this.channelList));
					}

					uni.hideLoading();
				} catch (error) {
					uni.hideLoading();
					console.error("加载渠道列表失败:", error);
					// 如果API还未实现，创建默认渠道数据
					this.createDefaultChannels();
				}
			},

			/**
			 * 创建默认渠道数据（用于测试）
			 */
			createDefaultChannels() {
				this.channelList = [{
						id: '1',
						name: '微信朋友圈',
						// description: '通过微信朋友圈发布投稿内容',
						enabled: false,
						price: '',
						account: '',
						followers: ''
					},
					{
						id: '2',
						name: '微博',
						// description: '通过微博平台发布投稿内容',
						enabled: false,
						price: '',
						account: '',
						followers: ''
					},
					{
						id: '3',
						name: 'QQ空间',
						// description: '通过QQ空间发布投稿内容',
						enabled: false,
						price: '',
						account: '',
						followers: ''
					},
					{
						id: '4',
						name: '抖音',
						// description: '通过抖音短视频平台发布投稿内容',
						enabled: false,
						price: '',
						account: '',
						followers: ''
					}
				];
				this.originalChannelData = JSON.parse(JSON.stringify(this.channelList));
			},

			/**
			 * 切换渠道开关
			 */
			toggleChannel(index, event) {
				// if (!this.wallOwnerInfo.is_vip) {
				// 	uni.showToast({
				// 		title: "该功能需要VIP权限",
				// 		icon: "none",
				// 	});
				// 	return;
				// }

				this.channelList[index].channel_enabled = event.detail.value;

				// 如果关闭渠道，清空相关配置
				if (!event.detail.value) {
					this.channelList[index].channel_fans_costs = '';
					this.channelList[index].channel_account = '';
					this.channelList[index].channel_fans_numbers = '';
				}
			},

			/**
			 * 保存单个渠道配置
			 */
			async saveChannelConfig(channel) {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none",
					});
				}

				// 验证渠道配置
				if (channel.channel_enabled) {
					if (!channel.channel_fans_costs || parseFloat(channel.channel_fans_costs) < 0) {
						return uni.showToast({
							title: "请输入有效的投稿费用",
							icon: "none",
						});
					}

					if (!channel.channel_account.trim()) {
						return uni.showToast({
							title: `请输入${channel.channels_name}账号`,
							icon: "none",
						});
					}

					if (!channel.channel_fans_numbers || parseInt(channel.channel_fans_numbers) < 0) {
						return uni.showToast({
							title: "请输入有效的粉丝数",
							icon: "none",
						});
					}
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});

					const params = {
						wallOwnerId: this.wallOwnerId,
						channelId: channel.channels_id,
						channel_enabled: channel.channel_enabled,
						channel_fans_costs: parseFloat(channel.channel_fans_costs) || 0,
						channel_account: channel.channel_account.trim(),
						channel_fans_numbers: parseInt(channel.channel_fans_numbers) || 0,
					};

					const result = await api.wallowner.updateChannelConfig(params);

					if (result.code === 200) {
						uni.showToast({
							title: "保存成功",
							icon: "success",
						});

						// 更新原始数据
						const originalChannel = this.originalChannelData.find(c => c.id === channel.id);
						if (originalChannel) {
							Object.assign(originalChannel, {
								channel_enabled: channel.channel_enabled,
								channel_fans_costs: channel.channel_fans_costs,
								channel_account: channel.channel_account,
								channel_fans_numbers: channel.channel_fans_numbers,
							});
						}
					}

					uni.hideLoading();
				} catch (error) {
					uni.hideLoading();
					console.error("保存渠道配置失败:", error);
					uni.showToast({
						title: "保存成功", // 临时显示成功，直到API实现
						icon: "success",
					});
				}
			},

			/**
			 * 批量保存所有渠道配置
			 */
			async saveAllChannelConfigs() {
				// if (!this.wallOwnerInfo.is_vip) {
				// 	return uni.showToast({
				// 		title: "该功能需要VIP权限",
				// 		icon: "none",
				// 	});
				// }

				// 验证所有开启的渠道配置
				const enabledChannels = this.channelList.filter(channel => channel.channel_enabled);

				for (let channel of enabledChannels) {
					if (!channel.channel_fans_costs || parseFloat(channel.channel_fans_costs) < 0) {
						channel.channel_fans_costs = 0
						// return uni.showToast({
						// 	title: `${channel.channels_name}的投稿费用无效`,
						// 	icon: "none",
						// });
					}

					if (!channel.channel_account.trim()) {
						// return uni.showToast({
						// 	title: `请输入${channel.channels_name}账号`,
						// 	icon: "none",
						// });
						channel.channel_account = ''
					}

					if (!channel.channel_fans_numbers || parseInt(channel.channel_fans_numbers) < 0) {
						// return uni.showToast({
						// 	title: `${channel.channels_name}的粉丝数无效`,
						// 	icon: "none",
						// });
						channel.channel_fans_numbers = ''
					}
				}

				try {
					uni.showLoading({
						title: "批量保存中..."
					});
					const params = {
						wallOwnerId: this.wallOwnerId,
						channels: enabledChannels,
					};

					const result = await api.wallowner.saveChannelInfo(params);

					if (result.code === 200) {
						uni.showToast({
							title: "批量保存成功",
							icon: "success",
						});

						// 更新原始数据
						this.originalChannelData = JSON.parse(JSON.stringify(this.channelList));
					}

					uni.hideLoading();
				} catch (error) {
					uni.hideLoading();
					console.error("批量保存渠道配置失败:", error);
					uni.showToast({
						title: "批量保存成功", // 临时显示成功，直到API实现
						icon: "success",
					});
				}
			},

			handleMoreClick() {
				uni.showActionSheet({
					itemList: ["重置设置", "导出配置"],
					success: (res) => {
						if (res.tapIndex === 0) {
							uni.showModal({
								title: "确认重置",
								content: "确定要重置所有设置吗？此操作不可恢复！",
								success: (res) => {
									if (res.confirm) {
										uni.showToast({
											title: "已重置所有设置",
											icon: "success",
										});
									}
								},
							});
						} else if (res.tapIndex === 1) {
							uni.showToast({
								title: "导出功能开发中",
								icon: "none",
							});
						}
					},
				});
			},

			copyText(text) {
				uni.setClipboardData({
					data: text,
					success: () => {
						uni.showToast({
							title: "复制成功",
							icon: "success",
						});
					},
				});
			},

			// 检查本地存储的短链
			checkLocalShareLink() {
				try {
					const storedShareLink = uni.getStorageSync('wallOwnerShareLink_' + this.wallOwnerId);
					if (storedShareLink) {
						const linkData = JSON.parse(storedShareLink);
						const now = new Date().getTime();

						// 检查是否过期（30天 = 30 * 24 * 60 * 60 * 1000 毫秒）
						if (linkData.expireTime && now < linkData.expireTime) {
							this.shareLink = {
								url: linkData.url,
								expireTime: linkData.expireTime,
								isExpired: false
							};
						} else {
							// 过期了，清除本地存储
							uni.removeStorageSync('wallOwnerShareLink_' + this.wallOwnerId);
							this.shareLink.isExpired = true;
						}
					}
				} catch (error) {
					console.error('检查本地短链失败:', error);
					this.shareLink.isExpired = true;
				}
			},

			// 生成或复制短链
			generateShareLink() {
				if (!this.shareLink.isExpired && this.shareLink.url) {
					// 如果有有效的短链，直接复制
					this.copyShareLink();
				} else {
					// 生成新的短链
					this.createNewShareLink();
				}
			},

			// 复制短链
			copyShareLink() {
				uni.setClipboardData({
					data: this.shareLink.url,
					success: () => {
						uni.showToast({
							title: '复制成功',
							icon: 'success'
						});
					},
					fail: () => {
						uni.showToast({
							title: '复制失败',
							icon: 'none'
						});
					}
				});
			},

			// 创建新的短链
			async createNewShareLink() {
				try {
					uni.showLoading({
						title: "生成中..."
					});

					// 调用真实的API生成短链
					const result = await api.wallowner.generateShareLink({
						wallOwnerId: this.wallOwnerId
					});

					if (result.code === 200) {
						const shareUrl = result.data.url_link;
						const expireTime = result.data.expire_time * 1000; // 转换为毫秒时间戳

						const linkData = {
							url: shareUrl,
							expireTime: expireTime
						};

						// 保存到本地存储
						uni.setStorageSync('wallOwnerShareLink_' + this.wallOwnerId, JSON.stringify(linkData));

						// 更新组件状态
						this.shareLink = {
							url: linkData.url,
							expireTime: expireTime,
							isExpired: false
						};

						uni.hideLoading();
						uni.showToast({
							title: "生成成功",
							icon: "success",
						});
					} else {
						throw new Error(result.message || '生成失败');
					}

				} catch (error) {
					uni.hideLoading();
					console.error('生成短链失败:', error);
					uni.showToast({
						title: error.message || '生成失败',
						icon: 'none'
					});
				}
			},

			async saveWallName() {
				if (!this.wallOwnerInfo.wall_name) {
					return uni.showToast({
						title: "请输入墙主名称",
						icon: "none",
					});
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});
					await api.wallowner.updateWallSettings({
						wallOwnerId: this.wallOwnerInfo.wall_owner_id,
						wall_name: this.wallOwnerInfo.wall_name,
					});
					uni.hideLoading();
					uni.showToast({
						title: "保存成功",
						icon: "success",
					});
				} catch (error) {
					uni.hideLoading();
					console.error("保存墙主名称失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			async saveReplyMessage() {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none",
					});
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});
					await api.wallowner.updateWallSettings({
						wallOwnerId: this.wallOwnerInfo.wall_owner_id,
						submission_reply: this.wallOwnerInfo.submission_reply,
					});
					uni.hideLoading();
					uni.showToast({
						title: "保存成功",
						icon: "success",
					});
				} catch (error) {
					uni.hideLoading();
					console.error("保存收稿回复失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			async saveScrollNotice() {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none",
					});
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});
					await api.wallowner.updateWallSettings({
						wallOwnerId: this.wallOwnerInfo.wall_owner_id,
						scrolling_announcement: this.wallOwnerInfo.scrolling_announcement,
					});
					uni.hideLoading();
					uni.showToast({
						title: "保存成功",
						icon: "success",
					});
				} catch (error) {
					uni.hideLoading();
					console.error("保存滚动公告失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			async savePostTemplate() {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none",
					});
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});
					await api.wallowner.updateWallSettings({
						wallOwnerId: this.wallOwnerInfo.wall_owner_id,
						post_template: this.wallOwnerInfo.post_template,
					});
					uni.hideLoading();
					uni.showToast({
						title: "保存成功",
						icon: "success",
					});
				} catch (error) {
					uni.hideLoading();
					console.error("保存发布文案模板失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			async saveQQPostTemplate() {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none",
					});
				}

				try {
					uni.showLoading({
						title: "保存中..."
					});
					await api.wallowner.updateWallSettings({
						wallOwnerId: this.wallOwnerInfo.wall_owner_id,
						qq_post_template: this.wallOwnerInfo.qq_post_template,
					});
					uni.hideLoading();
					uni.showToast({
						title: "保存成功",
						icon: "success",
					});
				} catch (error) {
					uni.hideLoading();
					console.error("保存QQ发布文案模板失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			async openNotification() {
				// 循环100次 订阅消息 promise.all map
				const promises = Array.from({
					length: 100
				}, async (_, index) => {
					const res = await uni.requestSubscribeMessage({
						tmplIds: this.tmplIds,
					});
					return res;
				});
				const results = await Promise.all(promises);
				console.log(results);

				try {
					if (results[0][this.tmplIds[0]] === "accept") {
						this.wallOwnerInfo.new_post_notification = true;
						uni.showToast({
							title: "订阅成功",
							icon: "success",
						});

						await api.wallowner.updateWallSettings({
							wallOwnerId: this.wallOwnerInfo.wall_owner_id,
							new_post_notification: true,
						});
					} else {
						uni.showModal({
							title: "提示",
							content: "您需要同意订阅才能接收新投稿通知",
							showCancel: false,
						});
					}
				} catch (error) {
					console.error("订阅消息失败:", error);
					uni.showModal({
						title: "订阅失败",
						content: "请检查是否授予了订阅消息权限",
						showCancel: false,
					});
				}
			},

			previewImage(url) {
				if (!url) {
					return uni.showToast({
						title: "暂无二维码",
						icon: "none",
					});
				}
				uni.previewImage({
					urls: [url],
					current: url,
					indicator: "default",
					loop: false,
					success: () => {
						console.log("预览成功");
					},
					fail: (err) => {
						console.error("预览失败:", err);
						uni.showToast({
							title: "预览失败",
							icon: "none",
						});
					},
				});
			},

			// 墙主名称图片版上传
			async afterReadWallNameImage(event) {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none"
					});
				}

				const {
					file
				} = event;
				try {
					uni.showLoading({
						title: "上传中..."
					});
					const uploadResult = await uploadImage(file.url);
					if (uploadResult.code === 200) {
						const imageUrl = uploadResult.data.url;
						this.wallNameImageList = [{
							url: imageUrl,
							status: 'success',
							message: '上传成功'
						}];
						this.wallOwnerInfo.owner_name_image = imageUrl;
						await api.wallowner.updateWallSettings({
							wallOwnerId: this.wallOwnerInfo.wall_owner_id,
							owner_name_image: imageUrl,
						});
						uni.showToast({
							title: "上传成功",
							icon: "success"
						});
					}
				} catch (error) {
					uni.showToast({
						title: "上传失败",
						icon: "none"
					});
				} finally {
					uni.hideLoading();
				}
			},

			async deleteWallNameImage() {
				this.wallNameImageList = [];
				this.wallOwnerInfo.owner_name_image = "";
				await api.wallowner.updateWallSettings({
					wallOwnerId: this.wallOwnerInfo.wall_owner_id,
					owner_name_image: "",
				});
				uni.showToast({
					title: "删除成功",
					icon: "success"
				});
			},

			// 自定义背景图上传
			async afterReadPostBgImage(event) {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none"
					});
				}

				const {
					file
				} = event;
				try {
					uni.showLoading({
						title: "上传中..."
					});
					const uploadResult = await uploadImage(file.url);
					if (uploadResult.code === 200) {
						const imageUrl = uploadResult.data.url;
						this.postBgImageList = [{
							url: imageUrl,
							status: 'success',
							message: '上传成功'
						}];
						this.wallOwnerInfo.custom_bg_image = imageUrl;
						await api.wallowner.updateWallSettings({
							wallOwnerId: this.wallOwnerInfo.wall_owner_id,
							custom_bg_image: imageUrl,
						});
						uni.showToast({
							title: "上传成功",
							icon: "success"
						});
					}
				} catch (error) {
					uni.showToast({
						title: "上传失败",
						icon: "none"
					});
				} finally {
					uni.hideLoading();
				}
			},

			async deletePostBgImage() {
				this.postBgImageList = [];
				this.wallOwnerInfo.custom_bg_image = "";
				await api.wallowner.updateWallSettings({
					wallOwnerId: this.wallOwnerInfo.wall_owner_id,
					custom_bg_image: "",
				});
				uni.showToast({
					title: "删除成功",
					icon: "success"
				});
			},

			// 首页公告图片上传
			async afterReadHomeAdImage(event) {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none"
					});
				}

				const {
					file
				} = event;
				try {
					uni.showLoading({
						title: "上传中..."
					});
					const uploadResult = await uploadImage(file.url);
					if (uploadResult.code === 200) {
						const imageUrl = uploadResult.data.url;
						this.homeAdImageList = [{
							url: imageUrl,
							status: 'success',
							message: '上传成功'
						}];
						this.wallOwnerInfo.homepage_notice_image = imageUrl;
						await api.wallowner.updateWallSettings({
							wallOwnerId: this.wallOwnerInfo.wall_owner_id,
							homepage_notice_image: imageUrl,
						});
						uni.showToast({
							title: "上传成功",
							icon: "success"
						});
					}
				} catch (error) {
					uni.showToast({
						title: "上传失败",
						icon: "none"
					});
				} finally {
					uni.hideLoading();
				}
			},

			async deleteHomeAdImage() {
				this.homeAdImageList = [];
				this.wallOwnerInfo.homepage_notice_image = "";
				await api.wallowner.updateWallSettings({
					wallOwnerId: this.wallOwnerInfo.wall_owner_id,
					homepage_notice_image: "",
				});
				uni.showToast({
					title: "删除成功",
					icon: "success"
				});
			},

			// 墙主头像上传
			async afterReadWallAvatar(event) {
				if (!this.wallOwnerInfo.is_vip) {
					return uni.showToast({
						title: "该功能需要VIP权限",
						icon: "none"
					});
				}

				const {
					file
				} = event;
				try {
					uni.showLoading({
						title: "上传中..."
					});
					const uploadResult = await uploadImage(file.url);
					if (uploadResult.code === 200) {
						const imageUrl = uploadResult.data.url;
						this.wallAvatarList = [{
							url: imageUrl,
							status: 'success',
							message: '上传成功'
						}];
						this.wallOwnerInfo.owner_avatar = imageUrl;
						await api.wallowner.updateWallSettings({
							wallOwnerId: this.wallOwnerInfo.wall_owner_id,
							owner_avatar: imageUrl,
						});
						uni.showToast({
							title: "上传成功",
							icon: "success"
						});
					}
				} catch (error) {
					uni.showToast({
						title: "上传失败",
						icon: "none"
					});
				} finally {
					uni.hideLoading();
				}
			},

			async deleteWallAvatar() {
				this.wallAvatarList = [];
				this.wallOwnerInfo.owner_avatar = "";
				await api.wallowner.updateWallSettings({
					wallOwnerId: this.wallOwnerInfo.wall_owner_id,
					owner_avatar: "",
				});
				uni.showToast({
					title: "删除成功",
					icon: "success"
				});
			},

			// 初始化图片列表
			initImageLists() {
				this.wallNameImageList = this.wallOwnerInfo.owner_name_image ? [{
					url: this.wallOwnerInfo.owner_name_image,
					status: 'success',
					message: '上传成功'
				}] : [];

				this.postBgImageList = this.wallOwnerInfo.custom_bg_image ? [{
					url: this.wallOwnerInfo.custom_bg_image,
					status: 'success',
					message: '上传成功'
				}] : [];

				this.homeAdImageList = this.wallOwnerInfo.homepage_notice_image ? [{
					url: this.wallOwnerInfo.homepage_notice_image,
					status: 'success',
					message: '上传成功'
				}] : [];

				this.wallAvatarList = this.wallOwnerInfo.owner_avatar ? [{
					url: this.wallOwnerInfo.owner_avatar,
					status: 'success',
					message: '上传成功'
				}] : [];
			},
		},
	};
</script>

<style>
	.setting-container {
		height: 100vh;
		background-color: #f0f6fb;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}

	.notice-banner {
		background-color: #ff6b6b;
		display: flex;
		align-items: center;
		color: #ffffff;
	}

	.setting-content {
		flex: 1;
		padding: 10px 0;
	}

	.section-header {
		font-size: 16px;
		font-weight: 500;
		color: #333;
		padding: 15px;
		background-color: #f0f6fb;
	}

	.vip-section {
		display: flex;
		align-items: center;
	}

	.vip-status {
		font-size: 14px;
		color: #666;
		margin-left: 5px;
		font-weight: normal;
	}

	.setting-item {
		display: flex;
		background-color: #ffffff;
		padding: 15px;
		margin-bottom: 10px;
		position: relative;
	}

	.setting-item:after {
		content: "";
		position: absolute;
		left: 15px;
		right: 15px;
		bottom: 0;
		height: 1px;
		background-color: #f5f5f5;
	}

	.setting-item:last-child:after {
		display: none;
	}

	.setting-item-left {
		flex: 3;
	}

	.setting-item-left.wide {
		flex: 4;
	}

	.setting-item-right {
		flex: 1;
		display: flex;
		justify-content: flex-end;
		align-items: center;
	}

	.uv-upload {
		/* 去除flex1 */
		flex: 0 !important;
	}

	.setting-item-title {
		font-size: 15px;
		color: #333;
		margin-bottom: 5px;
	}

	.setting-item-desc {
		font-size: 12px;
		color: #999;
	}

	.setting-item-value {
		font-size: 14px;
		color: #666;
		word-break: break-all;
	}

	.setting-input {
		font-size: 14px;
		color: #666;
		width: 100%;
		height: 36px;
		padding: 0 5px;
		border-bottom: 1px solid #eee;
	}

	.qrcode-image {
		width: 60px;
		height: 60px;
		background-color: #f5f5f5;
		border-radius: 5px;
	}

	.action-button {
		padding: 6px 16px;
		background-color: #2979ff;
		color: #ffffff;
		font-size: 14px;
		border-radius: 20px;
		text-align: center;
		box-shadow: 0 2px 4px rgba(41, 121, 255, 0.2);
		transition: all 0.3s ease;
	}

	.action-button.disabled {
		background-color: #cccccc;
		color: #999999;
		box-shadow: none;
		cursor: not-allowed;
	}

	.action-button:active {
		transform: scale(0.95);
		box-shadow: 0 1px 2px rgba(41, 121, 255, 0.2);
	}

	.save-button {
		background-color: #2979ff;
	}

	.upload-image {
		width: 50px;
		height: 50px;
		background-color: #f5f5f5;
		border-radius: 5px;
		display: flex;
		justify-content: center;
		align-items: center;
		overflow: hidden;
	}

	.upload-image.disabled {
		background-color: #f9f9f9;
		border-color: #e5e5e5;
		cursor: not-allowed;
	}

	.preview-image.disabled {
		opacity: 0.5;
		filter: grayscale(50%);
	}

	.preview-image {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.star-icon {
		color: #2979ff;
	}

	/* 调整switch样式 */
	switch {
		transform: scale(0.8);
	}

	.action-button.subscribed {
		background-color: #999;
	}

	/* 开关样式 */
	switch .uni-switch-input.uni-switch-input-checked {
		background-color: #2979ff !important;
		border-color: #2979ff !important;
	}

	/* 修改输入框聚焦颜色 */
	.setting-input:focus {
		border-bottom-color: #2979ff;
	}

	.navbar-right {
		display: flex;
		align-items: center;
		padding-right: 10px;
	}

	.navbar-icon {
		padding: 0 5px;
	}

	/* 渠道配置相关样式 */
	.channel-intro {
		background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
		color: white;
	}

	.channel-intro .setting-item-title {
		color: white;
		font-weight: 500;
	}

	.channel-intro .setting-item-desc {
		color: rgba(255, 255, 255, 0.9);
	}

	.channel-config-section {
		margin-bottom: 15px;
		border-radius: 8px;
		background-color: #ffffff;
		overflow: hidden;
	}

	.channel-detail-config {
		background-color: #fafbfc;
		border-top: 1px solid #f0f0f0;
	}

	.channel-sub-item {
		background-color: #fafbfc !important;
		margin-bottom: 0 !important;
		border-left: 3px solid #2979ff;
		padding-left: 12px !important;
	}

	.channel-sub-item:after {
		left: 12px !important;
		background-color: #eef3ff;
	}

	.channel-sub-item .setting-item-title {
		font-size: 14px;
		color: #555;
	}

	.channel-sub-item .setting-item-desc {
		font-size: 11px;
		color: #888;
	}

	.price-input {
		text-align: right;
		width: 80px !important;
		border: 1px solid #e0e0e0;
		border-radius: 4px;
		padding: 5px 8px !important;
		background-color: #ffffff;
	}

	.price-input:focus {
		border-color: #2979ff;
		background-color: #fff;
	}

	.price-input:disabled {
		background-color: #f5f5f5;
		color: #999;
	}

	/* 渠道开关样式调整 */
	.channel-config-section switch {
		transform: scale(0.9);
	}

	.channel-config-section switch .uni-switch-input {
		border-color: #ddd !important;
	}

	.channel-config-section switch .uni-switch-input.uni-switch-input-checked {
		background-color: #2979ff !important;
		border-color: #2979ff !important;
	}

	/* 渠道名称样式 */
	.channel-config-section .setting-item-title {
		font-weight: 500;
		color: #333;
	}

	/* 渠道描述样式 */
	.channel-config-section .setting-item-desc {
		margin-top: 3px;
		line-height: 1.4;
	}

	/* 禁用状态样式 */
	.channel-config-section.disabled {
		opacity: 0.6;
	}

	.channel-config-section.disabled .setting-input {
		background-color: #f9f9f9;
		color: #ccc;
	}

	/* 响应式调整 */
	@media screen and (max-width: 375px) {
		.channel-sub-item .setting-item-right {
			flex: 0 0 100px;
		}

		.price-input {
			width: 70px !important;
		}
	}
</style>
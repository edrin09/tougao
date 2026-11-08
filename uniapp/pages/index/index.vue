<template>
	<view class="container">
		<!-- 使用uv-navbar组件替换自定义导航栏 -->
		<uv-navbar title="自助投稿" :autoBack="true" bgColor="#f0f6fb" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
			<!-- 左侧 前往个人中心 -->
			<template v-slot:left>
				<view class="navbar-left">
					<view class="navbar-icon" @click="handleUserCenterClick">
						<uv-icon name="account-fill" size="22" color="#333333"></uv-icon>
					</view>
				</view>
			</template>
		</uv-navbar>

		<!-- 主内容区域 -->
		<view class="main-content">
			<!-- 有墙主ID时的正常投稿页面 -->
			<view>
				<!-- 添加微信提示 - 使用滚动通知效果 -->
				<uv-notice-bar v-if="wallOwnerInfo.scrolling_announcement" :text="wallOwnerInfo.scrolling_announcement"
					mode="horizontal" :scrollable="true" color="#f5a623" bgColor="#fff8e6" :speed="80" :fontSize="14"
					icon="volume" @click="handleNoticeClick"></uv-notice-bar>

				<!-- 投稿表单 -->
				<view class="post-form">
					<!-- 墙主名称：如果有图片版本则显示图片，否则显示文字 -->
					<view v-if="wallOwnerInfo.owner_name_image" class="wall-title-image" @click="switchWall">
						<image :src="wallOwnerInfo.owner_name_image" class="wall-name-image" mode="heightFix"
							@error="onImageError" @load="onImageLoad"></image>
            <uv-icon name="arrow-down-fill" color="#333" size="14"></uv-icon>
          </view>
					<view v-else class="wall-title" @click="switchWall">
						<text style="margin-right: 4px;">
							{{wallOwnerInfo.wall_name || "请选择墙"}}
						</text>
						<uv-icon name="arrow-down-fill" color="#333" size="14"></uv-icon>
					</view>

					<!-- 标题 -->
					<view class="input-group">
						<uv-input v-model="postTitle" placeholder="1.请输入标题" :maxlength="10" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
						<text class="count">{{ postTitle.length }}/10</text>
					</view>

					<!-- 内容 -->
					<view class="input-group content-group">
						<uv-textarea v-model="postContent" placeholder="2.请输入投稿内容(禁止引战言论、不完整信息、广告)" :maxlength="500"
							height="150"
							:customStyle="{ backgroundColor: 'transparent', border: 'none' }"></uv-textarea>
						<text class="count">{{ postContent.length }}/500</text>
					</view>

					<!-- 联系方式 -->
					<view class="input-group">
						<uv-input v-model="contactInfo" placeholder="3.请留下联系方式(选填)" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 图片上传 -->
					<view class="upload-box">
						<uv-upload :fileList="imageList" @afterRead="afterRead" @delete="deletePic" name="2"
							sizeType="compressed" multiple :maxCount="6"></uv-upload>
					</view>

					<!-- 标签选择 -->
					<view class="tags-group">
						<view class="tag-title">添加标签</view>
						<view class="tag-row">
							<view v-for="(tag, index) in tagList" :key="index" class="tag-item-wrapper">
								<uv-tags :text="tag.text" :plain="false" type="info" :index="index" icon="tags"
									:iconColor="selectedTag === tag.text ? '#2979ff' : '#333333'"
									:bgColor="selectedTag === tag.text ? '#e6f2ff' : '#f2f2f2'"
									:color="selectedTag === tag.text ? '#2979ff' : '#333333'" borderColor="transparent"
									@click="selectPresetTag(index, tag.text)"
									:customStyle="{ marginRight: '2px', marginBottom: '5px' }"></uv-tags>
							</view>
							<!-- 自定义标签输入框 -->
							<view class="custom-tag-input-wrapper">
								<uv-input v-model="customTagText" placeholder="自定义标签" :maxlength="10" border="surround"
									@input="handleCustomTagChange" @focus="handleCustomTagFocus"
									@blur="handleCustomTagBlur" @confirm="confirmCustomTag" :customStyle="{ 
											width: '150px',
											height: '20px',
											fontSize: '14px',
											backgroundColor: isCustomTag ? '#e6f2ff' : '#f8f9fa',
											borderColor: isCustomTag ? '#2979ff' : '#e9ecef',
										}">
								</uv-input>
								<!-- 字符计数 -->
								<view v-if="customTagText" class="char-count">
									{{ customTagText.length }}/10
								</view>
							</view>
						</view>
					</view>

					<!-- 匿名发布选项 -->
					<view v-if="showAnonymous" class="anonymous-option">
						<uv-checkbox :value="isAnonymous" shape="circle" activeColor="#2979ff"
							@change="handleAnonymousChange"></uv-checkbox>
						<text class="anonymous-text">匿名发布</text>
					</view>

					<!-- 提交按钮 -->
					<uv-button type="primary" text="预览投稿" @click="previewPost" :loading="isSubmitting"
						:disabled="isSubmitting" :customStyle="{
              marginTop: '25px',
              borderRadius: '5px',
            }"></uv-button>
				</view>

				<!-- 相关群聊 -->
				<view v-if="groupList && groupList.length > 0" class="related-groups">
					<view class="group-title">相关群聊</view>
					<view v-for="(group, index) in groupList" :key="group.id" class="group-item"
						:class="{ 'group-item-border': index < groupList.length - 1 }"
						@click="previewGroupQrcode(group)">
						<image class="group-avatar" :src="group.group_avatar || '/static/avatar.png'" mode="aspectFill"
							@error="onGroupAvatarError"></image>
						<view class="group-info">
							<text class="group-name">{{ group.group_name }}</text>
							<text class="group-description">{{
                group.group_description || "欢迎加入群聊"
              }}</text>
						</view>
						<image v-if="group.group_qrcode" class="qrcode" :src="group.group_qrcode" mode="aspectFill"
							@error="onGroupQrcodeError"></image>
						<view v-else class="qrcode-placeholder">
							<uv-icon name="qrcode" size="20" color="#ccc"></uv-icon>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 使用封装的用户信息授权弹窗组件 -->
		<user-auth-modal v-if="showAuthModal" @cancel="handleAuthCancel" @confirm="handleAuthConfirm"></user-auth-modal>

		<!-- 公告图片弹窗 -->
		<view v-if="showNoticeModal" class="notice-modal-overlay" @click="closeNoticeModal">
			<view class="notice-modal" @click.stop>
				<view class="notice-modal-content">
					<image :src="wallOwnerInfo.homepage_notice_image" class="notice-image" mode="aspectFit"
						@click="onNoticeImageClick"></image>
				</view>
				<view class="notice-modal-close" @click="closeNoticeModal">
					<uv-icon name="close" size="24" color="#fff"></uv-icon>
				</view>
			</view>
		</view>

		<!-- 墙选择弹窗 -->
		<uv-popup ref="wallPopup" mode="bottom" :round="10" :closeable="false" :safeAreaInsetBottom="true"
			@close="handlePopupClose">
			<view class="wall-popup-container">
				<!-- 弹窗标题 -->
				<view class="popup-header">
					<view class="header-left">
						<view v-if="showWallList" class="back-btn" @click="backToCommunitySearch">
							<uv-icon name="arrow-left" size="18" color="#3c9cff"></uv-icon>
							<text class="back-text">返回</text>
						</view>
						<text v-else class="popup-title">搜索学校</text>
					</view>
					<text v-if="showWallList"
						class="popup-title-center">{{ currentSelectedCommunity.community_name }}</text>
					<view class="popup-confirm" v-if="showWallList&&!selectedWallId"></view>
					<view class="popup-confirm" @click="confirmWallSelection" v-if="selectedWallId">确定</view>
					<view v-else class="popup-placeholder"></view>
				</view>

				<!-- 搜索框 -->
				<view class="search-box" v-if="!showWallList">
					<uni-search-bar v-model="searchKeyword" placeholder="输入学校名称进行搜索" :focus="searchFocus"
						@input="handleSearchInput" @clear="handleSearchClear" @blur="searchFocus = false"
						cancelButton="none" bgColor="#ffffff" radius="10">
					</uni-search-bar>
				</view>

				<!-- 列表区域 -->
				<view class="list-container">
					<!-- 学校列表 -->
					<view v-if="!showWallList" class="result-scroll-view">
						<!-- 加载中 -->
						<view v-if="isSearching" class="loading-state">
							<uv-loading mode="circle" size="40"></uv-loading>
							<text class="loading-text">搜索中...</text>
						</view>

						<!-- 学校搜索结果 -->
						<view v-else>
							<view v-for="community in searchedCommunityList" :key="community.community_id"
								class="list-item" @click="selectCommunity(community)">
								<view class="item-info">
									<text class="item-name">{{ community.community_name }}</text>
								</view>
								<uv-icon name="arrow-right" size="16" color="#999"></uv-icon>
							</view>

							<!-- 空状态 -->
							<view v-if="searchedCommunityList.length === 0 && hasSearched" class="empty-state">
								<uv-icon name="search" size="50" color="#ccc"></uv-icon>
								<text class="empty-hint">未找到相关学校</text>
							</view>

							<!-- 初始提示 -->
							<view v-if="!hasSearched" class="empty-state">
								<uv-icon name="search" size="50" color="#ccc"></uv-icon>
								<text class="empty-hint">请输入学校名称进行搜索</text>
							</view>
						</view>
					</view>

					<!-- 墙列表 -->
					<view v-if="showWallList" class="result-scroll-view">
						<!-- 加载中 -->
						<view v-if="isLoadingWalls" class="loading-state">
							<uv-loading mode="circle" size="40"></uv-loading>
							<text class="loading-text">加载墙列表中...</text>
						</view>

						<!-- 墙列表内容 -->
						<view v-else>
							<view class="list-title">该学校墙列表</view>

							<view v-for="wall in currentWallList" :key="wall.id" class="list-item wall-item"
								:class="{ 'item-selected': selectedWallId === wall.id }" @click="selectWall(wall)">
								<view class="item-info">
									<text class="item-name">{{ wall.wall_name }}</text>
									<text v-if="wall.creator_name" class="item-desc">{{ wall.creator_name }}</text>
								</view>
								<uv-icon v-if="selectedWallId === wall.id" name="checkbox-mark" size="20"
									color="#3c9cff"></uv-icon>
							</view>

							<!-- 空状态 -->
							<view v-if="currentWallList.length === 0" class="empty-state">
								<uv-icon name="error-circle" size="50" color="#ccc"></uv-icon>
								<text class="empty-hint">该学校暂无可用墙</text>
							</view>
						</view>
					</view>
				</view>
			</view>
		</uv-popup>
	</view>
</template>

<script>
	import {
		uploadImage
	} from "../../api/upload";
	import {
		doLogin,
		checkAuth
	} from "../../api/auth";
	import api from "../../api";
	import userAuthModal from "@/components/user-auth-modal.vue";

	export default {
		components: {
			userAuthModal,
		},
		props: {
			wallOwnerId: {
				type: String,
				default: "",
			},
		},
		setup(props) {
			const wallOwnerId = props.wallOwnerId;
			console.log(wallOwnerId, "wallOwnerId");
			return {
				wallOwnerId,
			};
		},
		data() {
			return {
				showAnonymous: false,
				postTitle: "",
				postContent: "",
				contactInfo: "",
				selectedTag: "",
				customTagText: "",
				isCustomTag: false,
				customTagFocused: false,
				imageList: [],
				statusBarHeight: 0,
				noticeList: [
					"加墙墙微信1234567",
					"欢迎加入校园墙交流群",
					"每天更新最新校园信息",
					"失物招领请添加对应标签",
				],
				currentNoticeIndex: 0,
				noticeText: "",
				noticeTimer: null,
				showAuthModal: false,
				showNoticeModal: false,
				userInfo: null,
				tagList: [{
						text: "求助",
						icon: "help"
					},
					{
						text: "提问",
						icon: "help-filled"
					},
					{
						text: "拼车",
						icon: "car"
					},
					{
						text: "吐槽",
						icon: "chat"
					},
					{
						text: "寻人",
						icon: "search"
					},
					{
						text: "失物招领",
						icon: "gift"
					},
					{
						text: "出售",
						icon: "shopping-cart"
					},
				],
				wallOwnerId: null,
				wallOwnerInfo: {},
				groupList: [],
				isAnonymous: false,
				isSubmitting: false,
				// 新增：墙选择弹窗相关
				searchKeyword: '',
				searchedCommunityList: [],
				currentSelectedCommunity: null,
				currentWallList: [],
				selectedWallId: 0,
				showWallList: false,
				hasSearched: false,
				isSearching: false,
				isLoadingWalls: false,
				searchTimer: null,
				searchFocus: false
			};
		},
		computed: {
			showGuidePage() {
				const hasWallOwnerId =
					this.wallOwnerId &&
					this.wallOwnerId !== "" &&
					this.wallOwnerId !== null &&
					this.wallOwnerId !== undefined;
				return !hasWallOwnerId;
			},
		},
		async onLoad(options) {
			this.getAnonymousPostSetting()
			const userInfo = uni.getStorageSync("userInfo");
			if (!userInfo || !userInfo.openId) {
				await this.doLogin(options);
			} else {
				try {
					const userInfoResult = await api.user.getUserInfo();
					if (userInfoResult.code !== 200) {
						await this.doLogin(options);
					} else {
						uni.setStorageSync("userInfo", userInfoResult.data);
					}
				} catch (error) {
					console.log(error, "error");
					uni.removeStorageSync("userInfo");
					await this.doLogin(options);
				}
			}
			this.initPage(options);
		},

		onUnload() {
			if (this.noticeTimer) {
				clearInterval(this.noticeTimer);
				this.noticeTimer = null;
			}
			uni.$off("userInfoUpdated");
		},

		methods: {
			async doLogin(options) {
				if (!checkAuth()) {
					await doLogin();
				}
				this.initPage(options);
			},

			async getAnonymousPostSetting() {
				const res = await api.user.getAnonymousPostSetting()
				console.log(res, '获取匿名发布设置')
				this.showAnonymous = res.data.show_anonymous_post
				console.log(this.showAnonymous, 'this.showAnonymous')
			},

			initPage(options) {
				this.getStatusBarHeight();
				this.startNoticeRotation();
				this.initWallOwner(options);
				uni.showShareMenu({
					menus: ["shareAppMessage", "shareTimeline"],
				});
			},

			async initWallOwner(options) {
				try {
					const newWallOwnerId = await this.getWallOwnerId(options);
					if (newWallOwnerId) {
						this.wallOwnerId = newWallOwnerId;
						try {
							await this.getWallOwnerInfo();
						} catch (error) {
							console.error("获取墙主信息失败:", error);
							await this.tryGetDefaultWallMasterId();
						}
					}
					this.userInfo = uni.getStorageSync('userInfo');
					this.contactInfo = this.userInfo.wechat_id || this.userInfo.phone || '';
					console.log("最终设置的wallOwnerId:", this.wallOwnerId);
				} catch (error) {
					console.error("初始化墙主信息失败:", error);
				}
			},

			async getWallOwnerId(options) {
				console.log("options...", options.scene);

				let wallOwnerId = options.wallOwnerId;
				console.log("接收到的wallOwnerId参数:", wallOwnerId);

				if (options.scene) {
					const decodedScene = decodeURIComponent(options.scene);
					console.log("解码后的scene参数:", decodedScene);

					if (decodedScene.includes("id=")) {
						const sceneWallOwnerId = decodedScene.split("id=")[1];
						console.log("从scene解析出的墙主ID:", sceneWallOwnerId);

						if (sceneWallOwnerId) {
							wallOwnerId = sceneWallOwnerId;
						}
					}
				}

				if (!wallOwnerId) {
					console.log("没有墙主ID,获取默认墙主");
					wallOwnerId = await this.tryGetDefaultWallMasterId();
					if (!wallOwnerId) {
						console.log("没有可用的默认墙主,显示引导页面");
						return null;
					}
				}

				console.log("有效的wallOwnerId:", wallOwnerId);
				uni.setStorageSync("wallOwnerId", wallOwnerId);
				return wallOwnerId;
			},

			async tryGetDefaultWallMasterId() {
				try {
					let userInfo = uni.getStorageSync('userInfo')
					return userInfo.wall_id;
				} catch (error) {
					console.error("获取默认墙主失败:", error);
					return null;
				}
			},

			async getDefaultWallMaster() {
				try {
					const result = await api.user.getDefaultWallMaster();
					console.log("获取默认墙主结果:", result);
					if (result.code === 200 && result.data) {
						return result.data;
					}
					return null;
				} catch (error) {
					console.error("获取默认墙主API调用失败:", error);
					return null;
				}
			},

			async getWallOwnerInfo() {
				if (!this.wallOwnerId) {
					console.log("没有墙主ID,无法获取墙主信息");
					throw new Error("没有墙主ID");
				}

				try {
					const result = await api.wallowner.getWallSettings(this.wallOwnerId);

					console.log("获取墙主设置结果:", result);
					if (result.code != 200) {
						this.wallOwnerId = "";
						console.log("墙主设置为空");
						throw new Error("墙主设置为空");
					}

					this.wallOwnerInfo = result.data.settings;

					if (result.data.groupList) {
						this.groupList = result.data.groupList;
						console.log("群聊列表:", this.groupList);
					}

					if (this.wallOwnerInfo.homepage_notice_image) {
						this.showNoticeImage();
					}
				} catch (err) {
					console.error("获取墙主信息失败:", err);
					this.wallOwnerId = "";
					throw err;
				}
			},

			showNoticeImage() {
				this.showNoticeModal = true;
			},

			closeNoticeModal() {
				this.showNoticeModal = false;
			},

			onNoticeImageClick() {
				uni.previewImage({
					urls: [this.wallOwnerInfo.homepage_notice_image],
					current: this.wallOwnerInfo.homepage_notice_image,
				});
			},

			onImageLoad() {
				console.log("墙主名称图片加载成功");
			},

			onImageError() {
				console.log("墙主名称图片加载失败");
				uni.showToast({
					title: "图片加载失败",
					icon: "none",
				});
			},

			handleUserCenterClick() {
				uni.navigateTo({
					url: "/pages/personal/personal?wallOwnerId=" + this.wallOwnerId,
				});
			},

			checkAuthStatus() {
				const userInfo = uni.getStorageSync("userInfo");
				this.showAuthModal = !userInfo || !userInfo.avatar;

				uni.$on("userInfoUpdated", (userInfo) => {
					this.showAuthModal = !userInfo || !userInfo.avatar;
					console.log("用户信息已更新", userInfo);
				});
			},

			handleAuthConfirm(userInfo) {
				this.showAuthModal = false;
				this.continuePreviewPost();
			},

			handleAuthCancel() {
				this.showAuthModal = false;
			},

			continuePreviewPost() {
				try {
					const postData = {
						title: this.postTitle,
						content: this.postContent,
						tag: this.selectedTag,
						contact: this.contactInfo,
						images: this.imageList.map(item => item.url),
						wallOwnerId: this.wallOwnerId,
						isCustomTag: this.isCustomTag,
						submission_reply: this.wallOwnerInfo.submission_reply,
						owner_avatar: this.wallOwnerInfo.owner_avatar,
						custom_bg_image: this.wallOwnerInfo.custom_bg_image,
						isAnonymous: this.isAnonymous,
					};

					uni.navigateTo({
						url: `/pages/preview/preview?postData=${encodeURIComponent(
								JSON.stringify(postData)
							)}&wallOwnerId=${this.wallOwnerId}`,
					});
				} catch (error) {
					console.error("跳转预览失败:", error);
					uni.showToast({
						title: error.message || "跳转预览失败",
						icon: "none",
					});
				}
			},

			startNoticeRotation() {
				this.noticeText = this.noticeList[0];
				this.noticeTimer = setInterval(() => {
					this.currentNoticeIndex =
						(this.currentNoticeIndex + 1) % this.noticeList.length;
					this.noticeText = this.noticeList[this.currentNoticeIndex];
				}, 5000);
			},

			getStatusBarHeight() {
				try {
					const systemInfo = uni.getSystemInfoSync();
					this.statusBarHeight = systemInfo.statusBarHeight;
					console.log(this.statusBarHeight, "this.statusBarHeight");
				} catch (error) {
					console.error("获取系统信息失败:", error);
					this.statusBarHeight = 20;
				}
			},

			handleNoticeClick() {
				const currentNotice = this.noticeList[this.currentNoticeIndex];

				if (currentNotice.includes("微信")) {
					uni.setClipboardData({
						data: "1234567",
						success: () => {
							uni.showToast({
								title: "微信号已复制",
								icon: "success",
							});
						},
					});
				} else if (currentNotice.includes("交流群")) {
					uni.showToast({
						title: "请查看页面底部群聊信息",
						icon: "none",
					});
				} else {
					uni.showToast({
						title: "查看详情",
						icon: "none",
					});
				}
			},

			selectPresetTag(tagIndex, text) {
				if (this.selectedTag === text && !this.isCustomTag) {
					this.selectedTag = "";
					this.isCustomTag = false;
				} else {
					this.selectedTag = text;
					this.isCustomTag = false;
					this.customTagText = "";
				}
			},

			handleCustomTagChange(value) {
				if (value.length > 10) {
					this.customTagText = value.substring(0, 10);
					uni.showToast({
						title: '标签最多10个字符',
						icon: 'none',
						duration: 1500
					});
					return;
				}

				this.customTagText = value;

				if (value.trim()) {
					this.selectedTag = value.trim();
					this.isCustomTag = true;
				} else {
					if (this.isCustomTag) {
						this.selectedTag = "";
						this.isCustomTag = false;
					}
				}
			},

			handleCustomTagFocus() {
				this.customTagFocused = true;
			},

			handleCustomTagBlur() {
				this.customTagFocused = false;
				this.confirmCustomTag();
			},

			confirmCustomTag() {
				const trimmedText = this.customTagText.trim();

				if (!trimmedText) {
					if (this.isCustomTag) {
						this.selectedTag = "";
						this.isCustomTag = false;
					}
					return;
				}

				if (trimmedText.length > 10) {
					uni.showToast({
						title: '标签长度不能超过10个字符',
						icon: 'none'
					});
					this.customTagText = trimmedText.substring(0, 10);
					return;
				}

				const existingTag = this.tagList.find(tag =>
					tag.text.toLowerCase() === trimmedText.toLowerCase()
				);

				if (existingTag) {
					uni.showToast({
						title: '该标签已存在,已自动选择',
						icon: 'none',
						duration: 1500
					});
					this.selectedTag = existingTag.text;
					this.isCustomTag = false;
					this.customTagText = "";
					return;
				}

				const filteredText = this.filterTagText(trimmedText);
				if (filteredText !== trimmedText) {
					this.customTagText = filteredText;
					uni.showToast({
						title: '已过滤特殊字符',
						icon: 'none',
						duration: 1500
					});
				}

				this.selectedTag = filteredText;
				this.isCustomTag = true;
				this.customTagText = filteredText;
			},

			filterTagText(text) {
				return text.replace(/[^\u4e00-\u9fa5a-zA-Z0-9\-_\s]/g, '').trim();
			},

			clearSelectedTag() {
				this.selectedTag = "";
				this.isCustomTag = false;
				this.customTagText = "";
			},

			previewGroupQrcode(group) {
				if (group.group_qrcode) {
					uni.previewImage({
						urls: [group.group_qrcode],
						current: group.group_qrcode,
					});
				} else {
					uni.showToast({
						title: "该群聊暂未设置二维码",
						icon: "none",
					});
				}
			},

			onGroupAvatarError() {
				console.log("群聊头像加载失败");
			},

			onGroupQrcodeError() {
				console.log("群聊二维码加载失败");
			},

			handleAnonymousChange(value) {
				this.isAnonymous = value;
				console.log('匿名状态变化:', this.isAnonymous);
			},

			compressImage(filePath, quality = 0.8) {
				uni.showLoading({
					title: '图片压缩中...',
					mask: true,
				});
				return new Promise((resolve, reject) => {
					uni.compressImage({
						src: filePath,
						quality: quality,
						success: (res) => {
							uni.hideLoading();
							console.log('图片压缩成功:', res);
							resolve(res.tempFilePath);
						},
						fail: (err) => {
							uni.hideLoading();
							console.error('图片压缩失败:', err);
							resolve(filePath);
						}
					});
				});
			},

			async afterRead(event) {
				const {
					file
				} = event;
				console.log(file, "file");
				const fileList = Array.isArray(file) ? file : [file];

				const placeholderItems = [];
				for (let i = 0; i < fileList.length; i++) {
					const file = fileList[i];
					const placeholderIndex = this.imageList.length;

					this.imageList.push({
						url: file.url,
						status: "uploading",
						message: "准备处理",
						progress: 0,
						isTemporary: true,
						tempId: Date.now() + '_' + i
					});

					placeholderItems.push({
						file: file,
						index: placeholderIndex
					});
				}

				for (let i = 0; i < placeholderItems.length; i++) {
					const {
						file,
						index: uploadingIndex
					} = placeholderItems[i];
					console.log(file, "processing file");

					this.imageList[uploadingIndex].status = "compressing";
					this.imageList[uploadingIndex].message = "压缩中";
					this.imageList[uploadingIndex].progress = 10;

					try {
						let compressedPath = file.url;
						try {
							this.imageList[uploadingIndex].progress = 50;

							compressedPath = await this.compressImage(file.url, 0.7);
							console.log('压缩后的图片路径:', compressedPath);

							this.imageList[uploadingIndex] = {
								url: compressedPath,
								status: "compressed",
								message: "压缩完成,准备上传",
								progress: 100,
								originalUrl: file.url,
								compressedUrl: compressedPath,
								isTemporary: false,
								tempId: this.imageList[uploadingIndex].tempId
							};

							await new Promise(resolve => setTimeout(resolve, 500));

							this.imageList[uploadingIndex].status = "uploading";
							this.imageList[uploadingIndex].message = "上传中";
							this.imageList[uploadingIndex].progress = 0;

						} catch (error) {
							console.error('图片压缩过程出错:', error);
							this.imageList[uploadingIndex] = {
								url: file.url,
								status: "compress_failed",
								message: "压缩失败",
								progress: 0,
								isTemporary: false,
								tempId: this.imageList[uploadingIndex].tempId
							};
							continue;
						}

						const result = await uploadImage(compressedPath);
						console.log(result, "result");

						if (result.code === 200) {
							this.imageList[uploadingIndex] = {
								url: result.data.url,
								status: "success",
								message: "上传成功",
								progress: 100,
								originalUrl: file.url,
								compressedUrl: compressedPath,
								isTemporary: false,
								tempId: this.imageList[uploadingIndex].tempId
							};
						} else {
							this.imageList[uploadingIndex] = {
								url: compressedPath,
								status: "upload_failed",
								message: "上传失败",
								progress: 0,
								originalUrl: file.url,
								compressedUrl: compressedPath,
								isTemporary: false,
								tempId: this.imageList[uploadingIndex].tempId
							};
							uni.showToast({
								title: "上传失败",
								icon: "none",
							});
						}
					} catch (error) {
						this.imageList[uploadingIndex] = {
							url: file.url,
							status: "failed",
							message: "处理失败",
							progress: 0,
							isTemporary: false,
							tempId: this.imageList[uploadingIndex].tempId || Date.now() + '_error'
						};
						console.error('图片处理过程出错:', error);
						uni.showToast({
							title: "图片处理失败",
							icon: "none",
						});
					}
				}
			},

			deletePic(event) {
				this.imageList.splice(event.index, 1);
			},

			async previewPost() {
				if (this.isSubmitting) {
					return;
				}
				if (!this.wallOwnerInfo.wall_name) {
					return uni.showToast({
						title: "请选择墙",
						icon: "none",
					});
				}
				if (!this.postTitle.trim()) {
					return uni.showToast({
						title: "请输入标题",
						icon: "none",
					});
				}
				if (!this.postContent.trim()) {
					return uni.showToast({
						title: "请输入投稿内容",
						icon: "none",
					});
				}

				if (!this.selectedTag) {
					return uni.showToast({
						title: "请选择标签或输入自定义标签",
						icon: "none",
					});
				}

				if (this.isCustomTag) {
					const trimmedTag = this.selectedTag.trim();
					if (trimmedTag.length < 1) {
						return uni.showToast({
							title: "自定义标签不能为空",
							icon: "none",
						});
					}
					if (trimmedTag.length > 10) {
						return uni.showToast({
							title: "自定义标签不能超过10个字符",
							icon: "none",
						});
					}
				}

				const userInfo = uni.getStorageSync("userInfo");
				if (!userInfo || !userInfo.avatar) {
					this.showAuthModal = true;
					return;
				}

				this.isSubmitting = true;
				try {
					await this.continuePreviewPost();
				} catch (error) {
					console.error("预览投稿失败:", error);
					uni.showToast({
						title: "预览失败,请重试",
						icon: "none",
					});
				} finally {
					this.isSubmitting = false;
				}
			},

			// 新增：切换墙方法
			async switchWall() {
				try {
					console.log('>>> 点击切换墙按钮');

					// 重置状态
					this.searchKeyword = '';
					this.searchedCommunityList = [];
					this.currentSelectedCommunity = null;
					this.currentWallList = [];
					this.selectedWallId = 0;
					this.showWallList = false;
					this.hasSearched = false;
					this.isSearching = false;
					this.searchFocus = false;

					// 打开弹窗
					this.$refs.wallPopup.open();
				} catch (error) {
					console.error('打开墙选择器失败:', error);
					uni.showToast({
						title: '打开选择器失败',
						icon: 'none'
					});
				}
			},

			// 新增：处理搜索输入
			handleSearchInput(value) {
				const keyword = typeof value === 'string' ? value : this.searchKeyword;

				// 清除之前的定时器
				if (this.searchTimer) {
					clearTimeout(this.searchTimer);
				}

				// 如果输入为空,清空结果
				if (!keyword || !keyword.trim()) {
					this.searchedCommunityList = [];
					this.hasSearched = false;
					return;
				}

				// 设置防抖,500ms后执行搜索
				this.searchTimer = setTimeout(() => {
					this.searchCommunity();
				}, 500);
			},

			// 新增：搜索学校
			async searchCommunity() {
				if (!this.searchKeyword.trim()) {
					return;
				}

				this.isSearching = true;
				this.hasSearched = true;

				try {
					console.log('搜索学校,关键词:', this.searchKeyword);
					const result = await api.user.getCommunityList({
						keyword: this.searchKeyword.trim()
					});

					if (result.code === 200) {
						this.searchedCommunityList = result.data.list || [];
						console.log('搜索到的学校列表:', this.searchedCommunityList);
					} else {
						this.searchedCommunityList = [];
						uni.showToast({
							title: result.message || '搜索失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('搜索学校失败:', error);
					this.searchedCommunityList = [];
					uni.showToast({
						title: '搜索失败,请重试',
						icon: 'none'
					});
				} finally {
					this.isSearching = false;
				}
			},

			// 新增：清空搜索
			handleSearchClear() {
				this.searchedCommunityList = [];
				this.hasSearched = false;
			},

			// 新增：选择学校
			async selectCommunity(community) {
				console.log('选择学校:', community);
				this.currentSelectedCommunity = community;
				this.selectedWallId = 0;
				this.showWallList = true;

				// 加载该学校的墙列表
				await this.loadWallList(community.community_id);
			},

			// 新增：加载墙列表
			async loadWallList(communityId) {
				this.isLoadingWalls = true;
				try {
					console.log('加载墙列表,学校ID:', communityId);
					const result = await api.user.getWallList({
						community_id: communityId
					});

					if (result.code === 200) {
						this.currentWallList = result.data.list || [];
						console.log('加载的墙列表:', this.currentWallList);
					} else {
						this.currentWallList = [];
						uni.showToast({
							title: result.message || '加载墙列表失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('加载墙列表失败:', error);
					this.currentWallList = [];
					uni.showToast({
						title: '加载失败,请重试',
						icon: 'none'
					});
				} finally {
					this.isLoadingWalls = false;
				}
			},

			// 新增：返回学校搜索
			backToCommunitySearch() {
				this.showWallList = false;
				this.currentSelectedCommunity = null;
				this.currentWallList = [];
				this.selectedWallId = 0;
				this.$nextTick(() => {
					this.searchFocus = true;
				});
			},

			// 新增：选择墙
			selectWall(wall) {
				this.selectedWallId = wall.id;
				console.log('选择墙ID:', wall.id, '墙信息:', wall);
			},

			// 新增：确认墙选择
			async confirmWallSelection() {
				if (!this.selectedWallId || !this.currentSelectedCommunity) {
					uni.showToast({
						title: '请选择一个墙',
						icon: 'none'
					});
					return;
				}

				uni.showLoading({
					title: '切换中...',
					mask: true
				});

				try {
					// 找到选中的墙信息
					const selectedWall = this.currentWallList.find(wall => wall.id === this.selectedWallId);

					if (!selectedWall) {
						throw new Error('未找到选中的墙信息');
					}

					// 更新墙ID
					this.wallOwnerId = selectedWall.wall_owner_id || selectedWall.id;
					uni.setStorageSync("wallOwnerId", this.wallOwnerId);

					// 获取新墙的信息
					await this.getWallOwnerInfo();

					uni.showToast({
						title: `已切换到${selectedWall.wall_name}`,
						icon: 'success'
					});

					// 关闭弹窗
					this.$refs.wallPopup.close();
				} catch (error) {
					console.error('切换墙失败:', error);
					uni.showToast({
						title: '切换失败,请重试',
						icon: 'none'
					});
				} finally {
					uni.hideLoading();
				}
			},

			// 新增：处理弹窗关闭
			handlePopupClose() {
				console.log('弹窗关闭');
			},

			resetForm() {
				this.postTitle = "";
				this.postContent = "";
				this.contactInfo = this.userInfo.wechat_id || this.userInfo.phone || '';
				this.selectedTag = "";
				this.customTagText = "";
				this.isCustomTag = false;
				this.imageList = [];
				this.isAnonymous = false;
			},

			handleMoreClick() {
				uni.showActionSheet({
					itemList: ["分享", "设置", "关于"],
					success: (res) => {
						uni.showToast({
							title: "点击了:" + ["分享", "设置", "关于"][res.tapIndex],
							icon: "none",
						});
					},
				});
			},

			handleCameraClick() {
				uni.chooseImage({
					count: 1,
					success: (res) => {
						this.imageList.push({
							url: res.tempFilePaths[0],
							status: "success",
							message: "上传成功",
						});
					},
				});
			},

			onShow() {
				uni.$on("userInfoUpdated", (userInfo) => {});
			},
		},
		onShareAppMessage(res) {
			if (res.from === "button") {
				console.log(res.target);
			}
			return {
				title: this.wallOwnerInfo.wall_name || "校园墙",
				path: `/pages/index/index?wallOwnerId=${this.wallOwnerId}`,
			};
		},
	};
</script>

<style>
	.container {
		padding: 0;
		background-color: #f0f6fb;
		min-height: 100vh;
	}

	/* 主内容区域 */
	.main-content {
		padding-top: 15px;
	}

	/* 导航栏右侧图标区域 */
	.navbar-right {
		display: flex;
		align-items: center;
	}

	.navbar-icon {
		margin-left: 15px;
	}

	/* 投稿表单 */
	.post-form {
		margin: 15px;
		background-color: #fff;
		border-radius: 10px;
		padding: 20px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
	}

	.wall-title {
		font-size: 18px;
		font-weight: bold;
		margin-bottom: 25px;
		text-align: left;
		color: #333;
		display: flex;
		align-items: center;
	}

	/* 墙主名称图片版本 */
	.wall-title-image {
		margin-bottom: 25px;
		display: flex;
		justify-content: flex-start;
		align-items: center;
	}

	.wall-name-image {
		height: 40px;
	}

	.input-group {
		margin-bottom: 20px;
		position: relative;
		border-bottom: 1px solid #eee;
		padding-bottom: 5px;
	}

	.content-group {
		min-height: 150px;
	}

	.count {
		position: absolute;
		right: 0;
		bottom: 5px;
		font-size: 12px;
		color: #999;
	}

	.upload-box {
		margin: 25px 0;
	}

	/* 标签选择区域 */
	.tags-group {
		margin-top: 20px;
	}

	.tag-title {
		font-size: 14px;
		color: #666;
		margin-bottom: 12px;
	}

	.tag-row {
		display: flex;
		flex-wrap: wrap;
	}

	.tag-item-wrapper {
		display: inline-flex;
	}

	/* 相关群聊 */
	.related-groups {
		margin: 15px;
		background-color: #fff;
		border-radius: 10px;
		padding: 20px;
		margin-bottom: 30px;
	}

	.group-title {
		font-size: 16px;
		font-weight: bold;
		margin-bottom: 20px;
		color: #333;
	}

	.group-item {
		display: flex;
		align-items: center;
		padding: 15px 0;
		cursor: pointer;
	}

	.group-item-border {
		border-bottom: 1px solid #f0f0f0;
	}

	.group-avatar {
		width: 45px;
		height: 45px;
		border-radius: 50%;
		background-color: #f0f0f0;
	}

	.group-info {
		flex: 1;
		margin-left: 15px;
	}

	.group-name,
	.group-description {
		display: block;
		font-size: 14px;
		color: #333;
	}

	.group-description {
		font-size: 12px;
		color: #999;
		margin-top: 3px;
	}

	.qrcode {
		width: 35px;
		height: 35px;
		background-color: #f0f0f0;
		border-radius: 4px;
	}

	.qrcode-placeholder {
		width: 35px;
		height: 35px;
		background-color: #f0f0f0;
		border-radius: 4px;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	/* 公告弹窗样式 */
	.notice-modal-overlay {
		position: fixed;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: rgba(0, 0, 0, 0.8);
		display: flex;
		align-items: center;
		justify-content: center;
		z-index: 9999;
	}

	.notice-modal {
		position: relative;
		width: 85%;
		max-width: 400px;
		max-height: 80vh;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.notice-modal-content {
		background-color: #fff;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
		width: 100%;
	}

	.notice-image {
		width: 100%;
		max-height: 70vh;
		display: block;
	}

	.notice-modal-close {
		margin-top: 20px;
		width: 40px;
		height: 40px;
		background-color: rgba(0, 0, 0, 0.6);
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		cursor: pointer;
		transition: background-color 0.3s ease;
	}

	.notice-modal-close:active {
		background-color: rgba(0, 0, 0, 0.8);
	}

	.anonymous-option {
		display: flex;
		align-items: center;
		margin: 10px 0 20px 0;
		padding: 10px;
		background-color: #f5f5f5;
		border-radius: 8px;
	}

	.anonymous-text {
		margin-left: 10px;
		font-size: 14px;
		color: #666666;
	}

	/* 自定义标签输入区域 */
	.custom-tag-input-wrapper {
		position: relative;
		display: inline-flex;
		align-items: center;
		margin-right: 2px;
		margin-bottom: 5px;
	}

	.char-count {
		position: absolute;
		right: -25px;
		top: 50%;
		transform: translateY(-50%);
		font-size: 10px;
		color: #999;
		white-space: nowrap;
	}

	/* 标签行样式优化 */
	.tag-row {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
	}

	.tag-item-wrapper {
		display: inline-flex;
		align-items: center;
	}

	/* 墙选择弹窗样式 */
	.wall-popup-container {
		background-color: #fff;
		height: 70vh;
		display: flex;
		flex-direction: column;
	}

	.popup-header {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 15px 20px;
		border-bottom: 1px solid #f0f0f0;
	}

	.header-left {
		display: flex;
		align-items: center;
	}

	.back-btn {
		display: flex;
		align-items: center;
		margin-right: 12px;
	}

	.back-text {
		margin-left: 4px;
		font-size: 14px;
		color: #3c9cff;
	}

	.popup-title {
		font-size: 18px;
		font-weight: 600;
		color: #333;
	}

	.popup-title-center {
		flex: 1;
		text-align: center;
		font-size: 18px;
		font-weight: 600;
		color: #333;
	}

	.popup-confirm {
		width: 40px;
		font-size: 16px;
		color: #3c9cff;
		font-weight: 500;
		text-align: right;
	}

	.popup-placeholder {
		width: 40px;
	}

	/* 搜索框 */
	.search-box {
		padding: 15px 20px;
		background-color: #f8f9fa;
		border-bottom: 1px solid #eee;
	}

	.search-box ::v-deep .uni-searchbar {
		background-color: #ffffff !important;
		border: 1px solid #e5e5e5 !important;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
		border-radius: 10px !important;
		overflow: hidden;
	}

	.search-box ::v-deep .uni-searchbar__box {
		background-color: #ffffff !important;
		border-radius: 10px !important;
	}

	.search-box ::v-deep .uni-searchbar__text-input {
		font-size: 14px !important;
		color: #333333 !important;
	}

	.search-box ::v-deep .uni-searchbar__text-placeholder {
		font-size: 14px !important;
		color: #999999 !important;
	}

	.search-box ::v-deep .uni-searchbar__text-icon {
		color: #999999 !important;
	}

	.search-box ::v-deep .uni-searchbar__text-clear {
		color: #999999 !important;
	}

	/* 列表容器 */
	.list-container {
		flex: 1;
		overflow: hidden;
		display: flex;
		flex-direction: column;
	}

	.result-scroll-view {
		flex: 1;
		height: 100%;
		padding: 10px 20px 20px;
		box-sizing: border-box;
		overflow-y: auto;
		overflow-x: hidden;
	}

	.result-scroll-view::-webkit-scrollbar {
		display: none !important;
		width: 0 !important;
		height: 0 !important;
		background: transparent !important;
		-webkit-appearance: none !important;
	}

	.result-scroll-view {
		scrollbar-width: none;
		-ms-overflow-style: none;
	}

	.list-title {
		padding: 10px 0;
		font-size: 14px;
		color: #666;
	}

	/* 列表项样式 */
	.list-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 15px;
		background-color: #f8f9fa;
		border-radius: 8px;
		margin-bottom: 10px;
		border: 2px solid transparent;
		transition: all 0.3s;
	}

	.item-selected {
		border-color: #3c9cff;
		background-color: #e8f4ff;
	}

	.item-info {
		flex: 1;
		display: flex;
		flex-direction: column;
	}

	.item-name {
		font-size: 15px;
		color: #333;
		font-weight: 500;
		margin-bottom: 4px;
	}

	.item-desc {
		font-size: 12px;
		color: #999;
	}

	/* 加载状态 */
	.loading-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60px 20px;
	}

	.loading-text {
		margin-top: 15px;
		font-size: 14px;
		color: #999;
	}

	/* 空状态 */
	.empty-state {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60px 20px;
	}

	.empty-hint {
		margin-top: 15px;
		font-size: 14px;
		color: #999;
	}
</style>
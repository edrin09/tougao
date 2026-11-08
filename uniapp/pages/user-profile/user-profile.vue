<template>
	<view class="profile-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="个人信息" :autoBack="true" bgColor="#ffffff" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
		</uv-navbar>

		<!-- 主内容区域 -->
		<view class="main-content">
			<!-- 头像设置区域 -->
			<view class="avatar-section">
				<view class="avatar-card">
					<view class="avatar-container" @click="chooseAvatar">
						<image class="user-avatar" :src="userProfile.avatar || '/static/avatar.png'" mode="aspectFill">
						</image>
						<view class="avatar-overlay">
							<uv-icon name="camera" size="24" color="#ffffff"></uv-icon>
							<text class="avatar-text">点击更换</text>
						</view>
					</view>
				</view>
			</view>

			<!-- 个人信息表单 -->
			<view class="form-section">
				<!-- 基本信息卡片 -->
				<view class="info-card">
					<view class="card-title">基本信息</view>

					<!-- 昵称 -->
					<view class="input-group">
						<text class="input-label">昵称</text>
						<uv-input v-model="userProfile.nickname" placeholder="请输入昵称" :maxlength="20" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 手机号 -->
					<view class="input-group">
						<text class="input-label">手机号</text>
						<uv-input v-model="userProfile.phone" placeholder="请输入手机号" type="number" :maxlength="11"
							border="none" :customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 邮箱 -->
					<view class="input-group">
						<text class="input-label">邮箱</text>
						<uv-input v-model="userProfile.email" placeholder="请输入邮箱地址" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 微信号 -->
					<view class="input-group">
						<text class="input-label">微信号</text>
						<uv-input v-model="userProfile.wechat_id" placeholder="请输入微信号" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>
				</view>

				<!-- 学校信息卡片 -->
				<view class="info-card">
					<view class="card-title">学校信息</view>

					<!-- 社群/学校 -->
					<view class="input-group">
						<text class="input-label">学校</text>
						<uv-input v-model="userProfile.school" placeholder="请输入学校名称" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 小区/学院 -->
					<view class="input-group">
						<text class="input-label">学院</text>
						<uv-input v-model="userProfile.college" placeholder="请输入学院名称" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 专业 -->
					<view class="input-group">
						<text class="input-label">专业</text>
						<uv-input v-model="userProfile.major" placeholder="请输入专业名称" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>

					<!-- 班级 -->
					<view class="input-group">
						<text class="input-label">班级</text>
						<uv-input v-model="userProfile.classes" placeholder="请输入班级" border="none"
							:customStyle="{ backgroundColor: 'transparent' }"></uv-input>
					</view>
				</view>

				<!-- 绑定墙信息 -->
				<view class="info-card">
					<view class="card-title">绑定墙信息</view>
					<view class="wall-bind-info">
						<view v-if="userProfile.wall_id" class="bind-wall-item">
							<view class="wall-info">
								<text class="wall-name">{{ currentWallName }}</text>
								<text class="wall-desc">当前绑定墙</text>
							</view>
							<view class="unbind-btn" @click="showWallSelector">
								<text class="unbind-text">选择</text>
							</view>
						</view>
						<view v-else class="no-bind-wall" @click="showWallSelector">
							<uv-icon name="info-circle" size="18" color="#999"></uv-icon>
							<text class="no-bind-text">暂未绑定任何墙</text>
							<text class="add-text">添加</text>
						</view>
					</view>
				</view>

				<!-- 保存按钮 -->
				<view class="save-section">
					<uv-button type="primary" text="保存信息" @click="saveProfile" :loading="isSaving" :disabled="isSaving"
						:customStyle="{
              marginTop: '20px',
              borderRadius: '8px',
              height: '48px'
            }"></uv-button>
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

				<!-- 搜索框 - 优化样式 -->
				<view class="search-box" v-if="!showWallList">
					<uni-search-bar 
						v-model="searchKeyword" 
						placeholder="输入学校名称进行搜索"
						:focus="searchFocus"
						@input="handleSearchInput" 
						@clear="handleSearchClear"
						@blur="searchFocus = false"
						cancelButton="none"
						bgColor="#ffffff"
						radius="10">
					</uni-search-bar>
				</view>

				<!-- 列表区域（学校列表或墙列表共用）- 修复问题3：使用view替代scroll-view -->
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
							<view class="list-title">默认学校墙列表</view>

							<view v-for="wall in currentWallList" :key="wall.id" class="list-item wall-item"
								:class="{ 'item-selected': selectedWallId === wall.id }" @click="selectWall(wall.id)">
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
	import api from "../../api";
	import {
		uploadImage
	} from "../../api/upload";

	export default {
		data() {
			return {
				userProfile: {
					avatar: '',
					nickname: '',
					phone: '',
					email: '',
					wechat_id: '',
					school: '',
					college: '',
					major: '',
					classes: '',
					wall_id: 0,
					community_id: 0
				},
				isSaving: false,
				// 当前用户绑定的墙信息（用于回显）
				currentWallInfo: null,
				// 搜索关键词
				searchKeyword: '',
				// 搜索到的学校列表
				searchedCommunityList: [],
				// 当前选中的学校
				currentSelectedCommunity: null,
				// 当前学校的墙列表
				currentWallList: [],
				// 临时选择的墙ID
				selectedWallId: 0,
				// 是否显示墙列表（false: 显示学校列表, true: 显示墙列表）
				showWallList: false,
				// 是否已经搜索过
				hasSearched: false,
				// 是否正在搜索
				isSearching: false,
				// 是否正在加载墙列表
				isLoadingWalls: false,
				// 搜索防抖定时器
				searchTimer: null,
				// 搜索框焦点状态
				searchFocus: false
			};
		},

		onLoad() {
			this.getUserProfile();
		},

		computed: {
			// 获取当前绑定墙的名称
			currentWallName() {
				// 如果有缓存的墙信息，直接返回
				if (this.currentWallInfo) {
					return this.currentWallInfo.wall_name;
				}

				return '';
			}
		},

		methods: {
			// 从本地存储获取用户信息
			getUserProfile() {
				try {
					const userInfo = uni.getStorageSync('userInfo') || {};
					console.log('从本地存储获取的用户信息:', userInfo);

					// 合并本地存储的数据到用户资料
					this.userProfile = {
						...this.userProfile,
						avatar: userInfo.avatar || '',
						nickname: userInfo.nickname || '',
						phone: userInfo.phone || '',
						email: userInfo.email || '',
						wechat_id: userInfo.wechat_id || '',
						school: userInfo.school || '',
						college: userInfo.college || '',
						major: userInfo.major || '',
						classes: userInfo.classes || '',
						wall_id: userInfo.wall_id || 0,
						community_id: userInfo.community_id || 0
					};

					// 如果用户已绑定墙，获取墙信息用于显示
					if (this.userProfile.wall_id && this.userProfile.community_id) {
						this.getCurrentWallInfo();
					}

					console.log('初始化后的用户资料:', this.userProfile);
				} catch (error) {
					console.error('获取本地用户信息失败:', error);
					uni.showToast({
						title: '获取用户信息失败',
						icon: 'none'
					});
				}
			},

			// 获取当前绑定墙的信息
			async getCurrentWallInfo() {
				try {
					const result = await this.$api.user.getWallList({
						community_id: this.userProfile.community_id
					});

					if (result.code === 200) {
						const walls = result.data.list || [];
						this.currentWallInfo = walls.find(wall => wall.id === this.userProfile.wall_id);
					}
				} catch (error) {
					console.error('获取当前墙信息失败:', error);
				}
			},

			// 显示墙选择器
			showWallSelector() {
				// 重置状态
				this.searchKeyword = '';
				this.searchedCommunityList = [];
				this.currentSelectedCommunity = null;
				this.currentWallList = [];
				this.selectedWallId = 0;
				this.showWallList = false;
				this.hasSearched = false;
				this.isSearching = false;
				this.searchFocus = false; // 重置焦点状态

				// 打开弹窗
				this.$refs.wallPopup.open();
			},

			// 处理搜索输入（实时搜索）- 适配uni-search-bar的input事件
			handleSearchInput(value) {
				// uni-search-bar的input事件直接传递value
				const keyword = typeof value === 'string' ? value : this.searchKeyword;
				
				// 清除之前的定时器
				if (this.searchTimer) {
					clearTimeout(this.searchTimer);
				}

				// 如果输入为空，清空结果
				if (!keyword || !keyword.trim()) {
					this.searchedCommunityList = [];
					this.hasSearched = false;
					return;
				}

				// 设置防抖，500ms后执行搜索
				this.searchTimer = setTimeout(() => {
					this.searchCommunity();
				}, 500);
			},

			// 搜索学校
			async searchCommunity() {
				if (!this.searchKeyword.trim()) {
					return;
				}

				this.isSearching = true;
				this.hasSearched = true;

				try {
					console.log('搜索学校，关键词:', this.searchKeyword);
					const result = await this.$api.user.getCommunityList({
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

			// 清空搜索
			handleSearchClear() {
				this.searchedCommunityList = [];
				this.hasSearched = false;
			},

			// 选择学校
			async selectCommunity(community) {
				console.log('选择学校:', community);
				this.currentSelectedCommunity = community;
				this.selectedWallId = 0;
				this.showWallList = true;

				// 加载该学校的墙列表
				await this.loadWallList(community.community_id);
			},

			// 加载墙列表
			async loadWallList(communityId) {
				this.isLoadingWalls = true;
				try {
					console.log('加载墙列表，学校ID:', communityId);
					const result = await this.$api.user.getWallList({
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

			// 返回学校搜索 - 修复问题1：设置焦点使搜索框可编辑
			backToCommunitySearch() {
				this.showWallList = false;
				this.currentSelectedCommunity = null;
				this.currentWallList = [];
				this.selectedWallId = 0;
				// 修复：延迟设置焦点，确保搜索框渲染完成后可以输入
				this.$nextTick(() => {
					this.searchFocus = true;
				});
			},

			// 选择墙
			selectWall(wallId) {
				this.selectedWallId = wallId;
				console.log('选择墙ID:', wallId);
			},

			// 确认墙选择并调用绑定接口 - 修复问题2：正确赋值并更新墙信息
			async confirmWallSelection() {
				if (!this.selectedWallId || !this.currentSelectedCommunity) {
					uni.showToast({
						title: '请选择一个墙',
						icon: 'none'
					});
					return;
				}

				// 修复：正确赋值community_id（之前赋值了整个对象）
				this.userProfile.community_id = this.currentSelectedCommunity.community_id;
				this.userProfile.wall_id = this.selectedWallId;
				
				// 修复：更新currentWallInfo以便显示墙名称
				this.currentWallInfo = this.currentWallList.find(wall => wall.id === this.selectedWallId);
				
				this.$refs.wallPopup.close();
			},

			// 选择头像
			chooseAvatar() {
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['album', 'camera'],
					success: async (res) => {
						const tempFilePath = res.tempFilePaths[0];

						uni.showLoading({
							title: '上传中...',
							mask: true
						});
						try {
							// 压缩图片
							const compressedPath = await this.compressImage(tempFilePath, 0.8);
							// 上传图片
							const uploadResult = await uploadImage(compressedPath);

							if (uploadResult.code === 200) {
								this.userProfile.avatar = uploadResult.data.url;
								uni.showToast({
									title: '头像上传成功',
									icon: 'success'
								});
							} else {
								uni.showToast({
									title: '头像上传失败',
									icon: 'none'
								});
							}
						} catch (error) {
							console.error('上传头像失败:', error);
							uni.showToast({
								title: '上传失败',
								icon: 'none'
							});
						} finally {
							uni.hideLoading();
						}
					},
					fail: (error) => {
						console.error('选择图片失败:', error);
					}
				});
			},

			// 压缩图片
			compressImage(filePath, quality = 0.8) {
				return new Promise((resolve, reject) => {
					uni.compressImage({
						src: filePath,
						quality: quality,
						success: (res) => {
							resolve(res.tempFilePath);
						},
						fail: (err) => {
							// 压缩失败时返回原图片路径
							resolve(filePath);
						}
					});
				});
			},

			// 保存个人信息
			async saveProfile() {
				// 基本验证
				if (!this.userProfile.nickname.trim()) {
					uni.showToast({
						title: '请输入昵称',
						icon: 'none'
					});
					return;
				}

				// 手机号验证
				if (this.userProfile.phone && !/^1[3-9]\d{9}$/.test(this.userProfile.phone)) {
					uni.showToast({
						title: '请输入正确的手机号',
						icon: 'none'
					});
					return;
				}

				// 邮箱验证
				if (this.userProfile.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.userProfile.email)) {
					uni.showToast({
						title: '请输入正确的邮箱地址',
						icon: 'none'
					});
					return;
				}

				this.isSaving = true;
				try {
					const result = await api.user.updateUserInfo(this.userProfile);

					if (result.code === 200) {
						// 更新本地存储的用户信息
						const userInfo = uni.getStorageSync('userInfo') || {};
						const updatedUserInfo = {
							...userInfo,
							...this.userProfile // 保存所有用户资料信息
						};
						uni.setStorageSync('userInfo', updatedUserInfo);

						uni.showToast({
							title: '保存成功',
							icon: 'success'
						});

						// 发送用户信息更新事件
						uni.$emit('userInfoUpdated', updatedUserInfo);

						// 延迟返回上一页
						setTimeout(() => {
							uni.navigateBack();
						}, 1500);
					} else {
						uni.showToast({
							title: result.message || '保存失败',
							icon: 'none'
						});
					}
				} catch (error) {
					console.error('保存个人信息失败:', error);
					uni.showToast({
						title: '保存失败,请重试',
						icon: 'none'
					});
				} finally {
					this.isSaving = false;
				}
			}
		}
	};
</script>

<style>
	.profile-container {
		min-height: 100vh;
		background-color: #f0f6fb;
	}

	.main-content {
		padding-top: 15px;
		padding-bottom: 30px;
	}

	/* 头像设置区域 */
	.avatar-section {
		margin: 0 15px 15px 15px;
	}

	.avatar-card {
		border-radius: 10px;
		padding: 25px 20px;
		display: flex;
		flex-direction: column;
		align-items: center;
	}

	.avatar-container {
		position: relative;
		width: 80px;
		height: 80px;
		border-radius: 50%;
		overflow: hidden;
	}

	.user-avatar {
		width: 100%;
		height: 100%;
		border-radius: 50%;
		background-color: #f0f0f0;
	}

	.avatar-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background-color: rgba(0, 0, 0, 0.5);
		border-radius: 50%;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		opacity: 0;
		transition: opacity 0.3s ease;
	}

	.avatar-container:active .avatar-overlay {
		opacity: 1;
	}

	.avatar-text {
		color: #ffffff;
		font-size: 12px;
		margin-top: 4px;
	}

	/* 表单区域 */
	.form-section {
		margin: 0 15px;
	}

	.info-card {
		background-color: #fff;
		border-radius: 10px;
		padding: 20px;
		margin-bottom: 15px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
	}

	.card-title {
		font-size: 16px;
		font-weight: 600;
		color: #333;
		margin-bottom: 20px;
		padding-bottom: 10px;
		border-bottom: 1px solid #f0f0f0;
	}

	.input-group {
		margin-bottom: 20px;
		position: relative;
		border-bottom: 1px solid #eee;
		padding-bottom: 5px;
	}

	.input-group:last-child {
		margin-bottom: 0;
	}

	.input-label {
		display: block;
		font-size: 14px;
		color: #666;
		margin-bottom: 8px;
		font-weight: 500;
	}

	/* 绑定墙信息 */
	.wall-bind-info {
		min-height: 60px;
		display: flex;
		align-items: center;
	}

	.bind-wall-item {
		display: flex;
		align-items: center;
		justify-content: space-between;
		width: 100%;
		padding: 15px;
		background-color: #f8f9fa;
		border-radius: 8px;
		border-left: 4px solid #2979ff;
	}

	.wall-info {
		flex: 1;
	}

	.wall-name {
		display: block;
		font-size: 16px;
		color: #333;
		font-weight: 500;
		margin-bottom: 4px;
	}

	.wall-desc {
		font-size: 12px;
		color: #666;
	}

	.unbind-btn {
		padding: 6px 12px;
		background-color: #ff4757;
		border-radius: 4px;
	}

	.unbind-text {
		color: #ffffff;
		font-size: 12px;
	}

	.no-bind-wall {
		display: flex;
		align-items: center;
		justify-content: center;
		padding: 20px;
		color: #999;
	}

	.no-bind-text {
		margin-left: 8px;
		font-size: 14px;
	}

	.add-text {
		font-size: 14px;
		color: #3c9cff;
	}

	/* 保存按钮区域 */
	.save-section {
		margin-top: 20px;
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
	}

	.popup-confirm {
		width: 40px;
		font-size: 16px;
		color: #3c9cff;
		font-weight: 500;
	}

	/* 搜索框 - 优化样式 */
	.search-box {
		padding: 15px 20px;
		background-color: #f8f9fa;
		border-bottom: 1px solid #eee;
	}
	
	/* uni-search-bar 样式优化 */
	.search-box ::v-deep .uni-searchbar {
		background-color: #ffffff !important;
		border: 1px solid #e5e5e5 !important;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06) !important;
		border-radius: 10px !important;
		overflow: hidden;
	}
	
	/* 搜索框输入区域 */
	.search-box ::v-deep .uni-searchbar__box {
		background-color: #ffffff !important;
		border-radius: 10px !important;
	}
	
	/* 搜索框文本 */
	.search-box ::v-deep .uni-searchbar__text-input {
		font-size: 14px !important;
		color: #333333 !important;
	}
	
	/* 搜索框占位符 */
	.search-box ::v-deep .uni-searchbar__text-placeholder {
		font-size: 14px !important;
		color: #999999 !important;
	}
	
	/* 搜索图标 */
	.search-box ::v-deep .uni-searchbar__text-icon {
		color: #999999 !important;
	}
	
	/* 清除按钮 */
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

	/* 修复问题3：使用view实现滚动并隐藏滚动条 */
	.result-scroll-view {
		flex: 1;
		height: 100%;
		padding: 10px 20px 20px;
		box-sizing: border-box;
		overflow-y: auto;
		overflow-x: hidden;
	}

	/* 隐藏滚动条 - 微信小程序兼容方案 */
	.result-scroll-view::-webkit-scrollbar {
		display: none !important;
		width: 0 !important;
		height: 0 !important;
		background: transparent !important;
		-webkit-appearance: none !important;
	}
	
	/* 其他浏览器兼容 */
	.result-scroll-view {
		scrollbar-width: none; /* Firefox */
		-ms-overflow-style: none; /* IE 10+ */
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
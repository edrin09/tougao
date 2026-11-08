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

		<!-- 墙选择器 - 二级联动选择器 -->
		<uv-picker ref="wallPicker" :columns="pickerColumns" keyName="label" @confirm="handleWallConfirm"
			@cancel="handleWallCancel" @change="handleColumnChange" :defaultIndex="defaultPickerIndex" title="选择绑定墙">
		</uv-picker>
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
				// 学校列表
				communityList: [],
				// 墙列表 - 按学校ID分组的缓存
				wallListByCommunity: {},
				// 二级联动选择器的数据
				pickerColumns: [[], []],
				// 当前选择的索引
				defaultPickerIndex: [0, 0],
				// 当前用户绑定的墙信息（用于回显）
				currentWallInfo: null,
				// 是否已初始化
				isInitialized: false
			};
		},

		onLoad() {
			this.getUserProfile();
			this.initData();
		},

		computed: {
			// 获取当前绑定墙的名称
			currentWallName() {
				// 如果有缓存的墙信息，直接返回
				if (this.currentWallInfo) {
					return this.currentWallInfo.wall_name;
				}

				// 从本地缓存中查找
				if (!this.userProfile.wall_id || !this.userProfile.community_id) {
					return '';
				}

				const communityWalls = this.wallListByCommunity[this.userProfile.community_id];
				if (!communityWalls) {
					return '';
				}

				const wall = communityWalls.find(wall => wall.id === this.userProfile.wall_id);
				return wall ? wall.wall_name : '';
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

					console.log('初始化后的用户资料:', this.userProfile);
				} catch (error) {
					console.error('获取本地用户信息失败:', error);
					uni.showToast({
						title: '获取用户信息失败',
						icon: 'none'
					});
				}
			},

			// 初始化数据
			async initData() {
				try {
					await this.getCommunityList();
					await this.buildPickerData();
					this.isInitialized = true;
				} catch (error) {
					console.error('初始化数据失败:', error);
					uni.showToast({
						title: '加载数据失败',
						icon: 'none'
					});
				}
			},

			// 获取学校列表
			async getCommunityList() {
				try {
					const result = await this.$api.user.getCommunityList();
					if (result.code === 200) {
						this.communityList = result.data.list || [];
						console.log('获取学校列表:', this.communityList);
					}
				} catch (error) {
					console.error('获取学校列表失败:', error);
				}
			},

			// 为指定学校加载墙列表
			async loadWallListForCommunity(communityId) {
				if (!communityId || this.wallListByCommunity[communityId]) {
					return; // 如果已有缓存，直接返回
				}
				
				try {
					console.log('加载学校墙列表，学校ID:', communityId);
					const result = await this.$api.user.getWallList({
						community_id: communityId
					});

					if (result.code === 200) {
						this.wallListByCommunity[communityId] = result.data.list || [];
						console.log(`学校${communityId}的墙列表:`, this.wallListByCommunity[communityId]);
					} else {
						this.wallListByCommunity[communityId] = [];
					}
				} catch (error) {
					console.error(`获取学校${communityId}的墙列表失败:`, error);
					this.wallListByCommunity[communityId] = [];
				}
			},

			// 构建选择器数据和设置默认选择
			async buildPickerData() {
				if (this.communityList.length === 0) {
					return;
				}

				// 构建第一列：学校列表
				const firstColumn = this.communityList.map(community => ({
					label: community.community_name,
					value: community.community_id,
				}));
				this.pickerColumns[0] = firstColumn;

				// 确定默认选中的学校
				let selectedCommunityIndex = 0;
				let selectedCommunityId = this.communityList[0].community_id;

				// 如果用户已绑定学校，找到对应的索引
				if (this.userProfile.community_id) {
					const communityIndex = this.communityList.findIndex(
						community => community.community_id === this.userProfile.community_id
					);
					if (communityIndex !== -1) {
						selectedCommunityIndex = communityIndex;
						selectedCommunityId = this.userProfile.community_id;
					}
				}

				// 加载对应学校的墙列表
				await this.loadWallListForCommunity(selectedCommunityId);

				// 构建第二列：墙列表
				const secondColumn = (this.wallListByCommunity[selectedCommunityId] || []).map(wall => ({
					label: wall.wall_name,
					value: wall.id,
				}));
				this.pickerColumns[1] = secondColumn;

				// 设置默认选择的墙索引
				let selectedWallIndex = 0;
				if (this.userProfile.wall_id && secondColumn.length > 0) {
					const wallIndex = secondColumn.findIndex(wall => wall.value === this.userProfile.wall_id);
					if (wallIndex !== -1) {
						selectedWallIndex = wallIndex;
						// 设置当前墙信息用于显示
						this.currentWallInfo = this.wallListByCommunity[selectedCommunityId].find(
							wall => wall.id === this.userProfile.wall_id
						);
					}
				}

				// 设置默认选择索引
				this.defaultPickerIndex = [selectedCommunityIndex, selectedWallIndex];
				
				console.log('构建完成的选择器数据:', {
					pickerColumns: this.pickerColumns,
					defaultPickerIndex: this.defaultPickerIndex,
					currentWallInfo: this.currentWallInfo
				});
			},

			// 显示墙选择器
			async showWallSelector() {
				if (!this.isInitialized || this.communityList.length === 0) {
					uni.showToast({
						title: '数据加载中，请稍候',
						icon: 'none'
					});
					return;
				}

				if (this.pickerColumns[0].length === 0) {
					uni.showToast({
						title: '暂无可选择的学校',
						icon: 'none'
					});
					return;
				}

				this.$refs.wallPicker.open();
			},

			// 处理列变化事件（用户选择了不同的学校）
			async handleColumnChange(e) {
				console.log('列变化事件:', e);
				const { columnIndex, index, indexs, value, values } = e;

				// 如果是第一列（学校）发生变化
				if (columnIndex === 0) {
					// 从value数组中获取当前选中的学校ID
					const selectedCommunityId = value && value[0] && value[0].value;
					console.log('选择的学校ID:', selectedCommunityId);

					if (!selectedCommunityId) {
						console.error('无法获取选中的学校ID');
						return;
					}

					// 显示加载状态
					uni.showLoading({
						title: '加载墙列表...',
						mask: true
					});

					try {
						// 加载对应学校的墙列表
						await this.loadWallListForCommunity(selectedCommunityId);
						
						// 更新第二列数据
						const secondColumn = (this.wallListByCommunity[selectedCommunityId] || []).map(wall => ({
							label: wall.wall_name,
							value: wall.id,
						}));
						
						// 使用Vue.set或this.$set来确保响应式更新
						this.$set(this.pickerColumns, 1, secondColumn);
						
						console.log('更新后的第二列数据:', secondColumn);
						console.log('更新后的pickerColumns:', this.pickerColumns);
					} catch (error) {
						console.error('加载墙列表失败:', error);
						this.$set(this.pickerColumns, 1, []);
					} finally {
						uni.hideLoading();
					}
				}
			},

			// 处理墙选择确认
			handleWallConfirm(e) {
				console.log('选择结果:', e);

				const { indexs, value } = e;

				if (indexs && indexs.length >= 2 && value && value.length >= 2) {
					// 获取选择的学校和墙信息
					const selectedCommunityId = value[0] && value[0].value;
					const selectedWallId = value[1] && value[1].value;

					console.log('选择的学校ID:', selectedCommunityId);
					console.log('选择的墙ID:', selectedWallId);

					if (!selectedCommunityId || !selectedWallId) {
						uni.showToast({
							title: '选择数据异常，请重试',
							icon: 'none'
						});
						return;
					}

					// 更新用户资料
					this.userProfile.community_id = selectedCommunityId;
					this.userProfile.wall_id = selectedWallId;

					// 更新当前墙信息缓存，用于显示
					const communityWalls = this.wallListByCommunity[selectedCommunityId];
					if (communityWalls) {
						this.currentWallInfo = communityWalls.find(wall => wall.id === selectedWallId);
						console.log('更新当前墙信息:', this.currentWallInfo);
					}

					uni.showToast({
						title: '选择成功',
						icon: 'success'
					});
				} else {
					console.error('选择数据结构异常:', { indexs, value });
					uni.showToast({
						title: '选择失败，请重试',
						icon: 'none'
					});
				}
			},

			// 处理墙选择取消
			handleWallCancel() {
				console.log('取消选择墙');
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
						title: '保存失败，请重试',
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

	.avatar-title {
		font-size: 16px;
		font-weight: 600;
		color: #333;
		margin-bottom: 20px;
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
</style>
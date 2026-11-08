<template>
	<view class="submission-container" :style="{ paddingTop: statusBarHeight + 'px' }">
		<!-- 顶部导航栏 -->
		<uv-navbar title="投稿审核" :autoBack="true" bgColor="transparent" :border="false"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
		</uv-navbar>

		<!-- Tab栏 -->
		<view class="tab-bar" :style="{ top: statusBarHeight + 44 + 'px' }">
			<view v-for="(tab, index) in tabs" :key="index" class="tab-item"
				:class="{ 'tab-active': currentTab === index }" @click="switchTab(index)">
				<text>{{ tab }}</text>
			</view>
		</view>

		<!-- 日期筛选栏 -->
		<view class="filter-bar" :style="{ top: filterBarTop +10 + 'px' }">
			<view class="date-range-container" v-if="currentTab!==0">
				<uni-datetime-picker ref="dateRangePicker" type="daterange" :value="dateRange" placeholder="选择日期范围"
					start-placeholder="开始日期" end-placeholder="结束日期" range-separator="至" :border="false"
					:clearIcon="true" @change="onDateRangeChange" @clear="clearDateFilter">
					<view class="date-range-input">
						<uv-icon name="calendar" size="16" color="#666"></uv-icon>
						<text class="date-range-text">{{ dateRangeText }}</text>
					</view>
				</uni-datetime-picker>
			</view>
			<!-- 统计信息 -->
			<view class="stats-info">
				<text class="stats-text">共 {{ totalCount }} 条</text>
			</view>
		</view>

		<!-- 投稿列表 -->
		<scroll-view class="submission-list" scroll-y="true" @scrolltolower="loadMore" @refresherrefresh="onRefresh"
			:refresher-enabled="true" :refresher-triggered="refresherTriggered" :style="{ 
        height: submissionListHeight + 'px',
        top: submissionListTop + 'px'
      }">
			<!-- 每一篇投稿 -->
			<view v-for="(item, index) in currentList" :key="index" class="submission-item">
				<!-- 顶部信息 -->
				<view class="submission-header">
					<image class="author-avatar" :src="item.avatar || '/static/avatar.png'"></image>
					<view class="submission-info">
						<view class="submission-nickname">{{ item.nickname }}</view>
						<view class="copy-btn" v-if="item.wechat_id" @click="copyChannelAccount(item.wechat_id)">
							微信号：{{item.wechat_id}}<image style="width: 20px;height: 20px;" src="/static/copy.png" mode=""></image>
						</view>
						<view class="submission-time">{{ item.create_time }}</view>
					</view>
				</view>

				<view style="display: flex; align-items: center; margin-bottom: 10px">
					<!-- 标签 -->
					<view class="tag-container" v-if="item.tag">
						<text class="tag-text">#{{ item.tag }}</text>
					</view>
					<!-- 标题 -->
					<view class="submission-title" style="margin-left: 10px">{{item.title }}</view>
				</view>

        <view v-if="item.is_single" style="margin-bottom: 10px">
          <span style="color: #1a73e8">（单）</span>{{item.channels_name}}：{{item.channel_account}}
        </view>

				<!-- 内容 -->
				<view class="submission-content">{{ item.content }}</view>

				<!-- 联系方式 -->
				<view class="contact-info" v-if="item.contact">联系方式: {{ item.contact }}</view>

				<!-- 图片 -->
				<view class="image-container" v-if="item.images && item.images.length > 0">
					<image v-for="(img, imgIndex) in item.images" :key="imgIndex" :src="img" class="submission-image"
						mode="aspectFill" @click="previewImage(item.images, imgIndex)"></image>
				</view>

				<!-- 操作按钮 -->
				<view class="action-buttons">
					<view class="action-button-left">
						<view v-if="item.is_single">
							<text class="unit">￥</text>{{item.channel_fans_costs}}
						</view>
					</view>
					<view class="action-button-right">
            <view v-if="item.app_status===0" style="color: red;font-size: 12px;padding: 6px;">
              投稿失败
            </view>
						<!-- 预览按钮 - 所有状态都显示 -->
						<view class="action-btn preview-btn" @click="previewSubmission(item)">
							预览
						</view>

						<!-- 待审核状态：退稿 + 发布 -->
						<template v-if="currentTab === 0">
							<view class="action-btn reject-btn" @click="rejectSubmission(item)">
								退稿
							</view>
							<view class="action-btn approve-btn" @click="approveSubmission(item)">
								发布
							</view>
						</template>

						<!-- 未通过状态：转审核 -->
						<template v-if="currentTab === 1">
							<view class="action-btn approve-btn" @click="moveToReview(item)">
								转审核
							</view>
						</template>

						<!-- 已审核状态：发布 -->
						<template v-if="currentTab === 2">
							<view class="action-btn approve-btn" @click="approveSubmission(item)">
								发布
							</view>
						</template>
					</view>	
				</view>

				<!-- 选择框 -->
				<view class="checkbox" @click.stop="toggleSelection(item)">
					<view class="checkbox-inner" :class="{ 'checkbox-selected': item.selected }"></view>
				</view>
			</view>

			<!-- 加载更多 -->
			<view class="load-more" v-if="hasMoreData">加载更多...</view>
			<view class="no-more" v-else-if="currentList.length > 0">没有更多数据了</view>
			<view class="empty-list" v-else>
				<uv-icon name="list" size="50" color="#dddddd"></uv-icon>
				<text>暂无数据</text>
			</view>
		</scroll-view>

		<!-- 底部操作栏 - 在待审核和已审核状态显示 -->
		<view class="bottom-bar" v-if="currentTab === 0 || currentTab === 1 || currentTab === 2">
			<view class="selection-info">
				<view class="select-all" @click="toggleSelectAll">
					<view class="checkbox-small" :class="{ 'checkbox-selected': isAllSelected }"></view>
					<text>全选</text>
				</view>
				<text>当前已选中 {{ selectedCount }} 条</text>
			</view>
			<view class="batch-actions" v-if="selectedCount > 0">

				<view class="batch-action-btn reject-btn" @click="batchReject" v-if="currentTab === 0">
					退稿
				</view>
				<view class="batch-action-btn publish-btn" @click="batchPublish"
					v-if="currentTab === 0 || currentTab === 2">
					发布
				</view>
				<!-- 批量转审核按钮 -->
				<view class="batch-action-btn publish-btn" @click="batchMoveToReview" v-if="currentTab === 1">
					转审核
				</view>
			</view>
		</view>

		<!-- 发布成功弹层 -->
		<PublishSuccessModal :visible="showSuccessModal" :publishedPosts="successPosts" :count="successCount"
			:shareCommand="shareCommand" @save-and-copy="handleSaveAndCopy" @close="closeSuccessModal" />

		<!-- 评论区链接弹层 -->
		<CommentAreaLinkModal :visible="showCommentAreaModal" @close="closeCommentAreaModal" :commentUrl="commentUrl" />
	</view>
</template>

<script>
	import api from "../../api";
	import PublishSuccessModal from "../../components/PublishSuccessModal.vue";
	import CommentAreaLinkModal from "../../components/CommentAreaLinkModal.vue";

	export default {
		components: {
			PublishSuccessModal,
			CommentAreaLinkModal,
		},
		data() {
			return {
				tabs: ["待审核", "未通过", "已审核"],
				currentTab: 0,
				commentUrl: "",
				pendingList: [],
				rejectedList: [],
				approvedList: [],
				hasMoreData: true,
				page: 1,
				pageSize: 9,
				isAllSelected: false,
				loading: false,
				// 发布成功弹层相关
				showSuccessModal: false,
				successPosts: [],
				successCount: 0,
				shareCommand: "",
				// 评论区链接弹层相关
				showCommentAreaModal: false,
				scrollViewHeight: 0,
				statusBarHeight: 0,
				refresherTriggered: false,
				dateRange: [],
				dateRangeText: "选择日期范围",
				hasDateFilter: false,
				totalCount: 0,
			};
		},
		computed: {
			currentList() {
				switch (this.currentTab) {
					case 0:
						return this.pendingList;
					case 1:
						return this.rejectedList;
					case 2:
						return this.approvedList;
					default:
						return [];
				}
			},
			selectedCount() {
				return this.currentList.filter((item) => item.selected).length;
			},
			// 动态计算筛选栏的top值
			filterBarTop() {
				return this.statusBarHeight + 44 + 44 + 10; // 状态栏 + 导航栏 + Tab栏 + 间距
			},
			// 动态计算投稿列表的top值
			submissionListTop() {
				return this.statusBarHeight + 44 + 44 + 44 + 20; // 状态栏 + 导航栏 + Tab栏 + 筛选栏 + 间距
			},
			// 动态计算投稿列表的高度
			submissionListHeight() {
				const navBarHeight = 44; // 导航栏高度
				const tabBarHeight = 44; // Tab栏高度
				const filterBarHeight = 44; // 筛选栏高度
				const bottomBarHeight = 60; // 底部操作栏高度
				const spacing = 30; // 各种间距

				const totalUsedHeight = this.statusBarHeight + navBarHeight + tabBarHeight + filterBarHeight +
					bottomBarHeight + spacing;
				return uni.getSystemInfoSync().windowHeight - totalUsedHeight;
			}
		},
		onLoad() {
			// 初始化数据
			this.initDateRange();
			this.loadSubmissionData();
		},
		onReady() {
			// 获取系统信息
			const systemInfo = uni.getSystemInfoSync();
			// 获取状态栏高度
			this.statusBarHeight = systemInfo.statusBarHeight;
			// 计算scroll-view高度会在computed中动态计算
		},
		methods: {
			// 获取墙主待审核投稿列表
			async getWallOwnerPendingPosts() {
				const userInfo = uni.getStorageSync("userInfo");
				const res = await api.post.getWallOwnerPendingPosts({
					page: this.page,
					size: this.pageSize,
					wallOwnerId: userInfo.userId,
				});
			},

			// 获取墙主未通过投稿列表
			async getWallOwnerRejectedPosts() {
				const userInfo = uni.getStorageSync("userInfo");
				const res = await api.post.getWallOwnerRejectedPosts({
					page: this.page,
					size: this.pageSize,
					wallOwnerId: userInfo.userId,
				});
			},

			// 获取墙主已通过投稿列表
			async getWallOwnerApprovedPosts() {
				const userInfo = uni.getStorageSync("userInfo");
				const res = await api.post.getWallOwnerApprovedPosts({
					page: this.page,
					size: this.pageSize,
					wallOwnerId: userInfo.userId,
				});
			},

			// 切换标签
			switchTab(index) {
				// 若切换到相同标签则不做处理
				if (this.currentTab === index) return;

				this.currentTab = index;
				this.isAllSelected = false;

				// 重置分页参数
				this.page = 1;
				this.hasMoreData = true;

				// 清空当前标签数据，避免显示旧数据
				switch (this.currentTab) {
					case 0:
						this.pendingList = [];
						break;
					case 1:
						this.rejectedList = [];
						break;
					case 2:
						this.approvedList = [];
						break;
				}

				// 重新加载数据
				this.loadSubmissionData();
			},

			// 获取当前列表数据
			getCurrentListData() {
				return this.currentList;
			},

			// 加载投稿数据
			async loadSubmissionData() {
				if (this.loading) return;

				try {
					this.loading = true;
					uni.showLoading({
						title: "加载中..."
					});

					let res;
					const params = {
						page: this.page,
						size: this.pageSize,
						wallOwnerId: uni.getStorageSync("userInfo").userId,
						// 添加时间戳强制刷新
						_t: Date.now()
					};


					// 添加日期筛选参数
					if (this.currentTab != 0 && this.hasDateFilter && this.dateRange.length === 2) {
						params.startDate = this.dateRange[0];
						params.endDate = this.dateRange[1];
					}


					switch (this.currentTab) {
						case 0:
							res = await api.post.getWallOwnerPendingPosts(params);
							break;
						case 1:
							res = await api.post.getWallOwnerRejectedPosts(params);
							break;
						case 2:
							res = await api.post.getWallOwnerApprovedPosts(params);
							break;
					}

					const list = res.data.list || [];
					const total = res.data.total || list.length;

					// 为每个项目添加选择状态
					list.forEach(item => {
						item.selected = false;
					});

					// 根据当前标签更新对应列表
					switch (this.currentTab) {
						case 0:
							this.pendingList = this.page === 1 ? list : [...this.pendingList, ...list];
							break;
						case 1:
							this.rejectedList = this.page === 1 ? list : [...this.rejectedList, ...list];
							break;
						case 2:
							this.approvedList = this.page === 1 ? list : [...this.approvedList, ...list];
							break;
					}

					// 更新总数和分页状态
					this.totalCount = this.page === 1 ? total : this.totalCount;
					this.hasMoreData = list.length === this.pageSize;

				} catch (error) {
					console.error("加载投稿数据失败:", error);
					uni.showToast({
						title: "加载失败",
						icon: "none"
					});
				} finally {
					this.loading = false;
					this.refresherTriggered = false;
					uni.hideLoading();
				}
			},

			// 加载更多
			loadMore() {
				if (this.hasMoreData) {
					this.page++;
					this.loadSubmissionData();
				}
			},

			// 预览图片
			previewImage(images, index) {
				const urls = images;
				uni.previewImage({
					urls: urls,
					current: urls[index],
				});
			},

			// 预览投稿
			previewSubmission(item) {
				// 如果有海报图片，直接预览海报
				if (item.post_image || item.posterUrl) {
					const posterUrl = item.post_image || item.posterUrl;
					uni.previewImage({
						urls: [posterUrl],
						current: posterUrl,
					});
					return;
				}

				// 如果没有海报，跳转到预览页面生成
				const postData = {
					title: item.title,
					content: item.content,
					tag: item.tag,
					images: item.images,
				};

				uni.navigateTo({
					url: `/pages/preview/preview?postData=${encodeURIComponent(
          JSON.stringify(postData)
        )}`,
				});
			},

			// 退稿
			async rejectSubmission(item) {
				uni.showModal({
					title: "退稿确认",
					content: "确定要退回这篇投稿吗？",
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "处理中...",
								});

								await api.post.reviewPost({
									postId: item.post_id || item.id,
									status: -1,
									comment: "投稿未通过审核",
								});

								// 从待审核列表中移除
								const index = this.pendingList.findIndex(
									(post) =>
									(post.post_id || post.id) === (item.post_id || item.id)
								);
								if (index !== -1) {
									this.pendingList.splice(index, 1);
								}

								uni.showToast({
									title: "已退稿",
									icon: "success",
								});
							} catch (error) {
								console.error("退稿失败:", error);
								uni.showToast({
									title: "退稿失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},
			// 复制渠道账号
			async copyChannelAccount(account) {
				if (!account) {
					uni.showToast({
						title: '账号为空，无法复制',
						icon: 'none',
						duration: 1500
					});
					return;
				}
				try {
					// 使用uni-app的复制到剪贴板API
					await uni.setClipboardData({
						data: account,
						success: () => {
							// 显示复制成功提示
							uni.showToast({
								title: `微信号已复制`,
								icon: 'success',
								duration: 2000
							});
						},
						fail: (error) => {
							console.error('复制失败:', error);
							uni.showToast({
								title: '复制失败',
								icon: 'none',
								duration: 2000
							});
						}
					});
				} catch (error) {
					console.error('复制操作出错:', error);
					// 显示错误提示
					uni.showToast({
						title: '复制功能暂不可用',
						icon: 'none',
						duration: 1500
					});
				}
			},
			// 切换审核状态
			async toggleApproval(item) {
				if (this.currentTab === 0) {
					// 通过投稿
					uni.showModal({
						title: "审核确认",
						content: "确定通过这篇投稿吗？",
						confirmText: "确定",
						cancelText: "取消",
						success: async (res) => {
							if (res.confirm) {
								try {
									uni.showLoading({
										title: "处理中...",
									});

									await api.post.reviewPost({
										postId: item.post_id || item.id,
										status: 1,
										comment: "投稿审核通过",
									});

									// 从待审核列表中移除
									const index = this.pendingList.findIndex(
										(post) =>
										(post.post_id || post.id) === (item.post_id || item.id)
									);
									if (index !== -1) {
										this.pendingList.splice(index, 1);
									}

									uni.showToast({
										title: "已通过",
										icon: "success",
									});
								} catch (error) {
									console.error("审核失败:", error);
									uni.showToast({
										title: "审核失败",
										icon: "none",
									});
								} finally {
									uni.hideLoading();
								}
							}
						},
					});
				} else if (this.currentTab === 2) {
					// 撤销审核
					uni.showModal({
						title: "撤销确认",
						content: "确定撤销这篇已通过的投稿吗？",
						confirmText: "确定",
						cancelText: "取消",
						success: async (res) => {
							if (res.confirm) {
								try {
									uni.showLoading({
										title: "处理中...",
									});

									await api.post.reviewPost({
										postId: item.post_id || item.id,
										status: 0,
										comment: "撤销审核",
									});

									// 从已审核列表中移除
									const index = this.approvedList.findIndex(
										(post) =>
										(post.post_id || post.id) === (item.post_id || item.id)
									);
									if (index !== -1) {
										this.approvedList.splice(index, 1);
									}

									uni.showToast({
										title: "已撤销",
										icon: "success",
									});
								} catch (error) {
									console.error("撤销失败:", error);
									uni.showToast({
										title: "撤销失败",
										icon: "none",
									});
								} finally {
									uni.hideLoading();
								}
							}
						},
					});
				}
			},

			// 发布投稿（审核通过）
			async approveSubmission(item) {
				uni.showModal({
					title: "发布确认",
					content: "确定发布这篇投稿吗？",
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "处理中...",
								});

								const result = await api.post.reviewPost({
									postId: item.post_id || item.id,
									status: 1,
									comment: "投稿审核通过",
								});

								// 从待审核列表中移除
								const index = this.pendingList.findIndex(
									(post) =>
									(post.post_id || post.id) === (item.post_id || item.id)
								);
								if (index !== -1) {
									this.pendingList.splice(index, 1);
								}
								// 发布成功会有评论区短链返回
								console.log(res, 'res.data')
								const commentUrl = result.data.short_link;
								console.log(commentUrl, 'commentUrl')
								this.commentUrl = commentUrl;
								// 显示发布成功弹层
								await this.showPublishSuccessModal(1, [item]);
							} catch (error) {
								console.error("发布失败:", error);
								uni.showToast({
									title: "发布失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},

			// 转审核（将未通过的投稿重新转为待审核）
			async moveToReview(item) {
				uni.showModal({
					title: "转审核确认",
					content: "确定将这篇投稿重新转为待审核状态吗？",
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "处理中...",
								});

								await api.post.moveToReview({
									postId: item.post_id || item.id,
									comment: "重新审核",
								});

								// 从未通过列表中移除
								const index = this.rejectedList.findIndex(
									(post) =>
									(post.post_id || post.id) === (item.post_id || item.id)
								);
								if (index !== -1) {
									this.rejectedList.splice(index, 1);
								}

								uni.showToast({
									title: "已转为待审核",
									icon: "success",
								});
							} catch (error) {
								console.error("转审核失败:", error);
								uni.showToast({
									title: "操作失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},

			// 切换选择状态
			toggleSelection(item) {
				item.selected = !item.selected;
				this.checkAllSelected();
			},

			// 检查是否全选
			checkAllSelected() {
				this.isAllSelected =
					this.currentList.length > 0 &&
					this.currentList.every((item) => item.selected);
			},

			// 切换全选状态
			toggleSelectAll() {
				this.isAllSelected = !this.isAllSelected;
				this.currentList.forEach((item) => {
					item.selected = this.isAllSelected;
				});
			},

			// 批量发布
			async batchPublish() {
				const selectedItems = this.currentList.filter((item) => item.selected);
				if (selectedItems.length === 0) return;

				uni.showModal({
					title: "批量发布",
					content: `确定批量发布选中的 ${selectedItems.length} 篇投稿吗？`,
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "发布中...",
								});

								// 批量发布
								const result = await api.post.batchReviewPosts({
									postIds: selectedItems.map((item) => item.post_id || item.id),
									status: 1,
									comment: "批量发布",
								});

								// 从待审核列表中移除选中项
								selectedItems.forEach((item) => {
									const index = this.pendingList.findIndex(
										(post) =>
										(post.post_id || post.id) === (item.post_id || item.id)
									);
									if (index !== -1) {
										this.pendingList.splice(index, 1);
									}
								});

								// 更新全选状态
								this.isAllSelected = false;
								console.log(res, 'res.data')

								const commentUrl = result.data.short_link;
								console.log(commentUrl, 'commentUrl')
								this.commentUrl = commentUrl;

								// 显示发布成功弹层
								await this.showPublishSuccessModal(
									selectedItems.length,
									selectedItems
								);
							} catch (error) {
								console.error("批量发布失败:", error);
								uni.showToast({
									title: "批量发布失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},

			// 批量退稿
			async batchReject() {
				const selectedItems = this.currentList.filter((item) => item.selected);
				if (selectedItems.length === 0) return;

				uni.showModal({
					title: "批量退稿",
					content: `确定批量退稿选中的 ${selectedItems.length} 篇投稿吗？`,
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "处理中...",
								});

								// 批量退稿
								await api.post.batchReviewPosts({
									postIds: selectedItems.map((item) => item.post_id || item.id),
									status: -1,
									comment: "批量退稿",
								});

								// 从待审核列表中移除选中项
								selectedItems.forEach((item) => {
									const index = this.pendingList.findIndex(
										(post) =>
										(post.post_id || post.id) === (item.post_id || item.id)
									);
									if (index !== -1) {
										this.pendingList.splice(index, 1);
									}
								});

								// 更新全选状态
								this.isAllSelected = false;

								uni.showToast({
									title: "批量退稿成功",
									icon: "success",
								});
							} catch (error) {
								console.error("批量退稿失败:", error);
								uni.showToast({
									title: "批量退稿失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},

			// 显示发布成功弹层
			async showPublishSuccessModal(count, publishedPosts = []) {
				this.successCount = count;
				this.successPosts = publishedPosts;
				this.shareCommand = await this.generateShareCommand(
					count,
					publishedPosts
				);
				this.showSuccessModal = true;
			},

			// 关闭发布成功弹层
			closeSuccessModal() {
				this.showSuccessModal = false;
				this.successPosts = [];
				this.successCount = 0;
				this.shareCommand = "";
			},

			// 关闭评论区链接弹层
			closeCommentAreaModal() {
				this.showCommentAreaModal = false;
			},

			// 处理保存和复制操作
			async handleSaveAndCopy(data) {
				try {
					await this.savePostersAndCopyCommand(
						data.posterUrls,
						data.count,
						this.successPosts
					);
					this.closeSuccessModal();
				} catch (error) {
					console.error("保存图片失败:", error);
					uni.showToast({
						title: "保存失败",
						icon: "none",
					});
				}
			},

			// 保存海报到相册并复制口令
			async savePostersAndCopyCommand(posterUrls, count, publishedPosts = []) {
				try {
					// 首先检查相册权限
					const authResult = await this.checkPhotoAlbumAuth();
					if (!authResult) {
						// 权限被拒绝，只复制口令
						await this.copyCommandOnly(count, publishedPosts);
						return;
					}

					uni.showLoading({
						title: "保存中...",
					});

					// 1. 保存图片到相册
					let savedCount = 0;
					for (const url of posterUrls) {
						try {
							// 下载图片到本地
							const downloadResult = await uni.downloadFile({
								url: url,
							});

							if (downloadResult.statusCode === 200) {
								// 保存到相册
								await uni.saveImageToPhotosAlbum({
									filePath: downloadResult.tempFilePath,
								});
								savedCount++;
							}
						} catch (error) {
							console.error("保存单张图片失败:", error);
							// 继续保存其他图片
						}
					}

					// 2. 生成并复制口令
					const command = await this.generateShareCommand(count, publishedPosts);
					await uni.setClipboardData({
						data: command,
					});

					uni.hideLoading();

					// 3. 显示成功提示，然后显示评论区链接弹窗
					// uni.showModal({
					//   title: "操作完成",
					//   content: `已保存 ${savedCount} 张图片到相册\n分享口令已复制到剪贴板`,
					//   showCancel: false,
					//   confirmText: "知道了",
					//   success: () => {
					//     // 显示评论区链接弹窗
					//     this.showCommentAreaModal = true;
					//   },
					// });
					uni.showToast({
						title: `已保存 ${savedCount} 张图片到相册\n分享口令已复制到剪贴板`,
						icon: "none",
						duration: 3000,
					});
					this.showCommentAreaModal = true;
				} catch (error) {
					uni.hideLoading();
					console.error("保存图片或复制口令失败:", error);

					// 如果保存失败，至少尝试复制口令
					try {
						const command = await this.generateShareCommand(
							count,
							publishedPosts
						);
						await uni.setClipboardData({
							data: command,
						});

						uni.showToast({
							title: "口令已复制，图片保存失败",
							icon: "none",
							duration: 3000,
						});
					} catch (copyError) {
						uni.showToast({
							title: "保存失败，请手动操作",
							icon: "none",
						});
					}
				}
			},

			// 检查相册权限
			async checkPhotoAlbumAuth() {
				try {
					// 检查是否已有权限
					const authSetting = await uni.getSetting();
					if (authSetting.authSetting["scope.writePhotosAlbum"] === false) {
						// 权限被拒绝，引导用户去设置
						const modalResult = await uni.showModal({
							title: "需要相册权限",
							content: "保存图片需要访问您的相册，请在设置中开启权限",
							confirmText: "去设置",
							cancelText: "取消",
						});

						if (modalResult.confirm) {
							await uni.openSetting();
						}
						return false;
					}

					if (authSetting.authSetting["scope.writePhotosAlbum"] === undefined) {
						// 还未授权，请求权限
						try {
							await uni.authorize({
								scope: "scope.writePhotosAlbum",
							});
							return true;
						} catch (error) {
							return false;
						}
					}

					return true;
				} catch (error) {
					console.error("检查相册权限失败:", error);
					return false;
				}
			},

			// 只复制口令
			async copyCommandOnly(count, publishedPosts = []) {
				try {
					const command = await this.generateShareCommand(count, publishedPosts);
					await uni.setClipboardData({
						data: command,
					});

					// uni.showModal({
					//   title: "口令已复制",
					//   content: "分享口令已复制到剪贴板\n图片请手动保存",
					//   showCancel: false,
					//   confirmText: "知道了",
					//   success: () => {
					//     // 显示评论区链接弹窗
					//     this.showCommentAreaModal = true;
					//   },
					// });

					uni.showToast({
						title: '分享口令已复制到剪贴板\n图片请手动保存',
						icon: "none",
					})
					this.showCommentAreaModal = true;
				} catch (error) {
					console.error("复制口令失败:", error);
					uni.showToast({
						title: "复制失败",
						icon: "none",
					});
				}
			},

			// 生成分享口令
			async generateShareCommand(count, publishedPosts = []) {
				try {
					const userInfo = uni.getStorageSync("userInfo") || {};

					if (!userInfo.userId) {
						return "👉投稿已发布，快来查看吧~";
					}

					// 收集所有标签
					let tags = [];
					if (publishedPosts && publishedPosts.length > 0) {
						// 提取所有标签并去重
						publishedPosts.forEach(post => {
							if (post.tag && post.tag.trim()) {
								tags.push(post.tag.trim());
							}
						});
						// 去重
						// tags = [...new Set(tags)];
					}

					// 格式化标签，每三个一行，前面加太阳表情
					let tagsContent = "";
					if (tags.length > 0) {
						let formattedTags = [];
						for (let i = 0; i < tags.length; i++) {
							formattedTags.push(`☀️${tags[i]}`);

							// 每三个标签换一行，或者是最后一个标签
							if ((i + 1) % 3 === 0 || i === tags.length - 1) {
								tagsContent += formattedTags.join(" ") + "\n";
								formattedTags = [];
							}
						}
					}

          let no="";
					// 直接通过API获取墙设置信息
					console.log('用户信息',userInfo)
					const res = await api.wallowner.getWallSettings(userInfo.wall_id);

					if (res.code === 200 && res.data) {
            no = res.data.no;
						const settings = res.data.settings;
						// 如果有发布文案模板，使用模板
						if (settings.publish_template && settings.publish_template.trim()) {
							if (tagsContent) {
								return settings.publish_template + "\n\n" + tagsContent;
							}
							return settings.publish_template;
						}

						// 如果有小程序短链，使用短链
						// if (
						// 	settings.miniapp_short_link &&
						// 	settings.miniapp_short_link.trim()
						// ) {
						// 	if (tagsContent) {
						// 		return `👉一键投稿 ${settings.miniapp_short_link}\n${tagsContent}`;
						// 	}
						// 	return `👉一键投稿 ${settings.miniapp_short_link}`;
						// }
					}

					// 如果API调用失败或没有设置，返回默认
					if (tagsContent) {
            return `👉墙墙智能投稿合集${no}~\n` + tagsContent;
						// return "👉墙墙智能投稿合集~\n" + tagsContent;
					}
					return "👉投稿已发布，快来查看吧~";
				} catch (error) {
					console.error("生成分享口令失败:", error);
					return "👉投稿已发布，快来查看吧~";
				}
			},

			// 批量处理方法
			batchProcess(selectedItems, sourceList, targetList, newStatus) {
				// 从源列表中移除选中项
				selectedItems.forEach((item) => {
					const index = sourceList.findIndex((post) => post.id === item.id);
					if (index !== -1) {
						sourceList.splice(index, 1);
						// 更新状态并添加到目标列表
						item.status = newStatus;
						item.selected = false;
						targetList.unshift(item);
					}
				});

				// 更新全选状态
				this.checkAllSelected();
			},

			// 删除选中项
			deleteItems(selectedItems, sourceList) {
				// 从源列表中移除选中项
				selectedItems.forEach((item) => {
					const index = sourceList.findIndex((post) => post.id === item.id);
					if (index !== -1) {
						sourceList.splice(index, 1);
					}
				});

				// 更新全选状态
				this.checkAllSelected();
			},

			// 处理更多按钮点击
			handleMoreClick() {
				uni.showActionSheet({
					itemList: ["刷新", "筛选"],
					success: (res) => {
						if (res.tapIndex === 0) {
							// 刷新数据
							this.page = 1;
							this.hasMoreData = true;

							switch (this.currentTab) {
								case 0:
									this.pendingList = [];
									break;
								case 1:
									this.rejectedList = [];
									break;
								case 2:
									this.approvedList = [];
									break;
							}

							this.loadSubmissionData();

							uni.showToast({
								title: "刷新成功",
								icon: "success",
							});
						} else if (res.tapIndex === 1) {
							// 筛选功能
							uni.showToast({
								title: "筛选功能开发中",
								icon: "none",
							});
						}
					},
				});
			},

			// 批量转审核
			async batchMoveToReview() {
				const selectedItems = this.rejectedList.filter((item) => item.selected);
				if (selectedItems.length === 0) return;

				uni.showModal({
					title: "批量转审核",
					content: `确定将选中的 ${selectedItems.length} 篇投稿重新转为待审核状态吗？`,
					confirmText: "确定",
					cancelText: "取消",
					success: async (res) => {
						if (res.confirm) {
							try {
								uni.showLoading({
									title: "处理中...",
								});

								// 调用批量接口，状态置为0（待审核）
								await api.post.batchMoveToReview({
									postIds: selectedItems.map((item) => item.post_id || item.id),
									status: 0,
									comment: "批量转审核",
								});

								// 从未通过列表中移除选中项
								selectedItems.forEach((item) => {
									const index = this.rejectedList.findIndex(
										(post) =>
										(post.post_id || post.id) === (item.post_id || item.id)
									);
									if (index !== -1) {
										this.rejectedList.splice(index, 1);
									}
								});

								// 更新全选状态
								this.isAllSelected = false;

								uni.showToast({
									title: "批量转审核成功",
									icon: "success",
								});
							} catch (error) {
								console.error("批量转审核失败:", error);
								uni.showToast({
									title: "批量转审核失败",
									icon: "none",
								});
							} finally {
								uni.hideLoading();
							}
						}
					},
				});
			},

			// 刷新数据
			onRefresh() {
				this.refresherTriggered = true;
				this.loadSubmissionData();
			},

			// 初始化日期范围（默认今天）
			initDateRange() {
				const today = new Date();
				const todayStr = this.formatDate(today);
				this.dateRange = [todayStr, todayStr];
				this.dateRangeText = `${todayStr} 至 ${todayStr}`;
				this.hasDateFilter = true;
			},

			// 处理日期范围选择
			onDateRangeChange(e) {
				if (e && e.length === 2) {
					const [startDate, endDate] = e;
					this.dateRange = e;
					this.dateRangeText = `${this.formatDate(startDate)} 至 ${this.formatDate(endDate)}`;
					this.hasDateFilter = true;
				} else {
					this.dateRange = [];
					this.dateRangeText = "选择日期范围";
					this.hasDateFilter = false;
				}
				this.page = 1;
				this.loadSubmissionData();
			},

			// 清除日期筛选
			clearDateFilter() {
				this.dateRange = [];
				this.dateRangeText = "选择日期范围";
				this.hasDateFilter = false;
				this.page = 1;
				this.loadSubmissionData();
			},

			// 格式化日期显示
			formatDate(dateStr) {
				if (!dateStr) return "";
				const date = new Date(dateStr);
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, "0");
				const day = String(date.getDate()).padStart(2, "0");
				return `${year}-${month}-${day}`;
			},
		},
	};
</script>

<style>
	.submission-container {
		height: 100vh;
		background-color: #f0f6fb;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
	}

	.navbar-right {
		display: flex;
		align-items: center;
		padding-right: 10px;
	}

	.navbar-icon {
		padding: 0 5px;
	}

	/* Tab栏样式 */
	.tab-bar {
		background-color: #fff;
		display: flex;
		justify-content: space-around;
		padding: 0;
		margin: 10px 15px;
		border-radius: 10px;
		overflow: hidden;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
		position: fixed;
		left: 0;
		right: 0;
		z-index: 99;
	}

	.tab-item {
		flex: 1;
		text-align: center;
		padding: 15px 0;
		font-size: 16px;
		color: #666;
		position: relative;
	}

	.tab-active {
		color: #fff;
		background-color: #2979ff;
		font-weight: 500;
	}

	/* 投稿列表样式 */
	.submission-list {
		position: fixed;
		left: 0;
		right: 0;
		background-color: #f5f5f5;
		padding: 10px 15px;
		box-sizing: border-box;
	}

	.submission-item {
		background-color: #fff;
		border-radius: 10px;
		padding: 15px;
		margin-bottom: 15px;
		position: relative;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
	}

	.submission-header {
		display: flex;
		align-items: center;
		margin-bottom: 10px;
	}

	.author-avatar {
		width: 40px;
		height: 40px;
		border-radius: 50%;
		margin-right: 10px;
	}

	.submission-info {
		flex: 1;
	}

	.submission-nickname {
		font-size: 16px;
		font-weight: 500;
		color: #333;
		margin-bottom: 5px;
	}

	.submission-time {
		font-size: 12px;
		color: #999;
	}

	.submission-title {
		font-size: 16px;
		color: #000;
	}

	.tag-container {
		display: inline-block;
		background-color: rgba(41, 121, 255, 0.1);
		padding: 4px 10px;
		border-radius: 15px;
		/* margin-bottom: 10px; */
	}

	.tag-text {
		color: #2979ff;
		font-size: 14px;
	}

	.submission-content {
		font-size: 13px;
		color: #999;
		line-height: 1.5;
		margin-bottom: 10px;
		word-break: break-all;
		white-space: pre-wrap;
	}

	.contact-info {
		font-size: 14px;
		color: #666;
		background-color: #f8f8f8;
		padding: 8px 10px;
		border-radius: 5px;
		margin-bottom: 10px;
	}

	.image-container {
		display: flex;
		flex-wrap: wrap;
		margin: 0 -5px 10px;
	}

	.submission-image {
		width: calc(50% - 10px);
		height: 100px;
		margin: 5px;
		border-radius: 5px;
		background-color: #f5f5f5;
	}

	.action-buttons {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.action-btn {
		padding: 6px 16px;
		border-radius: 20px;
		margin-left: 10px;
		font-size: 14px;
		background-color: #f5f5f5;
		color: #666;
	}

	.preview-btn {
		background-color: #f2f2f2;
		color: #666;
	}

	.reject-btn {
		background-color: #fae3e3;
		color: #fff;
	}

	.approve-btn {
		background-color: #e6f7ff;
		color: #2979ff;
	}

	.checkbox {
		position: absolute;
		top: 15px;
		right: 15px;
		width: 20px;
		height: 20px;
		border-radius: 50%;
		border: 1px solid #ddd;
		display: flex;
		justify-content: center;
		align-items: center;
		background-color: #fff;
	}

	.checkbox-inner {
		width: 12px;
		height: 12px;
		border-radius: 50%;
		background-color: transparent;
	}

	.checkbox-selected {
		background-color: #2979ff;
	}

	.load-more {
		text-align: center;
		font-size: 14px;
		color: #2979ff;
		padding: 15px 0;
	}

	.no-more {
		text-align: center;
		font-size: 14px;
		color: #999;
		padding: 15px 0;
	}

	.empty-list {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 50px 0;
		color: #999;
	}

	.empty-list text {
		margin-top: 10px;
		font-size: 14px;
	}

	/* 底部操作栏 */
	.bottom-bar {
		position: fixed;
		left: 0;
		right: 0;
		bottom: 0;
		background-color: #fff;
		display: flex;
		justify-content: space-between;
		align-items: center;
		height: 60px;
		padding: 0 15px;
		box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.05);
	}

	.selection-info {
		display: flex;
		align-items: center;
		gap: 15px;
	}

	.select-all {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.checkbox-small {
		width: 18px;
		height: 18px;
		border-radius: 50%;
		border: 1px solid #ddd;
		background-color: #fff;
	}

	.checkbox-selected {
		background-color: #2979ff;
		border-color: #2979ff;
	}

	.batch-actions {
		display: flex;
		gap: 10px;
	}

	.batch-action-btn {
		padding: 8px 16px;
		border-radius: 20px;
		font-size: 14px;
		color: #fff;
		min-width: 30px;
		text-align: center;
	}

.action-button-left{
	font-size: 14px;
	font-weight: bold;
}

.unit{
	font-size: 12px;
	font-weight: normal;
	color: #333333;
}
.action-button-right{
	display: flex;
	justify-content: flex-end;
}


	.publish-btn {
		background-color: #2979ff;
	}

	.reject-btn {
		background-color: #ff4d4f;
	}

	/* 日期筛选栏样式 */
	.filter-bar {
		position: fixed;
		left: 0;
		right: 0;
		z-index: 98;
		background-color: #f8f9fa;
		padding: 8px 15px;
		display: flex;
		justify-content: space-between;
		align-items: center;
		border-bottom: 1px solid #eee;
	}

	.date-range-container {
		flex: 1;
	}

	.date-range-input {
		display: flex;
		align-items: center;
		background-color: #fff;
		padding: 8px 12px;
		border-radius: 6px;
		border: 1px solid #ddd;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
	}

	.date-range-text {
		margin-left: 6px;
		font-size: 14px;
		color: #333;
		flex: 1;
	}

	.stats-info {
		display: flex;
		align-items: center;
		flex-shrink: 0;
		margin-left: 10px;
	}

	.stats-text {
		font-size: 12px;
		color: #666;
		background-color: #fff;
		padding: 4px 8px;
		border-radius: 12px;
		border: 1px solid #ddd;
	}
	
	/* 复制按钮样式 */
	.copy-btn {
		display: flex;
		align-items: center;
		padding: 2px;
		color: #999;
		font-size: 16px;
		transition: all 0.2s ease;
		cursor: pointer;
	}
</style>
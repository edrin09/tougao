<template>
	<view class="withdraw-container" :style="{ paddingTop: statusBarHeight + 'px' }">
		<!-- 顶部导航栏 -->
		<uv-navbar title="提现历史" :autoBack="true" bgColor="transparent" :border="false"
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
		<view class="filter-bar" :style="{ top: filterBarTop + 10 + 'px' }">
			<view class="date-range-container">
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
				<text class="amount-text">总金额: ¥{{ totalAmount }}</text>
			</view>
		</view>

		<!-- 提现记录列表 -->
		<scroll-view class="withdraw-list" scroll-y="true" @scrolltolower="loadMore" @refresherrefresh="onRefresh"
			:refresher-enabled="true" :refresher-triggered="refresherTriggered" :style="{ 
        height: withdrawListHeight + 'px',
        top: withdrawListTop + 'px'
      }">
			<!-- 每一条提现记录 -->
			<view v-for="(item, index) in currentList" :key="index" class="withdraw-item">
				<!-- 提现信息头部 -->
				<view class="withdraw-header">
					<view class="withdraw-method">
						<uv-icon :name="getMethodIcon(item.withdrawal_method)" size="24" 
							:color="getMethodColor(item.withdrawal_method)"></uv-icon>
						<text class="method-text">{{ getMethodText(item.withdrawal_method) }}</text>
					</view>
					<view class="withdraw-status" :class="getStatusClass(item.withdraw_status)">
						{{ getStatusText(item.withdraw_status) }}
					</view>
				</view>

				<!-- 提现金额 -->
				<view class="withdraw-amount">
					<text class="amount-label">提现金额</text>
					<text class="amount-value">¥{{ item.cash_amount }}</text>
				</view>

				<!-- 提现详情 -->
				<view class="withdraw-details">
					<view class="detail-row">
						<text class="detail-label">申请时间:</text>
						<text class="detail-value">{{ item.create_time }}</text>
					</view>
					
					<view class="detail-row" v-if="item.cash_time">
						<text class="detail-label">到账时间:</text>
						<text class="detail-value">{{ item.cash_time }}</text>
					</view>

					<!-- 线下提现账户信息 -->
					<template v-if="item.withdrawal_method === 'offline'">
						<view class="detail-row" v-if="item.bank_name">
							<text class="detail-label">银行名称:</text>
							<text class="detail-value">{{ item.bank_name }}</text>
						</view>
						<view class="detail-row" v-if="item.bank_card">
							<text class="detail-label">银行卡号:</text>
							<text class="detail-value">{{ formatBankCard(item.bank_card) }}</text>
						</view>
						<view class="detail-row" v-if="item.payee_name">
							<text class="detail-label">收款人:</text>
							<text class="detail-value">{{ item.payee_name }}</text>
						</view>
					</template>

					<!-- 拒绝原因 -->
					<view class="detail-row" v-if="item.reject_remarks && item.withdraw_status === -1">
						<text class="detail-label">拒绝原因:</text>
						<text class="detail-value reject-reason">{{ item.reject_remarks }}</text>
					</view>
				</view>

				<!-- 提现凭证 -->
				<view class="withdraw-evidence" v-if="item.withdrawal_evidence">
					<text class="evidence-label">提现凭证:</text>
					<image class="evidence-image" :src="item.withdrawal_evidence" mode="aspectFill" 
						@click="previewEvidence(item.withdrawal_evidence)"></image>
				</view>

				<!-- 操作按钮 -->
				<view class="action-buttons" v-if="item.withdraw_status === -1">
					<view class="action-btn retry-btn" @click="retryWithdraw(item)">
						重新申请
					</view>
				</view>
			</view>

			<!-- 加载更多 -->
			<view class="load-more" v-if="hasMoreData">加载更多...</view>
			<view class="no-more" v-else-if="currentList.length > 0">没有更多数据了</view>
			<view class="empty-list" v-else>
				<uni-icons type="wallet" size="50" color="#dddddd"></uni-icons>
				<text>暂无提现记录</text>
			</view>
		</scroll-view>
	</view>
</template>

<script>
	import withdrawApi from "../../api/withdraw";

	export default {
		data() {
			return {
				tabs: ["全部", "微信零钱", "线下账户"],
				currentTab: 0,
				allList: [],
				wechatList: [],
				offlineList: [],
				hasMoreData: true,
				page: 1,
				pageSize: 10,
				loading: false,
				statusBarHeight: 0,
				refresherTriggered: false,
				dateRange: [],
				dateRangeText: "选择日期范围",
				hasDateFilter: false,
				totalCount: 0,
				totalAmount: 0
			};
		},
		computed: {
			currentList() {
				switch (this.currentTab) {
					case 0:
						return this.allList;
					case 1:
						return this.wechatList;
					case 2:
						return this.offlineList;
					default:
						return [];
				}
			},
			// 动态计算筛选栏的top值
			filterBarTop() {
				return this.statusBarHeight + 44 + 44 + 10; // 状态栏 + 导航栏 + Tab栏 + 间距
			},
			// 动态计算提现列表的top值
			withdrawListTop() {
				return this.statusBarHeight + 44 + 44 + 44 + 60; // 状态栏 + 导航栏 + Tab栏 + 筛选栏 + 间距
			},
			// 动态计算提现列表的高度
			withdrawListHeight() {
				const navBarHeight = 44; // 导航栏高度
				const tabBarHeight = 44; // Tab栏高度
				const filterBarHeight = 44; // 筛选栏高度
				const spacing = 30; // 各种间距

				const totalUsedHeight = this.statusBarHeight + navBarHeight + tabBarHeight + filterBarHeight + spacing;
				return uni.getSystemInfoSync().windowHeight - totalUsedHeight;
			}
		},
		onLoad() {
			this.loadWithdrawData();
		},
		onReady() {
			// 获取系统信息
			const systemInfo = uni.getSystemInfoSync();
			this.statusBarHeight = systemInfo.statusBarHeight;
		},
		methods: {
			// 切换标签
			switchTab(index) {
				if (this.currentTab === index) return;

				this.currentTab = index;
				this.page = 1;
				this.hasMoreData = true;

				// 清空当前标签数据
				this.clearCurrentTabData();
				
				// 重新加载数据
				this.loadWithdrawData();
			},

			// 清空当前标签数据
			clearCurrentTabData() {
				switch (this.currentTab) {
					case 0:
						this.allList = [];
						break;
					case 1:
						this.wechatList = [];
						break;
					case 2:
						this.offlineList = [];
						break;
				}
			},

			// 加载提现数据
			async loadWithdrawData() {
				if (this.loading) return;

				try {
					this.loading = true;
					uni.showLoading({
						title: "加载中..."
					});

					const params = {
						page: this.page,
						limit: this.pageSize,
						// _t: Date.now()
					};

					// 添加日期筛选参数
					if (this.hasDateFilter && this.dateRange.length === 2) {
						params.start_date = this.dateRange[0];
						params.end_date = this.dateRange[1];
					}

					// 根据当前tab添加提现方式筛选
					if (this.currentTab === 1) {
						params.withdrawal_method = 'wechat';
					} else if (this.currentTab === 2) {
						params.withdrawal_method = 'offline';
					}

					const res = await withdrawApi.history(params);

					const list = res.data.list || [];
					const total = res.data.total || list.length;

					// 计算总金额
					let totalAmount = 0;
					if (this.page === 1) {
						// 只在第一页时计算总金额
						totalAmount = list.reduce((sum, item) => sum + parseFloat(item.cash_amount || 0), 0);
					}

					// 根据当前标签更新对应列表
					switch (this.currentTab) {
						case 0:
							this.allList = this.page === 1 ? list : [...this.allList, ...list];
							break;
						case 1:
							this.wechatList = this.page === 1 ? list : [...this.wechatList, ...list];
							break;
						case 2:
							this.offlineList = this.page === 1 ? list : [...this.offlineList, ...list];
							break;
					}

					// 更新总数和分页状态
					this.totalCount = this.page === 1 ? total : this.totalCount;
					if (this.page === 1) {
						this.totalAmount = totalAmount.toFixed(2);
					}
					this.hasMoreData = list.length === this.pageSize;

				} catch (error) {
					console.error("加载提现数据失败:", error);
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
					this.loadWithdrawData();
				}
			},

			// 获取提现方式图标
			getMethodIcon(method) {
				switch (method) {
					case 'wechat':
						return 'chat';
					case 'offline':
						return 'account';
					default:
						return 'wallet';
				}
			},

			// 获取提现方式颜色
			getMethodColor(method) {
				switch (method) {
					case 'wechat':
						return '#07c160';
					case 'offline':
						return '#2979ff';
					default:
						return '#666';
				}
			},

			// 获取提现方式文字
			getMethodText(method) {
				switch (method) {
					case 'wechat':
						return '微信零钱';
					case 'offline':
						return '线下账户';
					default:
						return '未知方式';
				}
			},

			// 获取状态样式类
			getStatusClass(status) {
				switch (status) {
					case 0:
						return 'status-pending';
					case 2:
						return 'status-pending';
					case 3:
						return 'status-failed';
					case 5:
						return 'status-success';
					default:
						return 'status-pending';
				}
			},

			// 获取状态文字
			getStatusText(status) {
				switch (status) {
					case 0:
						return '待审核';
					case 2:
						return '待打款';
					case 3:
						return '已拒绝';
					case 5:
						return '已完成';
					default:
						return '未知状态';
				}
			},

			// 格式化银行卡号
			formatBankCard(cardNumber) {
				if (!cardNumber) return '';
				// 只显示后四位，前面用*代替
				return cardNumber.replace(/^.{0,}(.{4})$/, '****$1');
			},

			// 预览提现凭证
			previewEvidence(evidenceUrl) {
				uni.previewImage({
					urls: [evidenceUrl],
					current: evidenceUrl
				});
			},

			// 重新申请提现
			retryWithdraw(item) {
				uni.showModal({
					title: '重新申请',
					content: '确定要重新申请提现吗？',
					confirmText: '确定',
					cancelText: '取消',
					success: (res) => {
						if (res.confirm) {
							// 跳转到提现申请页面，带上之前的信息
							const withdrawData = {
								amount: item.cash_amount,
								method: item.withdrawal_method,
								bankName: item.bank_name,
								bankCard: item.bank_card,
								payeeName: item.payee_name
							};
							
							uni.navigateTo({
								url: `/pages/withdraw/withdraw?data=${encodeURIComponent(JSON.stringify(withdrawData))}`
							});
						}
					}
				});
			},

			// 刷新数据
			onRefresh() {
				this.refresherTriggered = true;
				this.page = 1;
				this.hasMoreData = true;
				this.clearCurrentTabData();
				this.loadWithdrawData();
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
				this.hasMoreData = true;
				this.clearCurrentTabData();
				this.loadWithdrawData();
			},

			// 清除日期筛选
			clearDateFilter() {
				this.dateRange = [];
				this.dateRangeText = "选择日期范围";
				this.hasDateFilter = false;
				this.page = 1;
				this.hasMoreData = true;
				this.clearCurrentTabData();
				this.loadWithdrawData();
			},

			// 格式化日期显示
			formatDate(dateStr) {
				if (!dateStr) return "";
				const date = new Date(dateStr);
				const year = date.getFullYear();
				const month = String(date.getMonth() + 1).padStart(2, "0");
				const day = String(date.getDate()).padStart(2, "0");
				return `${year}-${month}-${day}`;
			}
		}
	};
</script>

<style>
	.withdraw-container {
		height: 100vh;
		background-color: #f0f6fb;
		display: flex;
		flex-direction: column;
		box-sizing: border-box;
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
		flex-direction: column;
		align-items: flex-end;
		flex-shrink: 0;
		margin-left: 10px;
		gap: 4px;
	}

	.stats-text {
		font-size: 12px;
		color: #666;
		background-color: #fff;
		padding: 4px 8px;
		border-radius: 12px;
		border: 1px solid #ddd;
	}

	.amount-text {
		font-size: 12px;
		color: #f56565;
		background-color: #fff;
		padding: 4px 8px;
		border-radius: 12px;
		border: 1px solid #ddd;
		font-weight: 500;
	}

	/* 提现列表样式 */
	.withdraw-list {
		position: fixed;
		left: 0;
		right: 0;
		background-color: #f5f5f5;
		padding: 10px 15px;
		box-sizing: border-box;
	}

	.withdraw-item {
		background-color: #fff;
		border-radius: 10px;
		padding: 15px;
		margin-bottom: 15px;
		box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
	}

	.withdraw-header {
		display: flex;
		justify-content: space-between;
		align-items: center;
		margin-bottom: 15px;
	}

	.withdraw-method {
		display: flex;
		align-items: center;
		gap: 8px;
	}

	.method-text {
		font-size: 16px;
		font-weight: 500;
		color: #333;
	}

	.withdraw-status {
		padding: 4px 12px;
		border-radius: 12px;
		font-size: 12px;
		font-weight: 500;
	}

	.status-pending {
		background-color: #fff3cd;
		color: #856404;
	}

	.status-success {
		background-color: #d4edda;
		color: #155724;
	}

	.status-failed {
		background-color: #f8d7da;
		color: #721c24;
	}

	.withdraw-amount {
		display: flex;
		justify-content: space-between;
		align-items: center;
		padding: 12px 0;
		border-bottom: 1px solid #f0f0f0;
		margin-bottom: 12px;
	}

	.amount-label {
		font-size: 14px;
		color: #666;
	}

	.amount-value {
		font-size: 20px;
		font-weight: bold;
		color: #f56565;
	}

	.withdraw-details {
		margin-bottom: 12px;
	}

	.detail-row {
		display: flex;
		justify-content: space-between;
		margin-bottom: 8px;
	}

	.detail-label {
		font-size: 14px;
		color: #666;
		flex-shrink: 0;
	}

	.detail-value {
		font-size: 14px;
		color: #333;
		text-align: right;
		flex: 1;
		margin-left: 10px;
	}

	.reject-reason {
		color: #f56565;
	}

	.withdraw-evidence {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 12px;
	}

	.evidence-label {
		font-size: 14px;
		color: #666;
		flex-shrink: 0;
	}

	.evidence-image {
		width: 60px;
		height: 60px;
		border-radius: 6px;
		background-color: #f5f5f5;
	}

	.action-buttons {
		display: flex;
		justify-content: flex-end;
	}

	.action-btn {
		padding: 8px 16px;
		border-radius: 20px;
		font-size: 14px;
		background-color: #f5f5f5;
		color: #666;
	}

	.retry-btn {
		background-color: #e6f7ff;
		color: #2979ff;
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
</style>
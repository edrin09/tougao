<template>
	<view class="withdraw-container">
		<!-- 顶部导航栏 -->
		<uv-navbar title="申请提现" :autoBack="true" bgColor="#ffffff" :border="true"
			:titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
			leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
		</uv-navbar>

		<!-- 主要内容区域 -->
		<view class="content-wrapper">
			<!-- 可提现金额卡片 -->
			<view class="available-amount-card">
				<view class="card-header">
					<uni-icons type="wallet" size="24" color="#2979ff"></uni-icons>
					<text class="card-title">可提现金额</text>
				</view>
				<view class="amount-display">
					<text class="currency-symbol">¥</text>
					<text class="amount-number">{{ availableAmount }}</text>
				</view>
			</view>

			<!-- 提现表单 -->
			<view class="withdraw-form-card">
				<!-- 提现金额输入 -->
				<view class="form-section">
					<text class="section-title">提现金额</text>
					<view class="amount-input-wrapper">
						<text class="input-currency">¥</text>
						<input class="amount-input" v-model="withdrawForm.cash_amount" type="digit"
							placeholder="请输入提现金额" @input="onAmountInput" />
						<text class="quick-all" @click="inputAllAmount">全部</text>
					</view>
					<view class="amount-tips" v-if="withdrawForm.cash_amount">
						<text class="tips-text">提现金额：¥{{ withdrawForm.cash_amount }}</text>
					</view>
				</view>

				<!-- 提现方式选择 -->
				<view class="form-section">
					<text class="section-title">提现方式</text>
					<view class="method-options">
						<view v-for="method in withdrawalMethods" :key="method.value" class="method-option"
							:class="{ 'method-active': withdrawForm.withdrawal_method === method.value }"
							@click="selectWithdrawMethod(method.value)">
							<view class="method-content">
								<uni-icons :type="method.icon" size="20"
									:color="withdrawForm.withdrawal_method === method.value ? '#2979ff' : '#666'"></uni-icons>
								<text class="method-text">{{ method.label }}</text>
							</view>
							<view class="method-radio">
								<view class="radio-dot" v-if="withdrawForm.withdrawal_method === method.value"></view>
							</view>
						</view>
					</view>
				</view>

				<!-- 银行账户信息（当选择线下提现时显示） -->
				<view class="form-section" v-if="withdrawForm.withdrawal_method === 'offline'">
					<text class="section-title">银行账户信息</text>

					<view class="input-group">
						<text class="input-label">收款人姓名</text>
						<input class="form-input" v-model="withdrawForm.payee_name" placeholder="请输入收款人姓名" />
					</view>

					<view class="input-group">
						<text class="input-label">银行名称</text>
						<input class="form-input" v-model="withdrawForm.bank_name" placeholder="请输入银行名称" />
					</view>

					<view class="input-group">
						<text class="input-label">银行卡号</text>
						<input class="form-input" v-model="withdrawForm.bank_card" placeholder="请输入银行卡号" />
					</view>
				</view>

				<!-- 提现说明 -->
				<view class="form-section">
					<text class="section-title">提现说明</text>
					<view class="tips-content">
						<view class="tip-item">
							<text class="tip-dot">•</text>
							<text class="tip-text">微信零钱提现将直接到账微信钱包</text>
						</view>
						<view class="tip-item">
							<text class="tip-dot">•</text>
							<text class="tip-text">银行账户提现需要1-3个工作日到账</text>
						</view>
						<view class="tip-item">
							<text class="tip-dot">•</text>
							<text class="tip-text">请确保填写信息准确，避免提现失败</text>
						</view>
					</view>
				</view>
			</view>
		</view>

		<!-- 底部确认按钮 -->
		<view class="bottom-button-area">
			<button class="confirm-withdraw-button" @click="submitWithdraw" :disabled="!canSubmit || submitting">
				<text class="button-text" v-if="!submitting">确认提现</text>
				<text class="button-text" v-else>提交中...</text>
			</button>
		</view>

		<!-- 加载遮罩 -->
		<uv-loading-page :loading="pageLoading" loadingText="加载中..."></uv-loading-page>
	</view>
</template>

<script>
	import withdrawApi from "../../api/withdraw";

	export default {
		data() {
			return {
				availableAmount: 0, // 可提现金额
				pageLoading: false,
				submitting: false,
				withdrawForm: {
					withdrawal_method: '', // 根据API动态设置
					cash_amount: '',
					bank_card: '',
					bank_name: '',
					payee_name: ''
				},
				withdrawalMethods: [], // 根据API动态填充
				// 所有可能的提现方式配置
				allWithdrawalMethods: [{
						value: 'wechat',
						label: '微信零钱',
						icon: 'weixin'
					},
					{
						value: 'offline',
						label: '银行账户',
						icon: 'creditcard'
					}
				]
			};
		},

		computed: {
			// 检查是否可以提交
			canSubmit() {
				if (!this.withdrawForm.cash_amount) return false;

				const amount = parseFloat(this.withdrawForm.cash_amount);
				if (isNaN(amount) || amount <= 0) return false;
				if (amount > this.availableAmount) return false;

				// 如果是银行账户方式，需要检查银行信息
				if (this.withdrawForm.withdrawal_method === 'offline') {
					return !!(this.withdrawForm.payee_name &&
						this.withdrawForm.bank_name &&
						this.withdrawForm.bank_card);
				}

				return true;
			}
		},

		onLoad(options) {
			// 获取传入的可提现金额
			this.availableAmount = options.amount ? parseFloat(options.amount) : 0;
			this.loadWithdrawalChannels();
		},

		methods: {
			// 加载提现渠道
			async loadWithdrawalChannels() {
				try {
					this.pageLoading = true;
					const result = await withdrawApi.withdrawal_channels();
					if (result && result.code === 200) {
						// 解析API返回的支持渠道
						const supportedChannels = JSON.parse(result.data.info || '[]');

						// 根据支持的渠道筛选可用的提现方式
						this.withdrawalMethods = this.allWithdrawalMethods.filter(method =>
							supportedChannels.includes(method.value)
						);

						// 设置默认提现方式为第一个可用的
						if (this.withdrawalMethods.length > 0) {
							this.withdrawForm.withdrawal_method = this.withdrawalMethods[0].value;
						}

						console.log('支持的提现渠道:', supportedChannels);
						console.log('可用的提现方式:', this.withdrawalMethods);
					} else {
						// 如果接口返回失败，使用默认配置
						this.withdrawalMethods = [...this.allWithdrawalMethods];
						this.withdrawForm.withdrawal_method = this.withdrawalMethods[0]?.value || 'wechat';

						uni.showToast({
							title: result?.msg || "获取提现渠道失败，使用默认配置",
							icon: "none"
						});
					}
				} catch (error) {
					console.error("获取提现渠道失败:", error);

					// 接口调用失败时，使用所有可用的提现方式作为备选
					this.withdrawalMethods = [...this.allWithdrawalMethods];
					this.withdrawForm.withdrawal_method = this.withdrawalMethods[0]?.value || 'wechat';

					uni.showToast({
						title: "获取提现渠道失败，使用默认配置",
						icon: "none"
					});
				} finally {
					this.pageLoading = false;
				}
			},

			// 金额输入处理
			onAmountInput(e) {
				let value = e.detail.value;
				// 限制小数点后两位
				if (value.includes('.')) {
					const parts = value.split('.');
					if (parts[1] && parts[1].length > 2) {
						value = parts[0] + '.' + parts[1].substring(0, 2);
						this.withdrawForm.cash_amount = value;
					}
				}
			},

			// 全部金额
			inputAllAmount() {
				this.withdrawForm.cash_amount = this.availableAmount.toString();
			},

			// 选择提现方式
			selectWithdrawMethod(method) {
				this.withdrawForm.withdrawal_method = method;
				// 切换方式时清空银行信息
				if (method === 'wechat') {
					this.withdrawForm.bank_card = '';
					this.withdrawForm.bank_name = '';
					this.withdrawForm.payee_name = '';
				}
			},

			// 提交提现申请
			async submitWithdraw() {
				// 表单验证
				if (!this.validateForm()) {
					return;
				}

				try {
					this.submitting = true;

					const params = {
						withdrawal_method: this.withdrawForm.withdrawal_method,
						cash_amount: this.withdrawForm.cash_amount
					};

					// 如果是银行账户方式，添加银行信息
					if (this.withdrawForm.withdrawal_method === 'offline') {
						params.bank_card = this.withdrawForm.bank_card;
						params.bank_name = this.withdrawForm.bank_name;
						params.payee_name = this.withdrawForm.payee_name;
					}

					const result = await withdrawApi.application(params);

					if (result && result.code === 200) {
						uni.showToast({
							title: "提现申请提交成功",
							icon: "success",
							duration: 2000
						});

						// 延迟返回上一页
						setTimeout(() => {
							uni.navigateBack();
						}, 2000);
					} else {
						uni.showToast({
							title: result?.msg || "提现申请失败",
							icon: "none"
						});
					}
				} catch (error) {
					console.error("提现申请失败:", error);
					uni.showToast({
						title: error.message || "网络错误，请稍后重试",
						icon: "none"
					});
				} finally {
					this.submitting = false;
				}
			},

			// 表单验证
			validateForm() {
				if (!this.withdrawForm.cash_amount) {
					uni.showToast({
						title: "请输入提现金额",
						icon: "none"
					});
					return false;
				}

				const amount = parseFloat(this.withdrawForm.cash_amount);
				if (isNaN(amount) || amount <= 0) {
					uni.showToast({
						title: "请输入有效的提现金额",
						icon: "none"
					});
					return false;
				}

				if (amount > this.availableAmount) {
					uni.showToast({
						title: "提现金额不能超过可提现金额",
						icon: "none"
					});
					return false;
				}

				// 银行账户方式需要额外验证
				if (this.withdrawForm.withdrawal_method === 'offline') {
					if (!this.withdrawForm.payee_name) {
						uni.showToast({
							title: "请输入收款人姓名",
							icon: "none"
						});
						return false;
					}
					if (!this.withdrawForm.bank_name) {
						uni.showToast({
							title: "请输入银行名称",
							icon: "none"
						});
						return false;
					}
					if (!this.withdrawForm.bank_card) {
						uni.showToast({
							title: "请输入银行卡号",
							icon: "none"
						});
						return false;
					}
				}

				return true;
			}
		}
	};
</script>

<style>
	.withdraw-container {
		min-height: 100vh;
		background-color: #f0f6fb;
		padding-bottom: 100px;
		/* 为底部按钮预留空间 */
		box-sizing: border-box;
	}

	.content-wrapper {
		padding: 15px;
	}

	/* 可提现金额卡片 */
	.available-amount-card {
		background-color: #ffffff;
		border-radius: 12px;
		padding: 20px;
		margin-bottom: 15px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
	}

	.card-header {
		display: flex;
		align-items: center;
		margin-bottom: 15px;
	}

	.card-title {
		margin-left: 8px;
		font-size: 16px;
		font-weight: 500;
		color: #333;
	}

	.amount-display {
		display: flex;
		align-items: baseline;
		justify-content: center;
	}

	.currency-symbol {
		font-size: 24px;
		color: #2979ff;
		margin-right: 8px;
		font-weight: 500;
	}

	.amount-number {
		font-size: 36px;
		font-weight: bold;
		color: #2979ff;
	}

	/* 提现表单卡片 */
	.withdraw-form-card {
		background-color: #ffffff;
		border-radius: 12px;
		padding: 20px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
	}

	/* 表单区块 */
	.form-section {
		margin-bottom: 25px;
	}

	.form-section:last-child {
		margin-bottom: 0;
	}

	.section-title {
		display: block;
		font-size: 16px;
		font-weight: 500;
		color: #333;
		margin-bottom: 15px;
	}

	/* 金额输入 */
	.amount-input-wrapper {
		display: flex;
		align-items: center;
		height: 50px;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		padding: 0 15px;
		background-color: #fff;
	}

	.input-currency {
		font-size: 18px;
		color: #666;
		margin-right: 8px;
	}

	.amount-input {
		flex: 1;
		height: 100%;
		border: none;
		outline: none;
		font-size: 18px;
		color: #333;
	}

	.quick-all {
		color: #2979ff;
		font-size: 14px;
		font-weight: 500;
		padding: 4px 8px;
		border: 1px solid #2979ff;
		border-radius: 4px;
		cursor: pointer;
	}

	.amount-tips {
		margin-top: 8px;
	}

	.tips-text {
		font-size: 12px;
		color: #999;
	}

	/* 提现方式选择 */
	.method-options {
		display: flex;
		flex-direction: column;
		gap: 10px;
	}

	.method-option {
		display: flex;
		align-items: center;
		justify-content: space-between;
		height: 60px;
		padding: 0 15px;
		border: 1px solid #e0e0e0;
		border-radius: 8px;
		cursor: pointer;
		transition: all 0.3s ease;
		background-color: #fff;
	}

	.method-option.method-active {
		border-color: #2979ff;
		background-color: rgba(41, 121, 255, 0.05);
	}

	.method-content {
		display: flex;
		align-items: center;
	}

	.method-text {
		margin-left: 12px;
		font-size: 16px;
		color: #333;
	}

	.method-active .method-text {
		color: #2979ff;
		font-weight: 500;
	}

	/* 单选按钮 */
	.method-radio {
		width: 20px;
		height: 20px;
		border: 2px solid #e0e0e0;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.3s ease;
	}

	.method-active .method-radio {
		border-color: #2979ff;
	}

	.radio-dot {
		width: 8px;
		height: 8px;
		background-color: #2979ff;
		border-radius: 50%;
	}

	/* 输入组 */
	.input-group {
		margin-bottom: 15px;
	}

	.input-group:last-child {
		margin-bottom: 0;
	}

	.input-label {
		display: block;
		font-size: 14px;
		color: #666;
		margin-bottom: 8px;
	}

	.form-input {
		width: 100%;
		height: 44px;
		padding: 0 12px;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		font-size: 14px;
		background-color: #fff;
		box-sizing: border-box;
	}

	.form-input:focus {
		border-color: #2979ff;
		outline: none;
	}

	/* 提现说明 */
	.tips-content {
		background-color: #f8f9fa;
		border-radius: 8px;
		padding: 15px;
	}

	.tip-item {
		display: flex;
		align-items: flex-start;
		margin-bottom: 8px;
	}

	.tip-item:last-child {
		margin-bottom: 0;
	}

	.tip-dot {
		color: #2979ff;
		margin-right: 8px;
		font-weight: bold;
		line-height: 1.4;
	}

	.tip-text {
		font-size: 12px;
		color: #666;
		line-height: 1.4;
		flex: 1;
	}

	/* 底部按钮区域 */
	.bottom-button-area {
		position: fixed;
		bottom: 0;
		left: 0;
		right: 0;
		background-color: #ffffff;
		border-top: 1px solid #f0f0f0;
		padding: 15px;
		padding-bottom: calc(15px + env(safe-area-inset-bottom));
		box-shadow: 0 -2px 12px rgba(0, 0, 0, 0.08);
		z-index: 999;
	}

	/* 确认提现按钮 */
	.confirm-withdraw-button {
		width: 100%;
		height: 50px;
		background: linear-gradient(135deg, #2979ff, #3c9cff);
		border: none;
		border-radius: 25px;
		box-shadow: 0 4px 15px rgba(41, 121, 255, 0.3);
		display: flex;
		align-items: center;
		justify-content: center;
		transition: all 0.3s ease;
	}

	.confirm-withdraw-button:active {
		transform: translateY(1px);
		box-shadow: 0 2px 8px rgba(41, 121, 255, 0.3);
	}

	.confirm-withdraw-button[disabled] {
		background: linear-gradient(135deg, #cccccc, #dddddd);
		box-shadow: none;
	}

	.button-text {
		color: white;
		font-size: 16px;
		font-weight: 600;
		text-align: center;
	}

	.confirm-withdraw-button[disabled] .button-text {
		color: #999999;
	}
</style>
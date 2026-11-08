<template>
  <view class="commission-detail-container">
    <!-- 顶部导航栏保持不变 -->
    <uv-navbar title="佣金明细" :autoBack="true" bgColor="#ffffff" :border="true"
               :titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }" leftIcon="arrow-left"
               leftIconColor="#333333" :safeAreaInsetTop="true" :fixed="true" :placeholder="true" :height="44">
    </uv-navbar>

    <!-- 主要内容区域 -->
    <view class="content-wrapper">
      <!-- 加载状态 -->
      <view class="loading-container" v-if="loading">
        <uv-loading-icon show mode="flower"></uv-loading-icon>
        <text class="loading-text">加载中...</text>
      </view>

      <!-- 明细列表 -->
      <view class="detail-content" v-else-if="listData.length > 0">
        <!-- 循环显示每个佣金明细卡片 -->
        <view class="post-info-card" v-for="(detailData, index) in listData" :key="detailData.post_id || index">
          <view class="info-content">
            <!-- 基本信息 -->
            <view class="basic-info">
              <view class="info-item">
                <text class="info-label">标题</text>
                <text class="info-value title-text">{{ detailData.title || '暂无标题' }}</text>
              </view>
              <view class="info-item">
                <text class="info-label">渠道</text>
                <text class="info-value">{{ detailData.channels_name || '--' }}</text>
              </view>
              <view class="info-item" v-if="detailData.create_time">
                <text class="info-label">时间</text>
                <text class="info-value">{{ detailData.create_time }}</text>
              </view>
            </view>
            <!-- 佣金信息 -->
            <view class="commission-section">
              <view class="commission-amount">
                <text class="amount-symbol">¥</text>
                <text class="amount-value">{{ detailData.commission_amount || 0 }}</text>
              </view>
            </view>
          </view>

          <!-- 图片展示区域 -->
          <view class="image-section">
            <view class="image-container" v-if="detailData.images && detailData.images.length > 0"
                  @click="previewImage(detailData.images[0], 0, index)">
              <image class="main-image" :src="detailData.images[0]" mode="aspectFill" :lazy-load="true"
                     @error="(e) => handleImageError(e, index)"></image>
              <view class="image-count" v-if="detailData.images.length > 1">
                <text class="count-text">{{ detailData.images.length }}</text>
              </view>
            </view>
            <view class="no-image-placeholder" v-else>
              <uv-icon name="photo" size="32" color="#cccccc"></uv-icon>
              <text class="placeholder-text">暂无图片</text>
            </view>
          </view>
        </view>

        <!-- 加载更多提示 -->
        <view class="load-more-container" v-if="listData.length > 0">
          <text class="load-more-text" v-if="isLoadingMore">加载中...</text>
          <text class="load-more-text" v-else-if="!hasMore">没有更多数据了</text>
          <text class="load-more-text" v-else>上拉加载更多</text>
        </view>
      </view>

      <!-- 错误状态 -->
      <view class="error-container" v-else-if="error">
        <uv-icon name="error-circle" size="48" color="#3c9cff"></uv-icon>
        <text class="error-text">{{ error }}</text>
        <button class="retry-button" @click="loadDetail(true)">重新加载</button>
      </view>

      <!-- 空状态 -->
      <view class="empty-container" v-else>
        <uv-icon name="inbox" size="48" color="#cccccc"></uv-icon>
        <text class="empty-text">暂无佣金明细数据</text>
      </view>
    </view>

    <!-- 底部提现区域保持不变 -->
    <view class="bottom-withdraw-area" v-if="listData.length > 0">
      <view class="withdraw-info">
        <view class="available-amount-section">
          <text class="available-label">可提现金额</text>
          <view class="amount-display">
            <text class="currency-symbol">¥</text>
            <text class="amount-number">{{ withdrawableAmount || 0 }}</text>
          </view>
        </view>
      </view>

      <view class="withdraw-button-wrapper">
        <button class="withdraw-button" @click="goToWithdrawPage"
                :disabled="!withdrawableAmount || withdrawableAmount <= 0">
          <text class="withdraw-button-text">提现</text>
        </button>
      </view>
    </view>
  </view>
</template>

<script>
import api from "../../api";

export default {
  data() {
    return {
      listData: [], // 改为数组存储多条数据
      loading: true,
      error: null,
      postId: null,
      withdrawableAmount: 0,
      // 分页相关参数
      currentPage: 1,
      pageSize: 10,
      hasMore: true, // 是否还有更多数据
      isLoadingMore: false, // 是否正在加载更多
      total: 0
    };
  },

  onLoad(options) {
    console.log('可提现金额：', options.withdrawable_amount);
    this.withdrawableAmount = parseFloat(options.withdrawable_amount) || 0;
    this.loadDetail(true);
  },

  // 上拉加载更多
  async onReachBottom() {
    if (this.hasMore && !this.isLoadingMore && !this.loading) {
      this.loadDetail(false);
    }
  },

  // 下拉刷新
  async onPullDownRefresh() {
    this.currentPage = 1;
    this.hasMore = true;
    this.loadDetail(true).then(() => {
      uni.stopPullDownRefresh();
    });
  },

  methods: {
    // 加载佣金明细
    async loadDetail(isRefresh = true) {
      // 检查是否还有更多数据
      if (!isRefresh && !this.hasMore) {
        return;
      }

      try {
        if (isRefresh) {
          this.loading = true;
        } else {
          this.isLoadingMore = true;
        }
        this.error = null;

        // 调用接口获取佣金明细
        const result = await api.wallowner.detail({
          page: this.currentPage,
          limit: this.pageSize,
        });

        if (result && result.code === 200 && result.data.list) {
          const newList = result.data.list;

          if (isRefresh) {
            // 刷新时替换整个列表
            this.listData = newList.map(item => this.formatDetailData(item));
          } else {
            // 加载更多时追加到列表
            this.listData = [...this.listData, ...newList.map(item => this.formatDetailData(item))];
          }

          // 更新分页信息
          this.total = result.data.total || 0;

          // 判断是否还有更多数据
          if (newList.length < this.pageSize) {
            this.hasMore = false;
          } else {
            this.hasMore = true;
            this.currentPage++;
          }

        } else {
          this.error = result?.msg || "获取明细失败";
        }
      } catch (error) {
        console.error("加载佣金明细失败:", error);
        this.error = error.message || "网络错误，请稍后重试";
      } finally {
        this.loading = false;
        this.isLoadingMore = false;
      }
    },

    // 格式化详情数据
    formatDetailData(postData) {
      return {
        commission_amount: postData.commission || 0,
        // post_id: postData.post_id,
        title: postData.title,
        images: postData.images || [],
        create_time: postData.create_time,
        channels_name: postData.channels_name,
      };
    },

    // 跳转到提现页面
    goToWithdrawPage() {
      if (!this.withdrawableAmount || this.withdrawableAmount <= 0) {
        uni.showToast({
          title: "暂无可提现金额",
          icon: "none"
        });
        return;
      }

      uni.navigateTo({
        url: `/pages/withdraw/withdraw?amount=${this.withdrawableAmount}`
      });
    },

    // 预览图片
    previewImage(currentImage, index, itemIndex) {
      const images = this.listData[itemIndex].images;
      if (!images || images.length === 0) {
        return;
      }

      uni.previewImage({
        current: index,
        urls: images,
        fail: (error) => {
          console.error("图片预览失败:", error);
          uni.showToast({
            title: "图片预览失败",
            icon: "none"
          });
        }
      });
    },

    // 处理图片加载错误
    handleImageError(e, itemIndex) {
      console.error("图片加载失败:", e);
      // 可以设置默认图片
      this.listData[itemIndex].images[0] = '/static/images/default-image.png';
    },
  }
};
</script>

<style>
	.commission-detail-container {
		min-height: 100vh;
		background-color: #f8fafe;
		padding-bottom: 120px;
		/* 为底部提现区域预留空间 */
		box-sizing: border-box;
	}

	.content-wrapper {
		padding: 15px;
	}

	/* 加载状态 */
	.loading-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60px 20px;
	}

	.loading-text {
		margin-top: 15px;
		font-size: 14px;
		color: #666;
	}

	/* 错误状态 */
	.error-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60px 20px;
	}

	.error-text {
		margin: 15px 0;
		font-size: 14px;
		color: #666;
		text-align: center;
	}

	/* 空状态 */
	.empty-container {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		padding: 60px 20px;
	}

	.empty-text {
		margin-top: 15px;
		font-size: 14px;
		color: #999;
	}

	/* 通用卡片样式 */
	.post-info-card {
		background-color: #ffffff;
		border-radius: 12px;
		margin-bottom: 15px;
		box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
		overflow: hidden;
		display: flex;
		padding: 10px;
		gap: 15px;
	}

	.info-content {
		flex: 1;
		min-width: 0;
	}

	/* 佣金部分 */
	.commission-section {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.commission-amount {
		display: flex;
		align-items: baseline;
	}

	.amount-symbol {
		font-size: 16px;
		margin-right: 2px;
	}

	.amount-value {
		font-size: 18px;
		font-weight: bold;
	}

	/* 基本信息部分 */
	.basic-info {
		display: flex;
		flex-direction: column;
		gap: 12px;
		margin-bottom: 10px;
	}

	.info-item {
		display: flex;
		align-items: flex-start;
		gap: 10px;
	}

	.info-label {
		font-size: 14px;
		color: #666;
		white-space: nowrap;
		flex-shrink: 0;
	}

	.info-value {
		font-size: 14px;
		color: #333;
		font-weight: 500;
		text-align: right;
		word-break: break-all;
	}

	.info-value.title-text {
		color: #2979ff;
		font-weight: 600;
	}

	/* 图片展示区域 */
	.image-section {
		width: 100px;
		flex-shrink: 0;
	}

	.image-container {
		position: relative;
		width: 100px;
		height: 100px;
		border-radius: 8px;
		overflow: hidden;
		background-color: #f5f5f5;
		cursor: pointer;
	}

	.main-image {
		width: 100%;
		height: 100%;
	}

	.image-count {
		position: absolute;
		bottom: 4px;
		right: 4px;
		background-color: rgba(0, 0, 0, 0.6);
		border-radius: 10px;
		padding: 2px 6px;
		min-width: 20px;
		height: 20px;
		display: flex;
		align-items: center;
		justify-content: center;
	}

	.count-text {
		color: white;
		font-size: 12px;
		font-weight: 500;
		line-height: 1;
	}

	/* 无图片占位符 */
	.no-image-placeholder {
		width: 100px;
		height: 100px;
		border-radius: 8px;
		background-color: #f8f9fa;
		border: 1px dashed #ddd;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		gap: 4px;
	}

	.placeholder-text {
		font-size: 10px;
		color: #999;
		text-align: center;
	}

	/* 底部提现区域 */
	.bottom-withdraw-area {
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

	/* 可提现金额信息 */
	.withdraw-info {
		margin-bottom: 15px;
	}

	.available-amount-section {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.available-label {
		font-size: 14px;
		color: #666;
	}

	.amount-display {
		display: flex;
		align-items: baseline;
	}

	.currency-symbol {
		font-size: 16px;
		color: #3c9cff;
		margin-right: 4px;
		font-weight: 500;
	}

	.amount-number {
		font-size: 20px;
		font-weight: bold;
		color: #3c9cff;
	}

	/* 提现按钮包装器 */
	.withdraw-button-wrapper {
		width: 100%;
	}

	/* 提现按钮样式 */
	.withdraw-button {
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

	.withdraw-button:active {
		transform: translateY(1px);
		box-shadow: 0 2px 8px rgba(41, 121, 255, 0.3);
	}

	.withdraw-button[disabled] {
		background: linear-gradient(135deg, #cccccc, #dddddd);
		box-shadow: none;
	}

	.withdraw-button-text {
		color: white;
		font-size: 16px;
		font-weight: 600;
		text-align: center;
	}

	.withdraw-button[disabled] .withdraw-button-text {
		color: #999999;
	}

  /* 加载更多容器 */
  .load-more-container {
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background-color: #ffffff;
    border-radius: 12px;
    margin-top: 10px;
  }

  .load-more-text {
    font-size: 14px;
    color: #999;
    text-align: center;
  }

  /* 重试按钮 */
  .retry-button {
    margin-top: 15px;
    background-color: #3c9cff;
    color: white;
    border: none;
    border-radius: 20px;
    padding: 8px 20px;
    font-size: 14px;
  }

  /* 确保列表可滚动 */
  .content-wrapper {
    padding: 15px;
    padding-bottom: 140px; /* 为底部提现区域预留更多空间 */
  }
</style>
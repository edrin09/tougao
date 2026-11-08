<template>
  <view class="container" :style="{ paddingTop: statusBarHeight + 'px' }">
    <!-- 导航栏 -->
    <uv-navbar
      title="我的投稿"
      :autoBack="true"
      bgColor="#f0f6fb"
      :border="false"
      :titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }"
      leftIcon="arrow-left"
      leftIconColor="#333333"
      :safeAreaInsetTop="true"
      :fixed="true"
      :placeholder="true"
      :height="44"
    ></uv-navbar>

    <!-- Tab栏 -->
    <view class="tab-bar" :style="{ top: statusBarHeight + 44 + 'px' }">
      <view
        v-for="(tab, index) in filterTabs"
        :key="index"
        class="tab-item"
        :class="{ 'tab-active': currentFilter === tab.value }"
        @click="switchFilter(tab.value)"
      >
        <text>{{ tab.label }}</text>
      </view>
    </view>

    <!-- 投稿列表 -->
    <scroll-view
      class="submission-list"
      scroll-y="true"
      @scrolltolower="loadMore"
      :style="{ height: scrollViewHeight + 'px' }"
    >
      <!-- 每一篇投稿 -->
      <view
        v-for="(post, index) in filteredPosts"
        :key="post.id"
        class="submission-item"
      >
        <!-- 顶部信息 -->
        <view class="submission-header">
          <image
            class="author-avatar"
            :src="userInfo.avatar || '/static/avatar.png'"
          ></image>
          <view class="submission-info">
            <view class="submission-nickname">{{ userInfo.nickname }}</view>
            <view class="submission-time">{{
              (post.created_at)
            }}</view>
          </view>
        </view>

        <view style="display: flex; align-items: center; margin-bottom: 10px">
          <!-- 标签 -->
          <view class="tag-container" v-if="post.tag">
            <text class="tag-text">#{{ post.tag }}</text>
          </view>
          <view class="submission-title">{{ post.title }}</view>
        </view>

        <view v-if="post.is_single" style="margin-bottom: 10px">
          <span style="color: #1a73e8">（单）</span>{{post.channels_name}}：{{post.channel_account}}
        </view>

        <!-- 内容 -->
        <view class="submission-content">{{ post.content }}</view>

        <!-- 联系方式 -->
        <view class="contact-info" v-if="post.contact">
          联系方式: {{ post.contact }}
        </view>

        <!-- 图片 -->
        <view
          class="image-container"
          v-if="post.images && post.images.length > 0"
        >
          <image
            v-for="(img, imgIndex) in post.images"
            :key="imgIndex"
            :src="typeof img === 'string' ? img : img.url"
            class="submission-image"
            mode="aspectFill"
            @click="previewImage(post.images, imgIndex)"
          ></image>
        </view>

        <!-- 操作按钮 -->
        <view class="action-buttons">
			<view class="action-button-left">
				<view v-if="post.is_single">
					<text class="unit">￥</text>{{post.channel_fans_costs}}
				</view>
			</view>
			<view class="action-button-right">
				<!-- 预览按钮 - 所有状态都显示 -->
				<view class="action-btn preview-btn" @click="previewPost(post)">
				  预览
				</view>
				<!-- 删除按钮 - 只有待审核状态显示 -->
				<view
				  v-if="post.status === 'pending'"
				  class="action-btn delete-btn"
				  @click="deletePost(post)"
				>
				  删除
				</view>
			</view>
          
        </view>

        <!-- 状态标签 -->
        <view class="status-badge" :class="getStatusClass(post.status)">
          <text class="status-text">{{ getStatusText(post.status) }}</text>
        </view>
      </view>

      <!-- 加载更多 -->
      <view class="load-more" v-if="hasMore && !loading"> 加载更多... </view>
      <view class="no-more" v-else-if="filteredPosts.length > 0">
        没有更多数据了
      </view>
      <view class="empty-list" v-else-if="!loading">
        <uv-icon name="list" size="50" color="#dddddd"></uv-icon>
        <text>暂无投稿记录</text>
      </view>
    </scroll-view>
    
    <!-- 投稿入口悬浮按钮 -->
    <view class="float-btn" @click="goToSubmit">
      <image
        src="/static/images/tougao.png"
        class="tougao-icon"
        mode="aspectFit"
      />
    </view>
  </view>
</template>

<script>
import api from "../../api";

export default {
  data() {
    return {
      loading: false,
      posts: [],
      currentFilter: "all",
      page: 1,
      pageSize: 10,
      hasMore: true,
      userInfo: {},
      wallOwnerId: "", // 墙主ID参数
      filterTabs: [
        { label: "待审核", value: "pending" },
        { label: "未通过", value: "rejected" },
        { label: "已发布", value: "approved" },
      ],
      scrollViewHeight: 0,
      statusBarHeight: 0,
    };
  },

  computed: {
    // 过滤后的投稿列表
    filteredPosts() {
      return this.posts.filter((post) => post.status === this.currentFilter);
    },
  },

  async onLoad(options) {
    // 设置默认筛选为待审核
    this.currentFilter = "pending";
    
    // 从缓存中获取墙主ID参数，而不是从URL参数中获取
    const cachedWallOwnerId = uni.getStorageSync("wallOwnerId");
    if (cachedWallOwnerId) {
      this.wallOwnerId = cachedWallOwnerId;
      console.log("从缓存获取的墙主ID:", this.wallOwnerId);
    }
    
    await this.getUserInfo();
    await this.loadPosts();
  },

  // 下拉刷新
  async onPullDownRefresh() {
    this.page = 1;
    this.posts = [];
    this.hasMore = true;
    await this.loadPosts();
    uni.stopPullDownRefresh();
  },

  // 上拉加载更多
  async onReachBottom() {
    if (this.hasMore && !this.loading) {
      await this.loadMore();
    }
  },

  onReady() {
    // 获取系统信息
    const systemInfo = uni.getSystemInfoSync();
    // 获取状态栏高度
    this.statusBarHeight = systemInfo.statusBarHeight;
    // 设置scroll-view高度 (屏幕高度 - 状态栏高度 - 导航栏高度[约44px] - 顶部tab栏高度[约40px])
    this.scrollViewHeight =
      systemInfo.windowHeight - this.statusBarHeight - 44 - 40;
  },

  methods: {
    // 获取用户信息
    async getUserInfo() {
      try {
        const result = await api.user.getUserInfo();
        if (result.code === 200) {
          this.userInfo = result.data;
        }
      } catch (error) {
        console.error("获取用户信息失败:", error);
      }
    },

    // 加载投稿列表
    async loadPosts() {
      if (this.loading) return;

      try {
        this.loading = true;

        const requestParams = {
          page: this.page,
          pageSize: this.pageSize,
        };

        // 如果有墙主ID，添加到请求参数中
        if (this.wallOwnerId) {
          requestParams.wallOwnerId = this.wallOwnerId;
        }

        const result = await api.post.getMyPosts(requestParams);

        if (result.code === 200) {
          const newPosts = result.data.list || [];

          if (this.page === 1) {
            this.posts = newPosts;
          } else {
            this.posts.push(...newPosts);
          }

          // 判断是否还有更多数据
          this.hasMore = newPosts.length === this.pageSize;
        } else {
          uni.showToast({
            title: result.message || "加载失败",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("加载投稿列表失败:", error);
        uni.showToast({
          title: "加载失败",
          icon: "none",
        });
      } finally {
        this.loading = false;
      }
    },

    // 加载更多
    async loadMore() {
      this.page++;
      await this.loadPosts();
    },

    // 切换筛选条件
    switchFilter(filter) {
      // 若点到当前筛选，无需处理
      if (this.currentFilter === filter) return;

      this.currentFilter = filter;

      // 重置分页参数并请求最新数据
      this.page = 1;
      this.posts = [];
      this.hasMore = true;

      this.loadPosts();
    },

    // 获取状态样式类
    getStatusClass(status) {
      const classMap = {
        pending: "status-pending",
        approved: "status-approved",
        rejected: "status-rejected",
      };
      return classMap[status] || "status-pending";
    },

    // 获取状态文本
    getStatusText(status) {
      const textMap = {
        pending: "待审核",
        approved: "已通过",
        rejected: "已拒绝",
      };
      return textMap[status] || "未知";
    },

    // 预览图片
    previewImage(images, index) {
      const urls = images.map((img) =>
        typeof img === "string" ? img : img.url
      );
      uni.previewImage({
        urls: urls,
        current: urls[index],
      });
    },

    // 格式化时间
    formatTime(timeStr) {
      const time = new Date(timeStr);
      const now = new Date();
      const diff = now - time;

      const minute = 60 * 1000;
      const hour = 60 * minute;
      const day = 24 * hour;

      if (diff < minute) {
        return "刚刚";
      } else if (diff < hour) {
        return Math.floor(diff / minute) + "分钟前";
      } else if (diff < day) {
        return Math.floor(diff / hour) + "小时前";
      } else if (diff < 7 * day) {
        return Math.floor(diff / day) + "天前";
      } else {
        return time.toLocaleDateString();
      }
    },

    // 查看投稿详情
    viewPostDetail(post) {
      uni.navigateTo({
        url: `/pages/post-detail/post-detail?id=${post.id}`,
      });
    },

    // 预览投稿 - 直接预览海报图片
    previewPost(post) {
      if (post.post_image) {
        // 直接预览海报图片，就像投稿审核页面一样
        uni.previewImage({
          urls: [post.post_image],
          current: post.post_image,
        });
      } else {
        uni.showToast({
          title: "暂无海报图片",
          icon: "none",
        });
      }
    },

    // 删除投稿
    deletePost(post) {
      uni.showModal({
        title: "确认删除",
        content: "确定要删除这条投稿吗？删除后无法恢复。",
        success: async (res) => {
          if (res.confirm) {
            await this.confirmDelete(post.id);
          }
        },
      });
    },

    // 确认删除
    async confirmDelete(postId) {
      try {
        const result = await api.post.deletePost(postId);

        if (result.code === 200) {
          uni.showToast({
            title: "删除成功",
            icon: "success",
          });

          // 从列表中移除
          this.posts = this.posts.filter((post) => post.id !== postId);
        } else {
          uni.showToast({
            title: result.message || "删除失败",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("删除投稿失败:", error);
        uni.showToast({
          title: "删除失败",
          icon: "none",
        });
      }
    },

    // 去投稿
    goToSubmit() {
      const wallOwnerId = uni.getStorageSync("wallOwnerId");
      uni.reLaunch({
        url: "/pages/index/index?wallOwnerId=" + wallOwnerId
      });
    },
  },
};
</script>

<style>
.container {
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

/* 投稿列表样式 */
.submission-list {
  flex: 1;
  background-color: #f5f5f5;
  padding: 10px;
  box-sizing: border-box;
  margin-top: 20px;
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

.submission-title {
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-left: 10px;
  /* margin-bottom: 5px; */
}

.submission-time {
  font-size: 12px;
  color: #999;
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

.submission-channels{
	padding: 3px 8px;
	border-radius: 4px;
	font-size: 12px;
	display: inline;
	background-color: #f5f5f5;
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

.delete-btn {
  background-color: #fae3e3;
  color: #ff4d4f;
}

/* 状态标签 */
.status-badge {
  position: absolute;
  top: 15px;
  right: 15px;
  padding: 4px 8px;
  border-radius: 4px;
  font-size: 12px;
}

.status-pending {
  background-color: #fff3cd;
  color: #856404;
}

.status-approved {
  background-color: #d4edda;
  color: #155724;
}

.status-rejected {
  background-color: #f8d7da;
  color: #721c24;
}

.status-text {
  font-size: 12px;
}

/* 加载更多 */
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

/* 投稿入口悬浮按钮 */
.float-btn {
  position: fixed;
  bottom: 30px;
  right: 30px;
  width: 60px;
  height: 60px;
  border-radius: 50%;
  /* background-color: #ffffff; */
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  z-index: 100;
}

.float-btn:active {
  transform: scale(0.95);
}

.tougao-icon {
  width: 30px;
  height: 30px;
}
</style>

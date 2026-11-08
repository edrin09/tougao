<template>
  <view class="comment-page">
    <!-- uv-navbar导航栏 -->
    <uv-navbar
      title="匿名评论区"
      bgColor="#f0f6fb"
      :border="false"
      :titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }"
      leftIcon="arrow-left"
      leftIconColor="#333333"
      :safeAreaInsetTop="true"
      :fixed="true"
      :placeholder="true"
      :height="44"
      @leftClick="goToHome"
    >
      <template #left>
        <view class="navbar-left-slot" @click="goToHome">
          <image
            src="/static/images/tougao.png"
            class="navbar-icon"
            mode="aspectFit"
          />
        </view>
      </template>
    </uv-navbar>

    <!-- 使用封装的用户信息授权弹窗组件 -->
    <user-auth-modal
      v-if="showAuthModal"
      @cancel="onCancelAuth"
      @confirm="onConfirmAuth"
    ></user-auth-modal>

    <!-- 头部信息 -->
    <!-- <view class="header" :style="{ marginTop: (statusBarHeight + 44) + 'px' }">
      <view class="wall-info">
        <image
          v-if="wallInfo.owner_avatar"
          :src="wallInfo.owner_avatar"
          class="avatar"
          mode="aspectFill"
        />
        <view v-else class="avatar-placeholder">
          <text class="avatar-text">{{ wallInfo.wall_name.charAt(0) }}</text>
        </view>
        <view class="info">
          <text class="wall-name">{{ wallInfo.wall_name }}</text>
          <text class="description">{{ wallInfo.description || '欢迎来到评论区' }}</text>
        </view>
      </view>
    </view> -->

    <!-- 评论列表 -->
    <scroll-view
      class="comment-scroll"
      scroll-y="true"
      :refresher-enabled="true"
      :refresher-triggered="refreshing"
      @refresherrefresh="onRefresh"
      @scrolltolower="onScrollToLower"
      :lower-threshold="100"
    >
      <view class="comment-list">
        <view
          v-for="comment in commentList"
          :key="comment.id"
          class="comment-item"
        >
          <view class="comment-header">
            <image
              v-if="comment.avatar_url"
              :src="comment.avatar_url"
              class="user-avatar"
              mode="aspectFill"
            />
            <view v-else class="user-avatar-placeholder">
              <text class="user-avatar-text">{{
                comment.nickname.charAt(0)
              }}</text>
            </view>
            <view class="user-info">
              <text class="nickname">{{ comment.nickname }}</text>
              <text class="time">{{ comment.create_time_formatted }}</text>
            </view>
            <view
              class="delete-btn"
              v-if="canDeleteComment(comment)"
              @click="deleteComment(comment.id)"
            >
              删除
            </view>
          </view>

          <view class="comment-content">
            <text class="content-text">{{ comment.content }}</text>
            <view
              v-if="comment.images && comment.images.length > 0"
              class="comment-images"
            >
              <image
                v-for="(image, index) in comment.images"
                :key="index"
                :src="image"
                class="comment-image"
                mode="aspectFill"
                @click="previewImage(comment.images, index)"
              />
            </view>

            <!-- 回复按钮 -->
            <view class="comment-actions">
              <view class="reply-btn" @click="showReplyInput(comment)">
                <text class="reply-text">回复</text>
              </view>
            </view>
          </view>

          <!-- 回复列表 -->
          <view
            v-if="comment.replies && comment.replies.length > 0"
            class="replies-section"
          >
            <view
              v-for="reply in comment.replies"
              :key="reply.id"
              class="reply-item"
            >
              <view class="reply-header">
                <image
                  v-if="reply.avatar_url"
                  :src="reply.avatar_url"
                  class="reply-avatar"
                  mode="aspectFill"
                />
                <view v-else class="reply-avatar-placeholder">
                  <text class="reply-avatar-text">{{
                    reply.nickname.charAt(0)
                  }}</text>
                </view>
                <view class="reply-info">
                  <text class="reply-nickname">{{ reply.nickname }}</text>
                  <text v-if="reply.reply_to_nickname" class="reply-to"
                    >回复 {{ reply.reply_to_nickname }}</text
                  >
                  <text class="reply-time">{{
                    reply.create_time_formatted
                  }}</text>
                </view>
                <view
                  class="reply-delete-btn"
                  v-if="canDeleteComment(reply)"
                  @click="deleteComment(reply.id)"
                >
                  删除
                </view>
              </view>

              <view class="reply-content">
                <text class="reply-text">{{ reply.content }}</text>
                <view
                  v-if="reply.images && reply.images.length > 0"
                  class="reply-images"
                >
                  <image
                    v-for="(image, index) in reply.images"
                    :key="index"
                    :src="image"
                    class="reply-image"
                    mode="aspectFill"
                    @click="previewImage(reply.images, index)"
                  />
                </view>
              </view>
            </view>
          </view>
        </view>

        <!-- 加载更多 -->
        <view v-if="hasMore" class="load-more">
          <text>{{ loading ? "加载中..." : "上拉加载更多" }}</text>
        </view>

        <!-- 没有更多数据 -->
        <view v-else-if="commentList.length > 0" class="no-more">
          <text>已全部加载</text>
        </view>

        <!-- 空状态 -->
        <view v-if="commentList.length === 0 && !loading" class="empty-state">
          <text class="empty-text">还没有评论，快来抢沙发吧~</text>
        </view>
      </view>
    </scroll-view>

    <!-- 底部输入框 -->
    <view class="input-section" v-if="showInput">
      <!-- 回复提示 -->
      <view v-if="replyTarget" class="reply-hint">
        <text class="reply-hint-text">回复 {{ replyTarget.nickname }}:</text>
        <view class="cancel-reply" @click="cancelReply">
          <text class="cancel-text">×</text>
        </view>
      </view>

      <view class="input-container">
        <textarea
          ref="commentInput"
          v-model="inputContent"
          :placeholder="
            replyTarget
              ? `回复 ${replyTarget.nickname}`
              : '良言一句三冬暖,恶语伤人六月寒'
          "
          class="input-textarea"
          :maxlength="500"
          auto-height
          @focus="onInputFocus"
          @blur="onInputBlur"
        />
        <view class="input-actions">
          <view class="anonymous-toggle">
            <text class="anonymous-text">匿名评论</text>
            <switch
              :checked="isAnonymous"
              @change="onAnonymousChange"
              color="#2979ff"
              style="transform: scale(0.8)"
            />
          </view>
          <view
            class="send-btn"
            @click="sendComment"
            :class="{ disabled: !canSend }"
          >
            <text class="send-text">发布</text>
          </view>
        </view>
      </view>
    </view>

    <!-- 底部触发区域 -->
    <view class="input-trigger" v-if="!showInput" @click="showInputArea">
      <view class="trigger-content">
        <text class="trigger-text">说点什么...</text>
      </view>
    </view>
  </view>
</template>

<script>
import api from "../../api";
// 导入授权弹窗组件
import userAuthModal from "@/components/user-auth-modal.vue";
// 导入登录相关方法
import { doLogin, checkAuth } from "../../api/auth";

export default {
  // 注册组件
  components: {
    userAuthModal,
  },
  data() {
    return {
      wallOwnerId: "",
      publishId:'',
      wallInfo: {
        wall_name: "评论区",
        description: "",
        owner_avatar: "",
        theme_color: "#2979ff",
      },
      commentList: [],
      inputContent: "",
      selectedImages: [],
      loading: false,
      hasMore: true,
      page: 1,
      limit: 20,
      isWallOwner: false,
      currentUserOpenid: "",
      replyTarget: null, // 回复目标

      userInfo: {}, // 用户信息
      showAuthModal: false, // 显示授权弹窗
      refreshing: false, // 下拉刷新状态
      showInput: false, // 是否显示输入框
      isAnonymous: false, // 是否匿名评论
    };
  },

  computed: {
    canSend() {
      return (
        this.inputContent.trim().length > 0 || this.selectedImages.length > 0
      );
    },
  },

  onLoad(options) {
    this.wallOwnerId = options.wallOwnerId;
    this.publishId = options.publishId;
    if (this.wallOwnerId) {
      uni.setStorageSync("wallOwnerId", this.wallOwnerId);
    } else {
      // reLaunch 到首页
      uni.reLaunch({
        url: "/pages/index/index",
      });
    }

    // 执行登录逻辑
    this.doLogin(options);
  },

  onShow() {
    // 检查是否需要显示授权弹窗
    const userInfo = uni.getStorageSync("userInfo");
    this.showAuthModal = !userInfo || !userInfo.avatar;

    // 监听授权状态变化
    uni.$on("userInfoUpdated", (userInfo) => {
      this.showAuthModal = !userInfo || !userInfo.avatar;
    });
  },

  methods: {
    // 执行登录逻辑
    async doLogin(options) {
      // 检查是否已授权
      if (!checkAuth()) {
        // 未授权，执行登录
        await doLogin();
      }

      // 初始化页面
      this.initPage(options);
    },

    // 页面初始化
    initPage(options) {
      // 加载用户信息
      this.loadUserInfo();
      // 检查授权状态
      this.checkAuthStatus();

      if (this.wallOwnerId) {
        this.loadWallInfo();
        this.loadComments().then(() => {
          // 初始加载完成后滚动到底部
          setTimeout(() => {
            this.scrollToBottom();
          }, 500);
        });
        this.checkWallOwner();
        this.getCurrentUserOpenid();
      }
    },

    // 加载墙主信息
    async loadWallInfo() {
      try {
        const res = await api.comment.getWallInfo({
          wallOwnerId: this.wallOwnerId,
        });
        if (res.code === 200) {
          this.wallInfo = res.data;
          // 设置页面标题
          uni.setNavigationBarTitle({
            title: this.wallInfo.wall_name + " - 评论区",
          });
        }
      } catch (error) {
        console.error("加载墙主信息失败:", error);
      }
    },

    // 加载评论列表
    async loadComments(isLoadMore = false) {
      if (this.loading) return Promise.resolve();

      this.loading = true;
      try {
        console.log("开始加载评论", {
          wallOwnerId: this.wallOwnerId,
          publishId: this.publishId,
          page: this.page,
          limit: this.limit,
        });

        const res = await api.comment.getCommentList({
          wallOwnerId: this.wallOwnerId,
          postId: this.publishId,
          page: this.page,
          limit: this.limit,
        });

        console.log("评论API响应:", res);

        if (res && res.code === 200) {
          const newComments = res.data && res.data.list ? res.data.list : [];
          console.log("获取到评论数据:", newComments);

          if (isLoadMore) {
            this.commentList = [...this.commentList, ...newComments];
          } else {
            this.commentList = newComments;
          }
          this.hasMore = res.data && res.data.hasMore;
          return Promise.resolve();
        } else {
          console.error("API返回错误:", res);
          uni.showToast({
            title: res && res.message ? res.message : "获取评论失败",
            icon: "none",
          });
          return Promise.reject(
            new Error(res && res.message ? res.message : "获取评论失败")
          );
        }
      } catch (error) {
        console.error("加载评论失败:", error);
        uni.showToast({
          title: "网络错误: " + (error.message || "未知错误"),
          icon: "none",
        });
        return Promise.reject(error);
      } finally {
        this.loading = false;
      }
    },

    // 加载更多
    loadMore() {
      if (this.hasMore && !this.loading) {
        this.page++;
        this.loadComments(true);
      }
    },

    // 下拉刷新
    async onRefresh() {
      this.refreshing = true;
      try {
        this.page = 1;
        this.hasMore = true;
        await this.loadComments();
        uni.showToast({
          title: "刷新成功",
          icon: "success",
          duration: 1000,
        });
      } catch (error) {
        console.error("刷新失败:", error);
      } finally {
        this.refreshing = false;
      }
    },

    // 滚动到底部触发加载更多
    onScrollToLower() {
      if (this.hasMore && !this.loading) {
        this.loadMore();
      }
    },

    // 检查是否为墙主
    checkWallOwner() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.isWallOwner = userInfo.userId === this.wallOwnerId;
    },

    // 获取当前用户openid
    getCurrentUserOpenid() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.currentUserOpenid = userInfo.openid || userInfo.openId || "";
    },

    // 判断是否可以删除评论
    canDeleteComment(comment) {
      // 墙主可以删除任意评论，用户可以删除自己的评论
      return this.isWallOwner || comment.openid === this.currentUserOpenid;
    },

    // 显示输入区域
    showInputArea() {
      this.showInput = true;
      this.$nextTick(() => {
        // 聚焦到输入框
        if (this.$refs.commentInput) {
          this.$refs.commentInput.focus();
        }
      });
    },

    // 输入框获取焦点
    onInputFocus() {
      this.showInput = true;
    },

    // 输入框失去焦点
    onInputBlur() {
      // 延迟隐藏，避免点击发送按钮时输入框消失
      setTimeout(() => {
        if (!this.inputContent.trim() && !this.replyTarget) {
          this.showInput = false;
        }
      }, 200);
    },

    // 匿名评论切换
    onAnonymousChange(e) {
      this.isAnonymous = e.detail.value;
    },

    // 生成随机用户名
    generateRandomUserName() {
      // 生成4位随机数字
      const randomNum = Math.floor(1000 + Math.random() * 9000);
      return `用户${randomNum}`;
    },

    // 显示回复输入框
    showReplyInput(comment) {
      this.replyTarget = comment;
      this.showInput = true;
      this.$nextTick(() => {
        if (this.$refs.commentInput) {
          this.$refs.commentInput.focus();
        }
      });
    },

    // 取消回复
    cancelReply() {
      this.replyTarget = null;
      if (!this.inputContent.trim()) {
        this.showInput = false;
      }
    },

    // 选择图片
    chooseImage() {
      const maxImages = 9 - this.selectedImages.length;
      if (maxImages <= 0) {
        uni.showToast({
          title: "最多只能选择9张图片",
          icon: "none",
        });
        return;
      }

      uni.chooseImage({
        count: maxImages,
        sizeType: ["compressed"],
        sourceType: ["album", "camera"],
        success: (res) => {
          this.selectedImages = [...this.selectedImages, ...res.tempFilePaths];
        },
      });
    },

    // 移除图片
    removeImage(index) {
      this.selectedImages.splice(index, 1);
    },

    // 发送评论
    async sendComment() {
      if (!this.canSend) return;

      // 检查用户授权
      if (!this.userInfo.avatar) {
        this.showAuthModal = true;
        uni.showToast({
          title: "请先授权登录",
          icon: "none",
        });
        return;
      }

      try {
        // 获取模板id
        const templates = await api.wallowner.getReplyNoticeTemplate();
        const templateId = templates.data.template_id;
        console.log("模板id:", templateId);
        uni.requestSubscribeMessage({
          tmplIds: [templateId],
          success: (res) => {
            console.log("订阅模板消息成功:", res);
          },
          fail: (err) => {
            console.error("订阅模板消息失败:", err);
          },
          complete: async () => {
            console.log("订阅模板消息完成");
            uni.showLoading({ title: "发送中..." });
            // 上传图片
            let imageUrls = [];
            if (this.selectedImages.length > 0) {
              for (const imagePath of this.selectedImages) {
                const uploadRes = await api.upload.uploadImage(imagePath);
                if (uploadRes.code === 200) {
                  imageUrls.push(uploadRes.data.url);
                }
              }
            }

            // 获取头像URL
            let avatarUrl = "";
            if (this.isAnonymous) {
              // 匿名评论使用随机头像
              try {
                const avatarResponse = await api.user.getRandomAvatar();
                if (avatarResponse.code === 200) {
                  avatarUrl = avatarResponse.data.avatar_url;
                }
              } catch (error) {
                console.error("获取随机头像失败:", error);
              }
            } else {
              // 非匿名使用用户头像
              avatarUrl = this.userInfo.avatar || "";
            }

            // 构建评论数据
            const commentData = {
              wallOwnerId: this.wallOwnerId,
              content: this.inputContent.trim(),
              images: imageUrls,
              nickname: this.isAnonymous
                ? this.generateRandomUserName()
                : this.userInfo.nickname || this.generateRandomUserName(),
              avatarUrl: avatarUrl,
              openid: this.userInfo.openid || this.userInfo.openId || "",
              postId: this.publishId,
            };

            // 如果是回复
            if (this.replyTarget) {
              commentData.parentId = this.replyTarget.id;
              commentData.replyToOpenid = this.replyTarget.openid;
              commentData.replyToNickname = this.replyTarget.nickname;
              
            }

            // 发送评论
            const res = await api.comment.addComment(commentData);

            if (res.code === 200) {
              uni.showToast({
                title: "评论成功,请等待审核",
                icon: "success",
              });

              // 清空输入
              this.inputContent = "";
              this.selectedImages = [];
              this.replyTarget = null;
              this.showInput = false;
              this.isAnonymous = false;

              // 重新加载评论列表，并滚动到底部
              this.page = 1;
              this.loadComments().then(() => {
                this.scrollToBottom();
              });
            } else {
              uni.showToast({
                title: res.message || "评论失败",
                icon: "none",
              });
            }
          },
        });
      } catch (error) {
        console.error("发送评论失败:", error);
        uni.showToast({
          title: "发送失败",
          icon: "none",
        });
      } finally {
        uni.hideLoading();
      }
    },

    // 删除评论
    async deleteComment(commentId) {
      uni.showModal({
        title: "确认删除",
        content: "确定要删除这条评论吗？",
        success: async (res) => {
          if (res.confirm) {
            try {
              const result = await api.comment.deleteComment({
                commentId,
                wallOwnerId: this.wallOwnerId,
                openid: this.userInfo.openid || this.userInfo.openId || "",
              });
              if (result.code === 200) {
                uni.showToast({
                  title: "删除成功",
                  icon: "success",
                });
                // 重新加载评论列表
                this.page = 1;
                this.loadComments();
              } else {
                uni.showToast({
                  title: result.message || "删除失败",
                  icon: "none",
                });
              }
            } catch (error) {
              console.error("删除评论失败:", error);
              uni.showToast({
                title: "删除失败",
                icon: "none",
              });
            }
          }
        },
      });
    },

    // 预览图片
    previewImage(urls, current) {
      uni.previewImage({
        urls: urls,
        current: current,
      });
    },

    // 滚动到底部
    scrollToBottom() {
      this.$nextTick(() => {
        uni.pageScrollTo({
          scrollTop: 999999,
          duration: 300,
        });
      });
    },

    // 前往首页
    goToHome() {
      uni.redirectTo({
        url: `/pages/index/index?wallOwnerId=${this.wallOwnerId}`,
      });
    },

    // 返回上一页
    goBack() {
      uni.navigateBack();
    },

    // 加载用户信息
    loadUserInfo() {
      const userInfo = uni.getStorageSync("userInfo") || {};
      this.userInfo = userInfo;
    },

    // 检查授权状态
    checkAuthStatus() {
      // 检查是否需要显示授权弹窗
      const userInfo = uni.getStorageSync("userInfo");
      this.showAuthModal = !userInfo || !userInfo.avatar;

      // 监听授权状态变化
      uni.$on("userInfoUpdated", (userInfo) => {
        this.showAuthModal = !userInfo || !userInfo.avatar;
        // 可以在这里处理用户信息更新后的逻辑
        console.log("用户信息已更新", userInfo);
      });
    },

    // 显示用户信息
    showUserInfo() {
      this.showAuthModal = true;
    },

    // 取消授权回调
    onCancelAuth() {
      this.showAuthModal = false;
    },

    // 确认授权回调
    async onConfirmAuth(userInfo) {
      try {
        // 更新用户信息
        const result = await api.user.updateUserInfo({
          nickname: userInfo.nickName,
          avatar: userInfo.avatarUrl,
        });

        if (result.code === 200) {
          // API调用成功后保存用户信息
          uni.setStorageSync("userInfo", result.data);
          this.userInfo = result.data;
          this.currentUserOpenid = result.data.openid;
          this.showAuthModal = false;
        } else {
          uni.showToast({
            title: result.message || "更新用户信息失败",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("更新用户信息失败:", error);
        uni.showToast({
          title: "更新用户信息失败",
          icon: "none",
        });
      }
    },
  },
};
</script>

<style scoped>
.comment-page {
  height: 100vh;
  background-color: #f5f5f5;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

/* uv-navbar自定义样式 */
.navbar-left-slot {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 44px;
  height: 44px;
}

.navbar-icon {
  width: 24px;
  height: 24px;
}

/* 滚动容器 */
.comment-scroll {
  flex: 1;
  overflow: hidden;
}

/* 评论列表容器 */
.comment-list {
  padding: 0 20px;
  min-height: 100%;
  padding: 16px 0;
  background-color: #f0f6fb;
}

/* 头部信息 */
.header {
  background-color: #ffffff;
  padding: 20px;
  border-bottom: 1px solid #f0f0f0;
}

.wall-info {
  display: flex;
  align-items: center;
}

.avatar {
  width: 50px;
  height: 50px;
  border-radius: 25px;
  margin-right: 12px;
}

.avatar-placeholder {
  width: 50px;
  height: 50px;
  border-radius: 25px;
  background-color: #2979ff;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 12px;
}

.avatar-text {
  color: #ffffff;
  font-size: 18px;
  font-weight: bold;
}

.info {
  flex: 1;
}

.wall-name {
  font-size: 18px;
  font-weight: bold;
  color: #333333;
  display: block;
  margin-bottom: 4px;
}

.description {
  font-size: 14px;
  color: #666666;
}

.comment-item {
  /* background-color: #ffffff; */
  border-radius: 12px;
  padding: 16px;
  margin: 12px 0;
  /* box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); */
}

.comment-header {
  display: flex;
  align-items: center;
  margin-bottom: 12px;
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 18px;
  margin-right: 10px;
}

.user-avatar-placeholder {
  width: 36px;
  height: 36px;
  border-radius: 18px;
  background-color: #cccccc;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 10px;
}

.user-avatar-text {
  color: #ffffff;
  font-size: 14px;
  font-weight: bold;
}

.user-info {
  flex: 1;
}

.nickname {
  font-size: 15px;
  font-weight: 500;
  color: #333333;
  display: block;
  margin-bottom: 2px;
}

.time {
  font-size: 12px;
  color: #999999;
}

.delete-btn {
  padding: 4px 8px;
  background-color: #ff4757;
  border-radius: 4px;
  font-size: 12px;
  color: #ffffff;
}

.comment-content {
  margin-left: 46px;
}

.content-text {
  font-size: 15px;
  line-height: 1.5;
  color: #333333;
  word-break: break-all;
}

.comment-images {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.comment-image {
  width: 80px;
  height: 80px;
  border-radius: 8px;
}

/* 评论操作 */
.comment-actions {
  margin-top: 8px;
}

.reply-btn {
  display: inline-block;
  padding: 4px 8px;
  background-color: #f0f0f0;
  border-radius: 12px;
  font-size: 12px;
  color: #666666;
}

.reply-text {
  font-size: 12px;
  color: #666666;
}

/* 回复区域 */
.replies-section {
  margin-top: 12px;
  margin-left: 46px;
  padding-left: 12px;
  border-left: 2px solid #f0f0f0;
}

.reply-item {
  margin-bottom: 12px;
  padding: 8px;
  background-color: #f8f9fa;
  border-radius: 8px;
}

.reply-header {
  display: flex;
  align-items: center;
  margin-bottom: 6px;
}

.reply-avatar {
  width: 24px;
  height: 24px;
  border-radius: 12px;
  margin-right: 8px;
}

.reply-avatar-placeholder {
  width: 24px;
  height: 24px;
  border-radius: 12px;
  background-color: #cccccc;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 8px;
}

.reply-avatar-text {
  color: #ffffff;
  font-size: 10px;
  font-weight: bold;
}

.reply-info {
  flex: 1;
}

.reply-nickname {
  font-size: 13px;
  font-weight: 500;
  color: #333333;
  margin-right: 8px;
}

.reply-to {
  font-size: 12px;
  color: #2196f3;
  margin-right: 8px;
}

.reply-time {
  font-size: 11px;
  color: #999999;
}

.reply-delete-btn {
  padding: 2px 6px;
  background-color: #ff4757;
  border-radius: 3px;
  font-size: 10px;
  color: #ffffff;
}

.reply-content {
  margin-left: 32px;
}

.reply-text {
  font-size: 13px;
  line-height: 1.4;
  color: #333333;
  word-break: break-all;
}

.reply-images {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 8px;
}

.reply-image {
  width: 60px;
  height: 60px;
  border-radius: 6px;
}

/* 加载状态 */
.load-more,
.no-more {
  text-align: center;
  padding: 20px;
  color: #999999;
  font-size: 14px;
}

.empty-state {
  text-align: center;
  padding: 60px 20px;
}

.empty-text {
  color: #999999;
  font-size: 16px;
}

/* 底部触发区域 */
.input-trigger {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #f0f6fb;
  border-top: 1px solid #f0f0f0;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
}

.trigger-content {
  background-color: #f5f5f5;
  border-radius: 20px;
  padding: 12px 16px;
  display: flex;
  align-items: center;
}

.trigger-text {
  color: #999999;
  font-size: 15px;
}

/* 底部输入区域 */
.input-section {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  background-color: #ffffff;
  border-top: 1px solid #f0f0f0;
  padding: 12px 16px;
  padding-bottom: calc(12px + env(safe-area-inset-bottom));
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

/* 回复提示 */
.reply-hint {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 12px;
  background-color: #e3f2fd;
  border-radius: 8px;
  margin-bottom: 8px;
}

.reply-hint-text {
  font-size: 13px;
  color: #1976d2;
  flex: 1;
}

.cancel-reply {
  width: 20px;
  height: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #1976d2;
  border-radius: 10px;
}

.cancel-text {
  color: #ffffff;
  font-size: 14px;
  font-weight: bold;
}

.input-container {
  background-color: #f8f9fa;
  border-radius: 20px;
  padding: 12px 16px;
  min-height: 44px;
}

.input-textarea {
  width: 100%;
  font-size: 15px;
  line-height: 1.4;
  max-height: 100px;
  background-color: transparent;
  border: none;
  outline: none;
  margin-bottom: 8px;
}

.input-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.anonymous-toggle {
  display: flex;
  align-items: center;
  gap: 8px;
}

.anonymous-text {
  font-size: 14px;
  color: #666666;
}

.send-btn {
  padding: 8px 20px;
  background-color: #2979ff;
  border-radius: 20px;
  transition: all 0.3s ease;
  min-width: 60px;
  text-align: center;
}

.send-btn.disabled {
  background-color: #cccccc;
}

.send-text {
  color: #ffffff;
  font-size: 15px;
  font-weight: 500;
}
</style>

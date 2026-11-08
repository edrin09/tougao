<template>
  <view v-if="visible" class="modal-overlay" @click="handleOverlayClick">
    <view class="modal-wrapper" @click.stop>
      <view class="modal-container">
        <!-- 标题区域 -->
        <view class="header-section">
          <!-- <text class="success-icon">✅</text> -->
          <text class="header-text">
            图文已保存，请发布图文后，再返回小程序点击下方按钮复制你的专属投稿链接，放到评论区，让同学自助投稿，解放双手~
          </text>
        </view>

        <!-- 显示短链 -->
        <view class="link-section" v-if="commentAreaLink">
          <view class="link-label">墙主专属投稿链接：</view>
          <view class="link-content" @click="copyLink">
            <text class="link-text">👉点击一键投稿到多个渠道 {{ commentAreaLink }}</text>
            <view class="copy-icon">📋</view>
          </view>
        </view>

        <!-- 操作按钮 -->
        <view class="action-button" @click="handleCopyCommentAreaLink">
          <text class="button-text">{{ commentAreaLink ? '复制链接' : '获取墙主专属投稿口令链接' }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import api from "../api/index.js";

export default {
  name: "CommentAreaLinkModal",
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    commentUrl: {
      type: String,
      default: '',
    },
  },
  data() {
    return {
      commentAreaLink: '', // 存储评论区链接
    };
  },

  mounted() {
    // 组件挂载时自动获取链接
    if (this.visible) {
      this.commentAreaLink = this.commentUrl;
      // this.loadCommentAreaLink();
    }
  },

  watch: {
    visible(newVal) {
      if (newVal) {
        this.commentAreaLink = this.commentUrl;
        // this.loadCommentAreaLink();
      }
    }
  },

  methods: {
    // 加载评论区链接
    async loadCommentAreaLink() {
      try {
        // 获取用户信息
        const userInfo = uni.getStorageSync("userInfo") || {};
        if (!userInfo.userId) {
          return;
        }

        // 获取墙主设置，直接使用 comment_area_short_link 字段
        const res = await api.wallowner.getWallSettings(userInfo.wall_id);

        if (res.code === 200 && res.data && res.data.settings) {
          const commentAreaLink = res.data.settings.miniapp_short_link;
          if (commentAreaLink && commentAreaLink.trim()) {
            this.commentAreaLink = commentAreaLink;
          }
        }
      } catch (error) {
        console.error("加载评论区链接失败:", error);
      }
    },

    // 复制链接（点击链接区域时调用）
    async copyLink() {
      if (this.commentAreaLink) {
        try {
          await uni.setClipboardData({
            data: `👉点击一键投稿到多个渠道 ${this.commentAreaLink}`,
          });
          uni.showToast({
            title: "链接已复制",
            icon: "success",
          });
        } catch (error) {
          uni.showToast({
            title: "复制失败",
            icon: "none",
          });
        }
      }
    },

    // 处理复制评论区链接
    async handleCopyCommentAreaLink() {
      if (this.commentAreaLink) {
        // 如果已有链接，直接复制
        await this.copyLink();
        // uni.showModal({
        //   title: "复制成功",
        //   content: "公共评论区链接已复制到剪贴板",
        //   showCancel: false,
        //   confirmText: "知道了",
        //   success: () => {
        //     // 复制成功后关闭弹窗
        //     this.handleClose();
        //   }
        // });
        uni.showToast({
          title: "墙主专属投稿口令链接已复制到剪贴板",
          icon: "none",
        });
        this.handleClose();
      } else {
        // 如果没有链接，重新加载
        uni.showLoading({
          title: "获取中...",
        });

        await this.loadCommentAreaLink();
        uni.hideLoading();

        if (this.commentAreaLink) {
          await this.copyLink();
          uni.showModal({
            title: "复制成功",
            content: "墙主专属投稿口令链接已复制到剪贴板",
            showCancel: false,
            confirmText: "知道了",
            success: () => {
              this.handleClose();
            }
          });
        } else {
          uni.showToast({
            title: "墙主专属投稿口令链接尚未生成",
            icon: "none",
          });
        }
      }
    },

    // 关闭弹层
    handleClose() {
      this.$emit("close");
    },

    // 点击遮罩层关闭
    handleOverlayClick() {
      this.handleClose();
    },
  },
};
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  z-index: 10000;
  padding: 20px;
  gap: 15px;
}

.modal-wrapper {
  background-color: #efeaea;
  border-radius: 20px;
  padding: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  min-height: 300px;
  max-height: 400px;
  width: 100%;
  max-width: 380px;
  overflow: hidden;
}

.modal-container {
  background-color: #ffffff;
  border-radius: 16px;
  width: 100%;
  height: 100%;
  position: relative;
  overflow-y: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.header-section {
  padding: 24px 20px 20px;
  background-color: #ffffff;
  display: flex;
  align-items: flex-start;
  gap: 8px;
  flex: 1;
}

.success-icon {
  font-size: 20px;
  line-height: 1;
  margin-top: 2px;
}

.header-text {
  font-size: 15px;
  color: #333333;
  line-height: 1.4;
  font-weight: 500;
  flex: 1;
}

.link-section {
  padding: 0 20px 20px;
  background-color: #ffffff;
}

.link-label {
  font-size: 14px;
  color: #666666;
  margin-bottom: 8px;
  font-weight: 500;
}

.link-content {
  background-color: #f8f9fa;
  border: 1px solid #e9ecef;
  border-radius: 8px;
  padding: 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  transition: all 0.2s ease;
}

.link-content:active {
  background-color: #e9ecef;
  transform: scale(0.98);
}

.link-text {
  font-size: 14px;
  color: #333333;
  flex: 1;
  word-break: break-all;
  line-height: 1.4;
  margin-right: 8px;
}

.copy-icon {
  font-size: 16px;
  color: #666666;
  flex-shrink: 0;
}

.action-button {
  background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
  border-radius: 25px;
  padding: 16px 24px;
  text-align: center;
  box-shadow: 0 6px 20px rgba(255, 152, 0, 0.4);
  transition: all 0.3s ease;
  margin: 20px;
}

.action-button:active {
  transform: translateY(2px);
  box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
}

.button-text {
  color: #ffffff;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* 动画效果 */
.modal-overlay {
  animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-wrapper {
  animation: scaleIn 0.5s cubic-bezier(0.4, 0, 0.2, 1);
}

.modal-container {
  animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1) 0.1s both;
}

.header-section {
  animation: slideDown 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.1s both;
}

.link-section {
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s both;
}

.action-button {
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
}

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.9);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* 响应式设计 */
@media (max-width: 400px) {
  .modal-overlay {
    padding: 15px;
  }

  .modal-wrapper {
    height: 280px;
    max-width: 100%;
  }

  .action-button {
    padding: 14px 20px;
    margin: 15px;
  }

  .button-text {
    font-size: 15px;
  }
}
</style>

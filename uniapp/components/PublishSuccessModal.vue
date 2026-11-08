<template>
  <view class="modal-overlay" v-if="visible" @click="handleOverlayClick">
    <view class="modal-wrapper">
      <view class="modal-container" @click.stop>
      <!-- 顶部提示区域 -->
      <view class="header-section">
        <text class="header-text">{{ shareCommand }}</text>
      </view>

  

      <!-- 海报预览区域 -->
      <view class="poster-section" v-if="posterUrls.length > 0">
        <view
          class="poster-card"
          v-for="(url, index) in posterUrls"
          :key="index"
          @click="previewPoster(url)"
        >
            <image
              :src="url"
              class="poster-image"
            mode="aspectFill"
              @error="handleImageError"
            />
        </view>
      </view>

      </view>
    </view>

    <!-- 操作按钮 -->
    <view class="action-button" @click="handleSaveAndCopy">
      <text class="button-text">复制并保存图片到相册</text>
    </view>
  </view>
</template>

<script>
export default {
  name: "PublishSuccessModal",
  props: {
    visible: {
      type: Boolean,
      default: false,
    },
    publishedPosts: {
      type: Array,
      default: () => [],
    },
    count: {
      type: Number,
      default: 0,
    },
    shareCommand: {
      type: String,
      default: "👍投稿发布成功",
    },
  },
  data() {
    return {};
  },

  computed: {
    posterUrls() {
      return this.publishedPosts
        .map((post) => post.post_image || post.posterUrl)
        .filter((url) => url);
    },
  },

  methods: {
    // 获取标签和标题（单条投稿时使用）
    getTagAndTitle(item) {
      const tag = item.tag || "投稿";
      const title = item.title || item.content || "无标题";
      // 限制标题长度
      const shortTitle = title.length > 15 ? title.substring(0, 15) + "..." : title;
      return `${tag} ${shortTitle}`;
    },

    // 获取内容预览（多条投稿时使用）
    getContentPreview(item) {
      const content = item.content || item.title || "投稿内容";
      // 限制显示长度，超过20个字符显示省略号
      return content.length > 20 ? content.substring(0, 20) + "..." : content;
    },

    // 预览海报
    previewPoster(url) {
      uni.previewImage({
        urls: this.posterUrls,
        current: url,
      });
    },

    // 处理图片加载错误
    handleImageError(e) {
      console.error("海报图片加载失败:", e);
    },

    // 处理保存和复制
    handleSaveAndCopy() {
      this.$emit("save-and-copy", {
        posterUrls: this.posterUrls,
        count: this.count,
      });
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
  z-index: 9999;
  padding: 20px;
  gap: 15px;
}

.modal-wrapper {
  background-color: #efeaea;
  border-radius: 20px;
  padding: 8px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
  height: 500px;
  width: 100%;
  max-width: 380px;
  overflow-y: auto;
}

.modal-container {
  background-color: #ffffff;
  border-radius: 16px;
  width: 100%;
  height: 100%;
  position: relative;
  overflow-y: auto;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.header-section {
  padding: 24px 20px 20px;
  background-color: #ffffff;
  display: flex;
  align-items: flex-start;
  gap: 8px;
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

.content-section {
  padding: 0 20px 20px;
  background-color: #ffffff;
}

.content-item {
  display: flex;
  margin-bottom: 12px;
  font-size: 15px;
  line-height: 1.5;
  align-items: flex-start;
}

/* 单条投稿时居中显示 */
.content-section.single-post .content-item {
  /* justify-content: center; */
  /* text-align: center; */
}

.content-section.single-post .item-content {
  /* text-align: center; */
  font-weight: 600;
  /* color: #2196F3; */
  font-size: 16px;
}

.item-number {
  color: #666666;
  margin-right: 8px;
  flex-shrink: 0;
  font-weight: 500;
}

.item-content {
  color: #333333;
  flex: 1;
  word-break: break-all;
  font-weight: 500;
}

.poster-section {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  padding: 15px;
  background-color: #ffffff;
}

.poster-card {
  position: relative;
  width: 100%;
  padding-top: 133%;
  overflow: hidden;
  border-radius: 8px;
  background-color: #f7f7f7;
  cursor: pointer;
  transition: transform 0.2s ease;
}

.poster-card:active {
  transform: scale(0.97);
}

.poster-image {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 8px;
}

.action-button {
  background: linear-gradient(135deg, #2196F3 0%, #1976D2 100%);
  border-radius: 25px;
  padding: 16px 24px;
  text-align: center;
  box-shadow: 0 6px 20px rgba(33, 150, 243, 0.4);
  transition: all 0.3s ease;
  width: 50%;
  max-width: 380px;
}

.action-button:active {
  transform: translateY(2px);
  box-shadow: 0 4px 12px rgba(33, 150, 243, 0.3);
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

.content-section {
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.2s both;
}

.poster-section {
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.3s both;
}

.action-button {
  animation: slideUp 0.6s cubic-bezier(0.4, 0, 0.2, 1) 0.4s both;
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
    height: 450px;
    max-width: 100%;
  }

  .poster-frame {
    width: 120px;
    height: 160px;
  }

  .action-button {
    padding: 14px 20px;
    max-width: 100%;
  }

  .button-text {
    font-size: 15px;
  }
}
</style>

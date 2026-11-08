<template>
  <view>
    <!-- 遮罩层 -->
    <view class="mask" @click.stop></view>
    
    <!-- 预览弹窗 -->
    <view class="preview-modal">
      <view class="preview-header">
        <view class="preview-title">预览投稿</view>
        <view class="close-btn" @click="closePreview">
          <uv-icon name="close" size="20" color="#333"></uv-icon>
        </view>
      </view>
      
      <scroll-view scroll-y class="preview-content">
        <!-- 标题 -->
        <view class="post-title">{{ postData.title || '无标题' }}</view>
        
        <!-- 标签 -->
        <view class="post-tag" v-if="postData.tag">
          <uv-icon :name="getTagIcon(postData.tag)" size="14" color="#1a73e8"></uv-icon>
          <text>{{ postData.tag }}</text>
        </view>
        
        <!-- 内容 -->
        <view class="post-content">{{ postData.content || '无内容' }}</view>
        
        <!-- 图片 -->
        <view class="post-images" v-if="postData.images && postData.images.length > 0">
          <view class="image-grid" :class="'grid-' + Math.min(postData.images.length, 3)">
            <image 
              v-for="(img, index) in postData.images" 
              :key="index" 
              :src="img.url" 
              class="post-image"
              mode="aspectFill"
              @click="previewImage(index)"
            ></image>
          </view>
        </view>
        
        <!-- 联系方式 -->
        <view class="post-contact" v-if="postData.contact">
          <text class="contact-label">联系方式: </text>
          <text class="contact-value">{{ postData.contact }}</text>
        </view>
        
        <!-- 发布信息 -->
        <view class="post-info">
          <view class="user-info">
            <image class="avatar" :src="userInfo.avatarUrl || '/static/avatar.png'"></image>
            <text class="nickname">{{ userInfo.nickName || '匿名用户' }}</text>
          </view>
          <view class="post-time">{{ formatDate(new Date()) }}</view>
        </view>
      </scroll-view>
      
      <view class="preview-footer">
        <view class="action-btn edit" @click="closePreview">返回编辑</view>
        <view class="action-btn publish" @click="publishPost">发布投稿</view>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: 'post-preview',
  props: {
    postData: {
      type: Object,
      default: () => ({
        title: '',
        content: '',
        tag: '',
        contact: '',
        images: []
      })
    },
    userInfo: {
      type: Object,
      default: () => ({
        avatarUrl: '',
        nickName: ''
      })
    }
  },
  methods: {
    // 关闭预览
    closePreview() {
      this.$emit('close');
    },
    
    // 发布投稿
    publishPost() {
      this.$emit('publish', this.postData);
    },
    
    // 预览图片
    previewImage(index) {
      const urls = this.postData.images.map(img => img.url);
      uni.previewImage({
        urls: urls,
        current: index
      });
    },
    
    // 获取标签对应的图标
    getTagIcon(tag) {
      const iconMap = {
        '求助': 'question-circle',
        '提问': 'help',
        '拼车': 'car',
        '吐槽': 'chat',
        '寻人': 'search',
        '失物招领': 'flag',
        '出售': 'shopping-cart',
        '其他': 'more'
      };
      
      return iconMap[tag] || 'help';
    },
    
    // 格式化日期
    formatDate(date) {
      const year = date.getFullYear();
      const month = (date.getMonth() + 1).toString().padStart(2, '0');
      const day = date.getDate().toString().padStart(2, '0');
      const hour = date.getHours().toString().padStart(2, '0');
      const minute = date.getMinutes().toString().padStart(2, '0');
      
      return `${year}-${month}-${day} ${hour}:${minute}`;
    }
  }
}
</script>

<style>
.mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 998;
}

.preview-modal {
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 90%;
  height: 80%;
  background-color: #ffffff;
  border-radius: 12px;
  z-index: 999;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
}

.preview-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  border-bottom: 1px solid #eee;
}

.preview-title {
  font-size: 18px;
  font-weight: bold;
  color: #333;
}

.close-btn {
  padding: 5px;
}

.preview-content {
  flex: 1;
  padding: 15px;
  overflow-y: auto;
}

.post-title {
  font-size: 20px;
  font-weight: bold;
  margin-bottom: 10px;
  line-height: 1.4;
  color: #333;
}

.post-tag {
  display: inline-flex;
  align-items: center;
  background-color: #e6f2ff;
  color: #1a73e8;
  padding: 4px 10px;
  border-radius: 15px;
  font-size: 12px;
  margin-bottom: 15px;
}

.post-tag text {
  margin-left: 5px;
}

.post-content {
  font-size: 16px;
  line-height: 1.6;
  color: #333;
  margin-bottom: 15px;
  word-break: break-all;
  white-space: pre-wrap;
}

.post-images {
  margin-bottom: 15px;
}

.image-grid {
  display: flex;
  flex-wrap: wrap;
}

.grid-1 .post-image {
  width: 70%;
  height: 200px;
  margin: 0 auto;
}

.grid-2 .post-image, .grid-3 .post-image {
  width: calc(33.33% - 5px);
  height: 100px;
  margin-right: 5px;
  margin-bottom: 5px;
}

.post-image {
  border-radius: 8px;
  background-color: #f5f5f5;
}

.post-contact {
  font-size: 14px;
  margin-bottom: 15px;
  padding: 10px;
  background-color: #f8f8f8;
  border-radius: 5px;
}

.contact-label {
  color: #999;
}

.contact-value {
  color: #333;
}

.post-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
  padding-top: 15px;
  border-top: 1px solid #eee;
}

.user-info {
  display: flex;
  align-items: center;
}

.avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  margin-right: 10px;
  background-color: #f0f0f0;
}

.nickname {
  font-size: 14px;
  color: #666;
}

.post-time {
  font-size: 12px;
  color: #999;
}

.preview-footer {
  display: flex;
  padding: 15px;
  border-top: 1px solid #eee;
}

.action-btn {
  flex: 1;
  height: 40px;
  line-height: 40px;
  text-align: center;
  border-radius: 5px;
  font-size: 16px;
}

.edit {
  background-color: #f5f5f5;
  color: #333;
  margin-right: 10px;
}

.publish {
  background-color: #1a73e8;
  color: #fff;
}
</style> 
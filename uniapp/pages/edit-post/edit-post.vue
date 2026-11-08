<template>
  <view class="container">
    <!-- 导航栏 -->
    <uv-navbar
      title="编辑投稿"
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

    <!-- 主内容区域 -->
    <view class="main-content">
      <!-- 编辑表单 -->
      <view class="edit-form">
        <!-- 标题 -->
        <view class="input-group">
          <uv-input
            v-model="postTitle"
            placeholder="请输入标题"
            :maxlength="50"
            border="none"
            :customStyle="{ backgroundColor: 'transparent' }"
          ></uv-input>
          <text class="count">{{ postTitle.length }}/50</text>
        </view>

        <!-- 内容 -->
        <view class="input-group content-group">
          <uv-textarea
            v-model="postContent"
            placeholder="请输入投稿内容"
            :maxlength="500"
            height="150"
            :customStyle="{ backgroundColor: 'transparent', border: 'none' }"
          ></uv-textarea>
          <text class="count">{{ postContent.length }}/500</text>
        </view>

        <!-- 联系方式 -->
        <view class="input-group">
          <uv-input
            v-model="contactInfo"
            placeholder="请留下联系方式(选填)"
            border="none"
            :customStyle="{ backgroundColor: 'transparent' }"
          ></uv-input>
        </view>

        <!-- 图片上传 -->
        <view class="upload-box">
          <uv-upload
            :fileList="imageList"
            @afterRead="afterRead"
            @delete="deletePic"
            name="2"
            multiple
            :maxCount="9"
          ></uv-upload>
        </view>

        <!-- 标签选择 -->
        <view class="tags-group">
          <view class="tag-title">选择标签</view>
          <view class="tag-row">
            <view
              v-for="(tag, index) in tagList"
              :key="index"
              class="tag-item-wrapper"
            >
              <uv-tags
                :text="tag.text"
                :plain="false"
                type="info"
                :index="index"
                icon="tags"
                :iconColor="selectedTag === tag.text ? '#2979ff' : '#333333'"
                :bgColor="selectedTag === tag.text ? '#e6f2ff' : '#f2f2f2'"
                :color="selectedTag === tag.text ? '#2979ff' : '#333333'"
                borderColor="transparent"
                @click="selectTag(tag.text)"
                :customStyle="{ marginRight: '10px', marginBottom: '10px' }"
              ></uv-tags>
            </view>
          </view>
        </view>

        <!-- 操作按钮 -->
        <view class="action-buttons">
          <uv-button
            type="default"
            text="取消"
            @click="cancelEdit"
            :customStyle="{
              marginRight: '15px',
              borderRadius: '5px',
              flex: '1'
            }"
          ></uv-button>
          <uv-button
            type="primary"
            text="保存修改"
            @click="savePost"
            :loading="saving"
            :customStyle="{
              borderRadius: '5px',
              flex: '1'
            }"
          ></uv-button>
        </view>
      </view>
    </view>
  </view>
</template>

<script>
import { uploadImage } from "../../api/upload";
import api from "../../api";

export default {
  data() {
    return {
      postId: '',
      postTitle: '',
      postContent: '',
      contactInfo: '',
      selectedTag: '',
      imageList: [],
      saving: false,
      tagList: [
        { text: "求助", icon: "help" },
        { text: "提问", icon: "help-filled" },
        { text: "拼车", icon: "car" },
        { text: "吐槽", icon: "chat" },
        { text: "寻人", icon: "search" },
        { text: "失物招领", icon: "gift" },
        { text: "出售", icon: "shopping-cart" },
        { text: "其他", icon: "more-dot-fill" },
      ]
    };
  },

  async onLoad(options) {
    if (options.id) {
      this.postId = options.id;
      await this.loadPostDetail();
    }
  },

  methods: {
    // 加载投稿详情
    async loadPostDetail() {
      try {
        uni.showLoading({ title: '加载中...' });
        
        const result = await api.post.getPostDetail(this.postId);
        
        if (result.code === 200) {
          const post = result.data;
          this.postTitle = post.title || '';
          this.postContent = post.content || '';
          this.contactInfo = post.contact || '';
          this.selectedTag = post.tag || '';
          
          // 处理图片列表
          if (post.images && Array.isArray(post.images)) {
            this.imageList = post.images.map(img => ({
              url: typeof img === 'string' ? img : img.url,
              status: 'success',
              message: '上传成功'
            }));
          }
        } else {
          uni.showToast({
            title: result.message || '加载失败',
            icon: 'none'
          });
          setTimeout(() => {
            uni.navigateBack();
          }, 1500);
        }
      } catch (error) {
        console.error('加载投稿详情失败:', error);
        uni.showToast({
          title: '加载失败',
          icon: 'none'
        });
        setTimeout(() => {
          uni.navigateBack();
        }, 1500);
      } finally {
        uni.hideLoading();
      }
    },

    // 选择标签
    selectTag(tag) {
      this.selectedTag = tag === this.selectedTag ? '' : tag;
    },

    // 上传图片后的回调
    async afterRead(event) {
      const { file } = event;
      console.log(file, "file");
      
      const fileList = Array.isArray(file) ? file : [file];
      
      for (let i = 0; i < fileList.length; i++) {
        const file = fileList[i];
        console.log(file, "file");
        
        try {
          const result = await uploadImage(file.url);
          console.log(result, "result");
          
          if (result.code === 200) {
            this.imageList.push({
              url: result.data.url,
              status: "success",
              message: "上传成功",
            });
          } else {
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
          }
        } catch (error) {
          console.error('图片上传失败:', error);
          uni.showToast({
            title: "上传失败",
            icon: "none",
          });
        }
      }
    },

    // 删除图片
    deletePic(event) {
      this.imageList.splice(event.index, 1);
    },

    // 保存投稿
    async savePost() {
      // 防止重复点击
      if (this.saving) {
        return;
      }

      // 验证表单
      if (!this.postTitle.trim()) {
        return uni.showToast({
          title: "请输入标题",
          icon: "none",
        });
      }
      
      if (!this.postContent.trim()) {
        return uni.showToast({
          title: "请输入投稿内容",
          icon: "none",
        });
      }
      
      if (!this.selectedTag) {
        return uni.showToast({
          title: "请选择标签",
          icon: "none",
        });
      }

      try {
        this.saving = true;
        
        const postData = {
          id: this.postId,
          title: this.postTitle.trim(),
          content: this.postContent.trim(),
          contact: this.contactInfo.trim(),
          tag: this.selectedTag,
          images: this.imageList.map(img => ({ url: img.url }))
        };

        const result = await api.post.updatePost(postData);
        
        if (result.code === 200) {
          uni.showToast({
            title: '保存成功',
            icon: 'success'
          });
          
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
        console.error('保存投稿失败:', error);
        uni.showToast({
          title: '保存失败',
          icon: 'none'
        });
      } finally {
        this.saving = false;
      }
    },

    // 取消编辑
    cancelEdit() {
      uni.showModal({
        title: '确认取消',
        content: '确定要取消编辑吗？未保存的修改将丢失。',
        success: (res) => {
          if (res.confirm) {
            uni.navigateBack();
          }
        }
      });
    }
  }
};
</script>

<style scoped>
.container {
  background-color: #f0f6fb;
  min-height: 100vh;
}

.main-content {
  padding: 15px;
}

.edit-form {
  background-color: #fff;
  border-radius: 10px;
  padding: 20px;
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
}

.input-group {
  margin-bottom: 20px;
  position: relative;
  background-color: #f8f9fa;
  border-radius: 8px;
  padding: 15px;
}

.content-group {
  min-height: 150px;
}

.count {
  position: absolute;
  bottom: 10px;
  right: 15px;
  font-size: 12px;
  color: #999;
}

.upload-box {
  margin-bottom: 25px;
}

.tags-group {
  margin-bottom: 30px;
}

.tag-title {
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 15px;
  color: #333;
}

.tag-row {
  display: flex;
  flex-wrap: wrap;
}

.action-buttons {
  display: flex;
  gap: 15px;
  margin-top: 30px;
}
</style>

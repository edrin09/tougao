# 校园墙应用开发指南

## 技术架构

### 前端框架
- **uni-app**：使用Vue.js开发，一次开发多端部署
- **uView UI**：统一UI组件库，提高开发效率
- **CSS变量**：实现主题色和暗黑模式支持

### 后端服务
- **云开发**：使用微信小程序云开发能力
- **云函数**：实现业务逻辑处理
- **云数据库**：数据存储和查询
- **云存储**：图片等静态资源存储

### 第三方服务
- **微信登录**：用户授权和身份认证
- **内容安全检测**：文本和图片内容安全审核

## 目录结构

```
校园墙应用/
├── components/             # 自定义组件
│   ├── tag-selector/       # 标签选择组件
│   ├── image-uploader/     # 图片上传组件
│   └── post-card/          # 投稿卡片组件
├── pages/                  # 页面文件
│   ├── index/              # 首页
│   ├── post/               # 投稿相关页面
│   │   ├── create.vue      # 投稿表单页
│   │   ├── preview.vue     # 投稿预览页
│   │   └── detail.vue      # 投稿详情页
│   ├── user/               # 用户中心页面
│   │   ├── profile.vue     # 个人信息页
│   │   └── settings.vue    # 设置页面
│   └── admin/              # 管理员页面
│       ├── review.vue      # 审核页面
│       └── settings.vue    # 墙主设置页面
├── static/                 # 静态资源
├── uni_modules/            # uni扩展组件
├── utils/                  # 工具函数
├── store/                  # 状态管理
├── api/                    # API接口
├── common/                 # 公共资源
│   ├── styles/             # 样式文件
│   └── config.js           # 全局配置
├── pages.json              # 页面路由配置
├── manifest.json           # 应用配置
└── main.js                 # 入口文件
```

## 开发规范

### 命名规范
- **文件命名**：小写字母+中横线，如`post-card.vue`
- **组件命名**：大驼峰命名，如`PostCard`
- **变量命名**：小驼峰命名，如`userInfo`
- **常量命名**：全大写+下划线，如`MAX_FILE_SIZE`

### 代码风格
- 使用ESLint进行代码规范检查
- 使用Prettier进行代码格式化
- 遵循Vue官方推荐的代码风格指南

### 注释规范
- 函数注释使用JSDoc规范
- 关键业务逻辑需添加注释说明
- 复杂组件需提供使用说明

## 核心功能开发流程

### 1. 投稿表单页面开发

#### 步骤1：创建表单组件
```vue
<template>
  <view class="post-form">
    <!-- 标题输入 -->
    <u-form-item label="标题">
      <u-input v-model="form.title" placeholder="请输入标题（50字以内）" :maxlength="50" />
    </u-form-item>
    
    <!-- 内容输入 -->
    <u-form-item label="内容">
      <u-textarea v-model="form.content" placeholder="请输入内容（500字以内）" :maxlength="500" />
    </u-form-item>
    
    <!-- 图片上传 -->
    <u-form-item label="上传图片">
      <image-uploader v-model="form.images" :max="9" />
    </u-form-item>
    
    <!-- 标签选择 -->
    <u-form-item label="选择标签">
      <tag-selector v-model="form.tags" />
    </u-form-item>
    
    <!-- 提交按钮 -->
    <u-button type="primary" @click="handlePreview">预览</u-button>
  </view>
</template>

<script>
export default {
  data() {
    return {
      form: {
        title: '',
        content: '',
        images: [],
        tags: []
      }
    }
  },
  methods: {
    handlePreview() {
      // 表单验证
      if (!this.form.title) {
        uni.showToast({
          title: '请输入标题',
          icon: 'none'
        });
        return;
      }
      
      // 跳转到预览页面
      uni.navigateTo({
        url: '/pages/post/preview',
        success: (res) => {
          res.eventChannel.emit('postData', this.form);
        }
      });
    }
  }
}
</script>
```

#### 步骤2：图片上传组件开发
```vue
<template>
  <view class="image-uploader">
    <view class="image-list">
      <view 
        v-for="(item, index) in imageList" 
        :key="index" 
        class="image-item"
      >
        <image :src="item" mode="aspectFill" @click="previewImage(index)"></image>
        <view class="delete-icon" @click.stop="deleteImage(index)">×</view>
      </view>
      
      <view 
        v-if="imageList.length < max" 
        class="upload-button" 
        @click="chooseImage"
      >
        <u-icon name="plus" size="30"></u-icon>
      </view>
    </view>
  </view>
</template>

<script>
export default {
  name: 'ImageUploader',
  props: {
    value: {
      type: Array,
      default: () => []
    },
    max: {
      type: Number,
      default: 9
    }
  },
  data() {
    return {
      imageList: this.value
    }
  },
  watch: {
    value(val) {
      this.imageList = val;
    },
    imageList(val) {
      this.$emit('input', val);
    }
  },
  methods: {
    chooseImage() {
      uni.chooseImage({
        count: this.max - this.imageList.length,
        sizeType: ['compressed'],
        sourceType: ['album', 'camera'],
        success: (res) => {
          this.imageList = [...this.imageList, ...res.tempFilePaths];
        }
      });
    },
    deleteImage(index) {
      this.imageList.splice(index, 1);
    },
    previewImage(index) {
      uni.previewImage({
        urls: this.imageList,
        current: this.imageList[index]
      });
    }
  }
}
</script>

<style scoped>
.image-uploader {
  width: 100%;
}
.image-list {
  display: flex;
  flex-wrap: wrap;
}
.image-item {
  width: 200rpx;
  height: 200rpx;
  margin: 10rpx;
  position: relative;
}
.image-item image {
  width: 100%;
  height: 100%;
  border-radius: 8rpx;
}
.delete-icon {
  position: absolute;
  top: -10rpx;
  right: -10rpx;
  width: 40rpx;
  height: 40rpx;
  background-color: rgba(0, 0, 0, 0.5);
  color: #fff;
  border-radius: 50%;
  display: flex;
  justify-content: center;
  align-items: center;
}
.upload-button {
  width: 200rpx;
  height: 200rpx;
  margin: 10rpx;
  background-color: #f5f5f5;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 8rpx;
}
</style>
```

#### 步骤3：标签选择组件开发
```vue
<template>
  <view class="tag-selector">
    <view class="tag-list">
      <u-tag
        v-for="(tag, index) in tagList"
        :key="index"
        :text="tag.name"
        :type="selectedTags.includes(tag.id) ? 'primary' : 'info'"
        @click="toggleTag(tag)"
        :closable="selectedTags.includes(tag.id)"
        @close="removeTag(tag)"
      />
    </view>
  </view>
</template>

<script>
export default {
  name: 'TagSelector',
  props: {
    value: {
      type: Array,
      default: () => []
    },
    max: {
      type: Number,
      default: 3
    }
  },
  data() {
    return {
      tagList: [
        { id: 1, name: '通知公告' },
        { id: 2, name: '失物招领' },
        { id: 3, name: '二手交易' },
        { id: 4, name: '求助互助' },
        { id: 5, name: '活动宣传' },
        { id: 6, name: '日常分享' }
      ],
      selectedTags: this.value
    }
  },
  watch: {
    value(val) {
      this.selectedTags = val;
    },
    selectedTags(val) {
      this.$emit('input', val);
    }
  },
  methods: {
    toggleTag(tag) {
      const index = this.selectedTags.indexOf(tag.id);
      if (index > -1) {
        this.selectedTags.splice(index, 1);
      } else {
        if (this.selectedTags.length >= this.max) {
          uni.showToast({
            title: `最多选择${this.max}个标签`,
            icon: 'none'
          });
          return;
        }
        this.selectedTags.push(tag.id);
      }
    },
    removeTag(tag) {
      const index = this.selectedTags.indexOf(tag.id);
      if (index > -1) {
        this.selectedTags.splice(index, 1);
      }
    }
  }
}
</script>

<style scoped>
.tag-selector {
  width: 100%;
}
.tag-list {
  display: flex;
  flex-wrap: wrap;
}
.u-tag {
  margin: 10rpx;
}
</style>
```

### 2. 投稿预览页面开发

```vue
<template>
  <view class="preview-page">
    <view 
      class="post-card"
      :style="{ backgroundColor: selectedColor }"
    >
      <view class="post-title">{{ postData.title }}</view>
      <view class="post-content">{{ postData.content }}</view>
      
      <view v-if="postData.images.length > 0" class="post-images">
        <image 
          v-for="(img, index) in postData.images" 
          :key="index"
          :src="img"
          mode="aspectFill"
          @click="previewImage(index)"
        ></image>
      </view>
      
      <view v-if="postData.tags.length > 0" class="post-tags">
        <u-tag
          v-for="(tagId, index) in postData.tags"
          :key="index"
          :text="getTagName(tagId)"
          type="info"
          size="mini"
        />
      </view>
    </view>
    
    <view class="color-selector">
      <view class="color-title">选择背景色</view>
      <view class="color-list">
        <view
          v-for="(color, index) in bgColors"
          :key="index"
          class="color-item"
          :style="{ backgroundColor: color }"
          :class="{ active: selectedColor === color }"
          @click="selectColor(color)"
        ></view>
      </view>
    </view>
    
    <view class="action-buttons">
      <u-button @click="goBack" plain>返回修改</u-button>
      <u-button type="primary" @click="submitPost">确认发布</u-button>
    </view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      postData: {
        title: '',
        content: '',
        images: [],
        tags: []
      },
      bgColors: [
        '#ffffff', '#f8f9fa', '#e9ecef', '#dee2e6',
        '#ffe8e8', '#e8f8ff', '#e8ffe8', '#fff8e8'
      ],
      selectedColor: '#ffffff',
      tagList: [
        { id: 1, name: '通知公告' },
        { id: 2, name: '失物招领' },
        { id: 3, name: '二手交易' },
        { id: 4, name: '求助互助' },
        { id: 5, name: '活动宣传' },
        { id: 6, name: '日常分享' }
      ]
    }
  },
  onLoad() {
    const eventChannel = this.getOpenerEventChannel();
    eventChannel.on('postData', (data) => {
      this.postData = data;
    });
  },
  methods: {
    getTagName(tagId) {
      const tag = this.tagList.find(item => item.id === tagId);
      return tag ? tag.name : '';
    },
    selectColor(color) {
      this.selectedColor = color;
    },
    previewImage(index) {
      uni.previewImage({
        urls: this.postData.images,
        current: this.postData.images[index]
      });
    },
    goBack() {
      uni.navigateBack();
    },
    submitPost() {
      // 添加背景色到投稿数据
      const submitData = {
        ...this.postData,
        bgColor: this.selectedColor
      };
      
      // 显示加载提示
      uni.showLoading({
        title: '提交中...'
      });
      
      // 上传图片（实际项目中需要实现）
      this.uploadImages(submitData.images)
        .then(imageUrls => {
          // 替换为上传后的图片URL
          submitData.images = imageUrls;
          
          // 调用云函数提交投稿
          return this.savePostToCloud(submitData);
        })
        .then(res => {
          uni.hideLoading();
          uni.showToast({
            title: '提交成功，等待审核',
            icon: 'success'
          });
          
          // 延迟返回首页
          setTimeout(() => {
            uni.switchTab({
              url: '/pages/index/index'
            });
          }, 1500);
        })
        .catch(err => {
          uni.hideLoading();
          uni.showToast({
            title: '提交失败，请重试',
            icon: 'none'
          });
          console.error(err);
        });
    },
    uploadImages(localPaths) {
      // 模拟图片上传，实际项目中需要实现
      return new Promise((resolve, reject) => {
        // 模拟网络延迟
        setTimeout(() => {
          resolve(localPaths); // 返回云存储URL列表
        }, 1000);
      });
    },
    savePostToCloud(postData) {
      // 模拟调用云函数，实际项目中需要实现
      return new Promise((resolve, reject) => {
        // 模拟网络延迟
        setTimeout(() => {
          resolve({ success: true, id: 'post_' + Date.now() });
        }, 1000);
      });
    }
  }
}
</script>

<style scoped>
.preview-page {
  padding: 30rpx;
}
.post-card {
  border-radius: 12rpx;
  padding: 30rpx;
  margin-bottom: 30rpx;
  box-shadow: 0 2rpx 10rpx rgba(0, 0, 0, 0.1);
}
.post-title {
  font-size: 36rpx;
  font-weight: bold;
  margin-bottom: 20rpx;
}
.post-content {
  font-size: 28rpx;
  line-height: 1.6;
  margin-bottom: 20rpx;
}
.post-images {
  display: flex;
  flex-wrap: wrap;
  margin-bottom: 20rpx;
}
.post-images image {
  width: 200rpx;
  height: 200rpx;
  margin-right: 10rpx;
  margin-bottom: 10rpx;
  border-radius: 8rpx;
}
.post-tags {
  display: flex;
  flex-wrap: wrap;
}
.color-selector {
  margin-bottom: 30rpx;
}
.color-title {
  font-size: 30rpx;
  margin-bottom: 20rpx;
}
.color-list {
  display: flex;
  flex-wrap: wrap;
}
.color-item {
  width: 80rpx;
  height: 80rpx;
  border-radius: 50%;
  margin-right: 20rpx;
  margin-bottom: 20rpx;
  border: 2rpx solid #ddd;
}
.color-item.active {
  border: 4rpx solid #2979ff;
}
.action-buttons {
  display: flex;
  justify-content: space-between;
}
</style>
```

## API接口设计

### 用户相关接口

| 接口名称 | 接口路径 | 请求方式 | 请求参数 | 返回数据 |
| --- | --- | --- | --- | --- |
| 登录 | /user/login | POST | code: string | { token, userInfo } |
| 更新用户信息 | /user/update | POST | nickname: string, avatar: string | { success: boolean } |
| 获取用户信息 | /user/info | GET | - | { userInfo } |

### 投稿相关接口

| 接口名称 | 接口路径 | 请求方式 | 请求参数 | 返回数据 |
| --- | --- | --- | --- | --- |
| 创建投稿 | /post/create | POST | title, content, images, tags, bgColor | { postId } |
| 获取投稿列表 | /post/list | GET | page, size, status | { list, total } |
| 获取投稿详情 | /post/detail | GET | postId | { postDetail } |
| 审核投稿 | /post/review | POST | postId, status, comment | { success: boolean } |

### 上传相关接口

| 接口名称 | 接口路径 | 请求方式 | 请求参数 | 返回数据 |
| --- | --- | --- | --- | --- |
| 获取上传签名 | /upload/sign | GET | - | { sign, key } |
| 上传图片 | /upload/image | POST | file | { url } |

## 小程序上线注意事项

1. **服务类目选择**：选择符合小程序功能的服务类目
2. **内容安全审核**：确保内容符合微信小程序平台规范
3. **隐私政策**：提供完善的隐私政策说明
4. **版本迭代计划**：规划版本迭代节奏，及时修复问题
5. **服务器配置**：合理配置服务器资源，保障稳定性
6. **测试**：提交前进行全面测试，确保各功能正常

## 常见问题解决方案

### 1. 图片上传失败
- 检查图片大小是否超过限制（建议不超过2MB）
- 检查网络连接状态
- 检查云存储权限配置

### 2. 用户授权问题
- 使用最新的授权API
- 提供清晰的授权说明
- 实现静默授权机制

### 3. 小程序审核被拒
- 检查内容合规性
- 完善服务类目说明
- 确保功能与类目一致

### 4. 性能优化
- 减少不必要的网络请求
- 合理使用缓存
- 优化图片加载策略
- 减少页面重绘和回流 
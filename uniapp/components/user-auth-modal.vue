<template>
  <view>
    <!-- 遮罩层 -->
    <view class="mask" @click.stop></view>
    
    <!-- 授权弹窗 -->
    <view class="auth-modal">
      <view class="auth-header">
        <uv-icon name="weixin-circle-fill" size="20" color="#07c160"></uv-icon>
        <text class="auth-title">{{ title }}</text>
      </view>
      
      <view class="auth-tips">{{ tips }}</view>
      <view class="auth-subtitle">{{ subtitle }}</view>
      
      <view class="avatar-section">
        <!-- 在微信小程序环境中使用原生头像选择器 -->
        <!-- #ifdef MP-WEIXIN -->
        <button class="avatar-wrapper" open-type="chooseAvatar" @chooseavatar="onChooseAvatar">
          <image
            v-if="userInfo.avatarUrl"
            class="avatar-img"
            :src="userInfo.avatarUrl"
          ></image>
          <uv-icon v-else name="account" size="40" color="#999"></uv-icon>
        </button>
        <text class="avatar-text">点击选择头像</text>
        <!-- #endif -->
        
        <!-- 在其他平台使用原有的选择方式 -->
        <!-- #ifndef MP-WEIXIN -->
        <view class="avatar-box" @click="chooseAvatar">
          <image
            v-if="userInfo.avatarUrl"
            class="avatar-img"
            :src="userInfo.avatarUrl"
          ></image>
          <uv-icon v-else name="account" size="40" color="#999"></uv-icon>
        </view>
        <text class="avatar-text">点击选择头像</text>
        <!-- #endif -->
      </view>
      
      <view class="nickname-section">
        <view class="nickname-label">昵称</view>
        <!-- 微信小程序环境使用原生昵称输入框 -->
        <!-- #ifdef MP-WEIXIN -->
        <input
          type="nickname"
          class="nickname-input"
          v-model="userInfo.nickName"
          placeholder="请输入昵称"
          @blur="onNicknameChange"
        />
        <!-- #endif -->

        <!-- 其他平台使用普通输入框 -->
        <!-- #ifndef MP-WEIXIN -->
        <input
          class="nickname-input"
          v-model="userInfo.nickName"
          placeholder="请填写昵称"
        />
        <!-- #endif -->
      </view>
      
      <!-- 隐私政策选择框 -->
      <view class="privacy-section">
        <view class="privacy-checkbox" @click="togglePrivacyAgreement">
          <view class="checkbox-box" :class="{ 'checkbox-checked': privacyAgreed }">
            <uv-icon v-if="privacyAgreed" name="checkmark" size="12" color="#ffffff"></uv-icon>
          </view>
          <text class="privacy-text">我已阅读并同意</text>
          <text class="privacy-link" @click.stop="showPrivacyPolicy">《隐私政策》</text>
        </view>
      </view>
      
      <view class="auth-buttons">
        <view class="cancel-button" @click="cancelAuth">取消</view>
        <view class="confirm-button" @click="confirmAuth">授权</view>
      </view>
    </view>
  </view>
</template>

<script>
  import { chooseAndUploadImage } from '../api/upload.js';
  import { updateUserInfo } from '../api/user.js';

  export default {
    name: "user-auth-modal",
    props: {
      title: {
        type: String,
        default: "小程序申请"
      },
      tips: {
        type: String,
        default: "完善你的昵称、头像"
      },
      subtitle: {
        type: String,
        default: "注册、登录小程序"
      }
    },
    data() {
      return {
        userInfo: {
          avatarUrl: "",
          nickName: ""
        },
        uploading: false,
        privacyAgreed: false
      };
    },
    methods: {
      // 微信小程序原生头像选择回调
      onChooseAvatar(e) {
        this.uploading = true;
        uni.showLoading({ title: '上传头像中...' });
        
        // 获取选中的头像临时路径
        const tempFilePath = e.detail.avatarUrl;
        
        // 使用封装的API上传头像
        this.$api.upload.uploadImage(tempFilePath)
          .then(res => {
            if (res.code === 200) {
              this.userInfo.avatarUrl = res.data.url;
            } else {
              uni.showToast({
                title: '头像上传失败',
                icon: 'none'
              });
            }
          })
          .catch(err => {
            uni.showToast({
              title: '头像上传失败',
              icon: 'none'
            });
            console.error('头像上传失败:', err);
          })
          .finally(() => {
            this.uploading = false;
            uni.hideLoading();
          });
      },
      
      // 昵称输入处理（微信小程序）
      onNicknameChange(e) {
        // 可以添加额外处理逻辑，如格式检查等
        console.log('昵称已更新:', this.userInfo.nickName);
      },
      
      // 选择头像（保留原有方法用于其他平台）
      chooseAvatar() {
        // #ifdef APP-PLUS || H5
        this.uploading = true;
        uni.showLoading({ title: '选择并上传头像中...' });
        
        this.$api.upload.chooseAndUploadImage()
          .then(imageUrl => {
            this.userInfo.avatarUrl = imageUrl;
          })
          .catch(err => {
            uni.showToast({
              title: '头像上传失败',
              icon: 'none'
            });
            console.error('头像上传失败:', err);
          })
          .finally(() => {
            this.uploading = false;
            uni.hideLoading();
          });
        // #endif
      },
      
      // 显示隐私政策
      showPrivacyPolicy() {
        // 可以跳转到隐私政策页面或显示隐私政策弹窗
        uni.navigateTo({
          url: '/pages/privacy-policy/privacy-policy'
        });
      },
      
      togglePrivacyAgreement() {
        this.privacyAgreed = !this.privacyAgreed;
      },
      
      // 取消授权
      cancelAuth() {
        this.$emit('cancel');
      },
      
      // 确认授权
      confirmAuth() {
        if (!this.privacyAgreed) {
          return uni.showToast({
            title: "请先同意隐私政策",
            icon: "none",
          });
        }
        
        if (!this.userInfo.avatarUrl) {
          return uni.showToast({
            title: "请选择头像",
            icon: "none",
          });
        }
        
        if (!this.userInfo.nickName.trim()) {
          return uni.showToast({
            title: "请填写昵称",
            icon: "none",
          });
        }
        
        // 显示加载提示
        uni.showLoading({ title: '保存信息中...' });
        
        // 调用更新用户信息API
        this.$api.user.updateUserInfo({
          nickname: this.userInfo.nickName,
          avatar: this.userInfo.avatarUrl
        })
        .then(res => {
          uni.hideLoading();
          if (res.code === 200) {
            uni.showToast({
              title: '授权成功',
              icon: 'success'
            });
            
            // 将用户信息传递给父组件
            this.$emit('confirm', {
              nickName: this.userInfo.nickName,
              avatarUrl: this.userInfo.avatarUrl
            });
          } else {
            uni.showToast({
              title: res.message || '授权失败',
              icon: 'none'
            });
          }
        })
        .catch(err => {
          uni.hideLoading();
          uni.showToast({
            title: '授权失败，请重试',
            icon: 'none'
          });
          console.error('授权失败:', err);
        });
      }
    }
  };
</script>

<style>
  /* 遮罩层 */
  .mask {
    position: fixed;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 998;
  }
  
  /* 授权弹窗 */
  .auth-modal {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ffffff;
    border-radius: 20px 20px 0 0;
    padding: 30px 20px;
    z-index: 999;
    box-shadow: 0 -5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .auth-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .auth-title {
    font-size: 16px;
    font-weight: 500;
    margin-left: 10px;
  }
  
  .auth-tips {
    font-size: 20px;
    font-weight: bold;
    margin-bottom: 10px;
  }
  
  .auth-subtitle {
    font-size: 14px;
    color: #666;
    margin-bottom: 30px;
  }
  
  .avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
  }
  
  .avatar-box {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    margin-bottom: 10px;
  }
  
  .avatar-img {
    width: 100%;
    height: 100%;
  }
  
  .avatar-text {
    font-size: 14px;
    color: #333;
  }
  
  .nickname-section {
    margin-bottom: 30px;
  }
  
  .nickname-label {
    font-size: 16px;
    margin-bottom: 10px;
  }
  
  .nickname-input {
    height: 50px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
    width: 100%;
  }
  
  .privacy-section {
    margin-bottom: 20px;
    text-align: center;
  }
  
  .privacy-checkbox {
    display: flex;
    align-items: center;
    justify-content: center;
    flex-wrap: wrap;
  }
  
  .checkbox-box {
    width: 16px;
    height: 16px;
    border: 1px solid #ddd;
    border-radius: 3px;
    margin-right: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #fff;
    transition: all 0.3s ease;
  }
  
  .checkbox-checked {
    background-color: #007aff;
    border-color: #007aff;
  }
  
  .privacy-text {
    font-size: 12px;
    color: #999;
  }
  
  .privacy-link {
    font-size: 12px;
    color: #4cd964;
    text-decoration: underline;
    margin-left: 4px;
  }
  
  .auth-buttons {
    display: flex;
    justify-content: space-between;
  }
  
  .cancel-button, .confirm-button {
    width: 45%;
    height: 50px;
    line-height: 50px;
    text-align: center;
    border-radius: 5px;
    font-size: 16px;
  }
  
  .cancel-button {
    background-color: #f5f5f5;
    color: #333;
  }
  
  .confirm-button {
    background-color: #4cd964;
    color: #fff;
  }
  
  .avatar-wrapper {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background-color: #f0f0f0;
    display: flex;
    justify-content: center;
    align-items: center;
    overflow: hidden;
    margin-bottom: 10px;
    padding: 0;
    border: none;
    line-height: 1;
  }
</style>
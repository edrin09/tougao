<template>
<view class="login-container">
	<view class="logo-box">
	  <image class="logo" src="/static/logo.png" mode="aspectFit"></image>
	  <text class="title">校园墙</text>
	</view>
	<view class="form-box">
	  <view class="input-group">
		<view class="input-item">
		  <input class="input" type="text" v-model="openid" placeholder="请输入您的OpenID" placeholder-class="placeholder"/>
		</view>
	  </view>
	</view>
	<view class="btn-box">
	  <button class="login-btn" type="primary" @tap="loginWithOpenid">登录</button>
	</view>
	<view class="tips">
	  <text class="tips-text">请联系管理员获取OpenID</text>
	</view>
</view>
</template>
<script>
export default {
  data() {
    return {
      loading: false,
      openid: ''
    }
  },
  methods: {
    // 使用OpenID登录
    loginWithOpenid() {
      if (!this.openid) {
        uni.showToast({
          title: '请输入OpenID',
          icon: 'none'
        });
        return;
      }
      // 显示加载中
      this.loading = true;
      uni.showLoading({
        title: '登录中...'
      });
      // 调用OpenID登录API
      this.$api.user.loginWithOpenid(this.openid)
          .then(res => {
            // 保存用户信息
            uni.setStorageSync("userInfo", res.data);
            // 登录成功，跳转到首页
            uni.reLaunch({
              url: '/pages/personal/personal'
            });
          })
          .catch(err => {
            console.error('登录失败:', err);
            uni.showToast({
              title: '登录失败，请检查OpenID是否正确',
              icon: 'none'
            });
          })
          .finally(() => {
            uni.hideLoading();
            this.loading = false;
          });
    }
  }
}
</script>
<style lang="scss">
.login-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 100rpx 50rpx;
  .logo-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 80rpx;
    .logo {
      width: 200rpx;
      height: 200rpx;
    }
    .title {
      font-size: 40rpx;
      font-weight: bold;
      margin-top: 30rpx;
    }
  }
  .form-box {
    width: 100%;
    margin-bottom: 60rpx;
    .input-group {
      background-color: #fff;
      border-radius: 16rpx;
      overflow: hidden;
      box-shadow: 0 4rpx 20rpx rgba(0, 0, 0, 0.05);
    }
    .input-item {
      padding: 30rpx;
      border-bottom: 1rpx solid #f5f5f5;
      .label {
        display: block;
        font-size: 30rpx;
        color: #333;
        margin-bottom: 20rpx;
      }
      .input {
        font-size: 32rpx;
        height: 60rpx;
        line-height: 60rpx;
      }
      .placeholder {
        color: #ccc;
        font-size: 30rpx;
      }
    }
  }
  .btn-box {
    width: 100%;
    .login-btn {
      width: 100%;
      height: 90rpx;
      line-height: 90rpx;
      margin-bottom: 30rpx;
      border-radius: 45rpx;
      font-size: 32rpx;
    }
    .cancel-btn {
      background-color: #f5f5f5;
      color: #333;
    }
  }
  .tips {
    margin-top: 40rpx;
    .tips-text {
      font-size: 26rpx;
      color: #999;
    }
  }
}
</style>
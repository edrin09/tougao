<template>
  <view>
  </view>
</template>

<script>
export default {
 
  
  onLaunch: function () {
    console.warn(
      "当前组件仅支持 uni_modules 目录结构 ，请升级 HBuilderX 到 3.1.0 版本以上！"
    );
    console.log("App Launch");
    
    // 不再自动调用登录逻辑，避免与页面执行顺序冲突
    // 登录逻辑已抽离到 api/auth.js 文件中
  },
  onShow: function () {
    console.log("App Show");
  },
  onHide: function () {
    console.log("App Hide");
  },
  methods: {

  },
};
</script>

<style lang="scss">
/*每个页面公共css */
@import "@/uni_modules/uni-scss/index.scss";
/* #ifndef APP-NVUE */
@import "@/static/customicons.css";
// 设置整个项目的背景色
page {
  background-color: #f5f5f5;
}

/* #endif */
.example-info {
  font-size: 14px;
  color: #333;
  padding: 10px;
}

/* 授权弹窗样式 */
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
}

.auth-buttons {
  display: flex;
  justify-content: space-between;
}

.cancel-button,
.confirm-button {
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

.mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  z-index: 998;
}
</style>

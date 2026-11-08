<template>
  <view class="privacy-container" :style="{ paddingTop: statusBarHeight + 'px' }">
    <!-- 顶部导航栏 -->
    <uv-navbar
      title="隐私政策"
      :autoBack="true"
      bgColor="transparent"
      :border="false"
      :titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }"
      leftIcon="arrow-left"
      leftIconColor="#333333"
      :safeAreaInsetTop="true"
      :fixed="true"
      :placeholder="true"
      :height="44"
    >
    </uv-navbar>

    <!-- 隐私政策内容 -->
    <scroll-view class="privacy-content" scroll-y="true">
      <view class="content-wrapper">
        
        <view class="section">
          <text class="section-title">引言</text>
          <text class="section-content">
            欢迎使用我们的小程序！我们非常重视您的隐私保护和个人信息安全。本隐私政策将向您说明我们如何收集、使用、储存和保护您的个人信息，以及您享有的相关权利。
          </text>
        </view>

        <view class="section">
          <text class="section-title">1. 我们收集的信息</text>
          <text class="section-content">
            为了向您提供更好的服务，我们可能会收集以下信息：
            \n• 基本信息：昵称、头像等您主动提供的信息
          </text>
        </view>

        <view class="section">
          <text class="section-title">2. 信息的使用</text>
          <text class="section-content">
            我们收集您的个人信息主要用于：
            \n• 为您提供、维护和改进我们的服务
            \n• 处理您的请求
            \n• 向您发送重要通知和更新
            \n• 遵守法律法规要求
          </text>
        </view>

        <view class="section">
          <text class="section-title">3. 信息的共享</text>
          <text class="section-content">
            我们不会向第三方出售、出租或以其他方式披露您的个人信息，除非：
            \n• 获得您的明确同意
            \n• 为了提供您所需的服务
            \n• 遵守法律法规、法院命令或政府要求
            \n• 保护我们的合法权益
          </text>
        </view>

        <view class="section">
          <text class="section-title">4. 信息的存储和保护</text>
          <text class="section-content">
            我们采用行业标准的安全措施来保护您的个人信息：
            \n• 使用加密技术传输和存储数据
            \n• 限制员工访问个人信息
            \n• 定期进行安全审计和更新
            \n• 建立数据泄露应急响应机制
          </text>
        </view>

        <view class="section">
          <text class="section-title">5. 您的权利</text>
          <text class="section-content">
            您对自己的个人信息享有以下权利：
            \n• 访问权：您有权了解我们收集的关于您的个人信息
            \n• 更正权：您有权要求我们更正不准确的个人信息
            \n• 删除权：在特定情况下，您有权要求我们删除您的个人信息
            \n• 撤回同意：您有权随时撤回对个人信息处理的同意
          </text>
        </view>

        <view class="section">
          <text class="section-title">6. 未成年人保护</text>
          <text class="section-content">
            我们非常重视未成年人的个人信息保护。如果您是未成年人，请在监护人的陪同下阅读本政策，并在监护人同意后使用我们的服务。
          </text>
        </view>

        <view class="section">
          <text class="section-title">7. 政策更新</text>
          <text class="section-content">
            我们可能会不时更新本隐私政策。更新后的政策将在本页面发布，重大变更我们会通过适当方式通知您。
          </text>
        </view>

       
      </view>
    </scroll-view>
  </view>
</template>

<script>
export default {
  data() {
    return {
      statusBarHeight: 0,
      updateTime: '2024年1月1日'
    };
  },
  onLoad() {
    // 获取系统信息
    const systemInfo = uni.getSystemInfoSync();
    this.statusBarHeight = systemInfo.statusBarHeight;
  }
};
</script>

<style scoped>
.privacy-container {
  min-height: 100vh;
  background-color: #f8f8f8;
}

.privacy-content {
  height: calc(100vh - 44px);
  padding: 0 20px 20px;
  box-sizing: border-box;
}

.content-wrapper {
  background-color: #ffffff;
  border-radius: 10px;
  padding: 20px;
  margin-bottom: 20px;
}

.update-time {
  font-size: 12px;
  color: #999999;
  text-align: center;
  margin-bottom: 20px;
  padding-bottom: 15px;
  border-bottom: 1px solid #f0f0f0;
}

.section {
  margin-bottom: 25px;
}

.section:last-child {
  margin-bottom: 0;
}

.section-title {
  display: block;
  font-size: 16px;
  font-weight: 600;
  color: #333333;
  margin-bottom: 10px;
  line-height: 1.5;
}

.section-content {
  display: block;
  font-size: 14px;
  color: #666666;
  line-height: 1.6;
  white-space: pre-line;
}
</style>
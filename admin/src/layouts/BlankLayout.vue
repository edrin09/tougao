<template>
  <div class="blank-layout">
    <router-view />
  </div>
</template>

<script setup>
// 空白布局，仅用于登录等认证页面
</script>

<style scoped>
.blank-layout {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}
</style> 
<template>
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <h1 class="login-title">校园墙后台管理</h1>
        <p class="login-subtitle">请使用管理员账号登录</p>
      </div>

      <el-form :model="form" :rules="rules" ref="formRef" class="login-form" size="large">

        <el-form-item prop="username">
          <el-input v-model="form.username" placeholder="请输入用户名" prefix-icon="User" clearable />
        </el-form-item>

        <el-form-item prop="password">
          <el-input v-model="form.password" type="password" placeholder="请输入密码" prefix-icon="Lock" show-password
            @keyup.enter="handleLogin" />
        </el-form-item>

        <el-form-item>
          <el-button type="primary" class="login-btn" @click="handleLogin" :loading="loading">
            {{ loading ? '登录中...' : '登录' }}
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup>
  import { reactive, ref } from 'vue';
  import { ElMessage } from 'element-plus';
  import { useRouter } from 'vue-router';
  import { API } from '../api';
  import { useAuthStore } from '../store/auth';

  const router = useRouter();
  const auth = useAuthStore();
  const loading = ref(false);
  const formRef = ref();

  const form = reactive({
    username: '',
    password: ''
  });

  const rules = {
    username: [
      { required: true, message: '请输入用户名', trigger: 'blur' },
      { min: 2, max: 20, message: '用户名长度在 2 到 20 个字符', trigger: 'blur' }
    ],
    password: [
      { required: true, message: '请输入密码', trigger: 'blur' },
      { min: 6, message: '密码长度不能少于 6 位', trigger: 'blur' }
    ],
  };

  async function handleLogin() {
    try {
      await formRef.value.validate();
      loading.value = true;

      const res = await API.admin.login(form);

      if (res.code === 200 || res.code === 1) {
        // 兼容不同的成功状态码
        const data = res.data || res;
        auth.setAuth(data.token, data.adminInfo || data.user || {});
        ElMessage.success('登录成功');
        router.push('/dashboard');
      } else {
        ElMessage.error(res.message || res.msg || '登录失败');
      }
    } catch (error) {
      console.error('登录错误:', error);
      ElMessage.error('登录失败，请检查网络连接');
    } finally {
      loading.value = false;
    }
  }


</script>

<style scoped>
  .login-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 20px;
  }

  .login-card {
    width: 400px;
    background: white;
    border-radius: 12px;
    padding: 40px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  }

  .login-header {
    text-align: center;
    margin-bottom: 40px;
  }

  .login-title {
    font-size: 24px;
    font-weight: 600;
    color: #303133;
    margin-bottom: 8px;
  }

  .login-subtitle {
    font-size: 14px;
    color: #909399;
    margin: 0;
  }

  .login-form {
    margin: 0;
  }

  .login-btn {
    width: 100%;
    height: 44px;
    font-size: 16px;
    border-radius: 6px;
  }

  :deep(.el-input__wrapper) {
    border-radius: 6px;
  }

  :deep(.el-form-item) {
    margin-bottom: 24px;
  }

  :deep(.el-form-item:last-child) {
    margin-bottom: 0;
  }
</style>
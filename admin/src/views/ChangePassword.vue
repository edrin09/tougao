<template>
  <div class="change-pwd-page">
    <el-card header="修改密码" class="w-96 mx-auto mt-20">
      <el-form ref="pwdFormRef" :model="pwdForm" :rules="pwdRules" label-width="100px">
        <el-form-item label="旧密码" prop="old">
          <el-input v-model="pwdForm.old" type="password" show-password />
        </el-form-item>
        <el-form-item label="新密码" prop="new">
          <el-input v-model="pwdForm.new" type="password" show-password />
        </el-form-item>
        <el-form-item label="确认密码" prop="confirm">
          <el-input v-model="pwdForm.confirm" type="password" show-password />
        </el-form-item>
      </el-form>
      <template #footer>
        <div class="flex justify-end gap-3">
          <el-button @click="$router.back()">取消</el-button>
          <el-button type="primary" :loading="loading" @click="submit">保存</el-button>
        </div>
      </template>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { ElMessage } from 'element-plus';
import { API } from '../api';
import { useAuthStore } from '../store/auth';

const auth = useAuthStore();
const loading = ref(false);
const pwdFormRef = ref(null);
const pwdForm = reactive({ old: '', new: '', confirm: '' });

const pwdRules = {
  old: [{ required: true, message: '请输入旧密码', trigger: 'blur' }],
  new: [
    { required: true, message: '请输入新密码', trigger: 'blur' },
    { min: 6, message: '最少 6 位', trigger: 'blur' }
  ],
  confirm: [
    { required: true, message: '请确认新密码', trigger: 'blur' },
    {
      validator: (rule, value) => value === pwdForm.new,
      message: '两次密码不一致',
      trigger: 'blur'
    }
  ]
};

async function submit() {
  await pwdFormRef.value.validate();
  loading.value = true;
  try {
    const res = await API.admin.updatePassword({
      old_password: pwdForm.old,
      new_password: pwdForm.new,
      confirm_password: pwdForm.confirm
    });
    if (res.code === 200 || res.code === 1) {
      ElMessage.success('密码修改成功，请重新登录');
      await API.admin.logout();
      auth.logout();
      location.href = '#/login';
    } else {
      ElMessage.error(res.message || '修改失败');
    }
  } catch (e) {
    ElMessage.error(e?.message || '修改失败');
  } finally {
    loading.value = false;
  }
}
</script>

<style scoped>
.change-pwd-page {
  display: flex;
  justify-content: center;
}
</style> 
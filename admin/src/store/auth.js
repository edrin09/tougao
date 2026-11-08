import { defineStore } from 'pinia';
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('adminToken') || '');
  const user = ref(JSON.parse(localStorage.getItem('adminInfo') || '{}'));

  function setAuth(tk, info) {
    token.value = tk;
    user.value = info;
    localStorage.setItem('adminToken', tk);
    localStorage.setItem('adminInfo', JSON.stringify(info));
  }

  function logout() {
    token.value = '';
    user.value = {};
    localStorage.removeItem('adminToken');
    localStorage.removeItem('adminInfo');
    ElMessage.success('已退出登录');
  }

  return { token, user, setAuth, logout };
}); 
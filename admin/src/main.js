import { createApp } from 'vue';
import ElementPlus from 'element-plus';
import 'element-plus/dist/index.css';
import '@wangeditor/editor/dist/css/style.css';
import * as Icons from '@element-plus/icons-vue';
import zhCn from 'element-plus/dist/locale/zh-cn.mjs';

import App from './App.vue';
import router from './router';
import { createPinia } from 'pinia';
import { useAuthStore } from './store/auth';

import api from './api';

const app = createApp(App);

// 注册全部图标为全局组件
for (const [key, component] of Object.entries(Icons)) {
  app.component(key, component);
}

app.use(ElementPlus, { locale: zhCn });
app.use(router);

// Pinia
const pinia = createPinia();
app.use(pinia);

// 设置axios token
const authStore = useAuthStore();
api.interceptors.request.use((config) => {
  if (authStore.token) {
    config.headers.Authorization = `Bearer ${authStore.token}`;
  }
  return config;
});

app.mount('#app'); 
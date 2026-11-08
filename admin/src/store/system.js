import { defineStore } from 'pinia';
import { ref } from 'vue';
import { ElMessage } from 'element-plus';

export const systemStore = defineStore('sysConfig', () => {
  const sysConfig = ref(JSON.parse(localStorage.getItem('sysConfig') || '{}'));

  function setConfig(config) {
    sysConfig.value = config;
    localStorage.setItem('sysConfig', JSON.stringify(config));
  }

  return { setConfig, sysConfig };
}); 
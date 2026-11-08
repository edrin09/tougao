// #ifndef VUE3
import Vue from 'vue'
import App from './App'
import api from './api'

// 将API挂载到Vue原型上，方便在页面中通过this.$api访问
Vue.prototype.$api = api

Vue.config.productionTip = false

App.mpType = 'app'

const app = new Vue({
    ...App
})
app.$mount()
// #endif

// #ifdef VUE3
import { createSSRApp } from 'vue'
import App from './App.vue'
import api from './api'

export function createApp() {
  const app = createSSRApp(App)
  // 将API挂载到应用全局属性上，在页面中通过app.config.globalProperties.$api访问
  app.config.globalProperties.$api = api
  return {
    app
  }
}
// #endif
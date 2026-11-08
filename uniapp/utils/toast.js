// Toast 工具类
class ToastManager {
  constructor() {
    this.toastInstance = null
    this.isInitialized = false
  }
  
  // 初始化Toast实例
  init(vueInstance) {
    if (!vueInstance) {
      console.error('Toast初始化失败：需要传入Vue实例')
      return
    }
    
    this.toastInstance = vueInstance
    this.isInitialized = true
  }
  
  // 显示Toast
  show(options) {
    if (!this.isInitialized || !this.toastInstance) {
      console.error('Toast未初始化，请先调用Toast.init()')
      return
    }
    
    if (typeof options === 'string') {
      options = { message: options }
    }
    
    this.toastInstance.show(options)
  }
  
  // 成功提示
  success(message, options = {}) {
    this.show({
      message,
      type: 'success',
      ...options
    })
  }
  
  // 错误提示
  error(message, options = {}) {
    this.show({
      message,
      type: 'error',
      ...options
    })
  }
  
  // 警告提示
  warning(message, options = {}) {
    this.show({
      message,
      type: 'warning',
      ...options
    })
  }
  
  // 信息提示
  info(message, options = {}) {
    this.show({
      message,
      type: 'info',
      ...options
    })
  }
  
  // 隐藏Toast
  hide() {
    if (this.toastInstance) {
      this.toastInstance.hide()
    }
  }
}

// 创建单例
const Toast = new ToastManager()

// 导出
export default Toast

// 也可以按需导出各个方法
export const showToast = Toast.show.bind(Toast)
export const successToast = Toast.success.bind(Toast)
export const errorToast = Toast.error.bind(Toast)
export const warningToast = Toast.warning.bind(Toast)
export const infoToast = Toast.info.bind(Toast)
export const hideToast = Toast.hide.bind(Toast)
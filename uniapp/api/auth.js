import { login } from './user';
import { get, post } from '../utils/request';

/**
 * 执行登录
 * @param {String} code - 微信登录code
 * @returns {Promise} 登录结果
 */
export const doLogin = async () => {
  // 显示加载提示
  uni.showLoading({
    title: '登录中...'
  });

  try {
    // 执行登录逻辑
    const res = await uni.login();
    const result = await login(res.code);

    if (result.code==200) {
      // 保存用户信息
      uni.setStorageSync("userInfo", result.data);
      console.log("登录成功");
      return { success: true, data: result.data };
    } else {
      console.log("登录失败", result.message);
      return { success: false, message: result.message };
    }
  } catch (error) {
    console.error("登录异常", error);
    return { success: false, message: "登录异常", error };
  } finally {
    uni.hideLoading();
  }
};

/**
 * 检查是否已授权
 * @returns {Boolean} 是否已授权
 */
export const checkAuth = () => {
  const userInfo = uni.getStorageSync("userInfo");
  return userInfo && userInfo.avatarUrl;
};

/**
 * 获取用户信息
 * @returns {Object|null} 用户信息
 */
export const getUserInfo = () => {
  try {
    return uni.getStorageSync("userInfo");
  } catch (error) {
    console.error("获取用户信息失败", error);
    return null;
  }
};

/**
 * 退出登录
 */
export const logout = () => {
  uni.removeStorageSync("userInfo");
};

export default {
  doLogin,
  checkAuth,
  getUserInfo,
  logout
}; 
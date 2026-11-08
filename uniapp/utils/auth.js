/**
 * 检查用户是否已授权
 * @returns {Boolean} 是否已授权
 */
export const checkUserAuth = () => {
  const userInfo = uni.getStorageSync('userInfo');
  return userInfo && userInfo.avatarUrl;
};

/**
 * 获取用户信息
 * @returns {Object|null} 用户信息
 */
export const getUserInfo = () => {
  return uni.getStorageSync('userInfo');
};

export default {
  checkUserAuth,
  getUserInfo
}; 
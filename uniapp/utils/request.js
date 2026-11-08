/**
 * 网络请求封装
 */

// 基础URL
const BASE_URL = "https://tougao.njlinks.cn";
// const BASE_URL = "https://tougao.lin-xin.com";

// 错误码映射
const ERROR_MESSAGE = {
  400: "请求参数错误",
  401: "未授权或登录已过期",
  403: "权限不足",
  404: "请求资源不存在",
  500: "服务器内部错误",
};

/**
 * 获取用户openid
 * @returns {String} openid
 */
const getOpenId = () => {
  const userInfo = uni.getStorageSync("userInfo");
  return userInfo ? userInfo.openId : "";
};

/**
 * 请求封装
 * @param {Object} options - 请求配置
 * @returns {Promise} - 返回Promise对象
 */
const request = (options) => {
  return new Promise((resolve, reject) => {
    // 获取openid
    const openid = getOpenId();

    // 确保url以/开头
    const apiUrl = options.url.startsWith("/")
      ? options.url
      : "/" + options.url;

    // 组合请求参数
    const requestOptions = {
      url: BASE_URL + apiUrl,
      data: {
        ...options.data,
        // 自动添加openid认证参数
        ...(options.auth !== false && openid ? { openid } : {}),
      },
      method: options.method || "GET",
      header: {
        "content-type": options.contentType || "application/json",
        ...options.header,
      },
    };

    uni.request({
      ...requestOptions,
      success: (res) => {
        const { data } = res;

        // 处理业务错误
        if (data.code === 401) {
          // 清除用户信息
          uni.removeStorageSync("userInfo");

          // 跳转到登录页
          setTimeout(() => {
            uni.navigateTo({
              url: "/pages/login/login",
            });
          }, 1500);

          reject(new Error("未登录或登录已过期"));
        } else if (data.code !== 200) {
          reject(new Error(data.msg || "请求失败"));
        } else {
          resolve(data);
        }
      },
      fail: (error) => {
        console.error("请求失败:", error);
        reject(error);
      },
    });
  });
};

/**
 * GET请求
 * @param {String} url - 请求地址
 * @param {Object} data - 请求参数
 * @param {Object} options - 其他配置
 * @returns {Promise} - 返回Promise对象
 */
export const get = (url, data = {}, options = {}) => {
  return request({
    url,
    data: {
      ...data,
      openid: getOpenId(),
    },
    method: "GET",
    ...options,
  });
};

/**
 * POST请求
 * @param {String} url - 请求地址
 * @param {Object} data - 请求参数
 * @param {Object} options - 其他配置
 * @returns {Promise} - 返回Promise对象
 */
export const post = (url, data = {}, options = {}) => {
  return request({
    url,
    data: {
      ...data,
      openid: getOpenId(),
    },
    method: "POST",
    ...options,
  });
};

/**
 * 上传文件请求
 * @param {String} url - 上传地址
 * @param {String} filePath - 文件路径
 * @param {String} name - 文件对应的 key
 * @param {Object} formData - 额外的表单数据
 * @param {Object} options - 其他配置
 * @returns {Promise} - 返回Promise对象
 */
export const upload = (
  url,
  filePath,
  name = "file",
  formData = {},
  options = {}
) => {
  return new Promise((resolve, reject) => {
    // 获取openid
    const openid = getOpenId();

    // 如果有openid，添加到表单数据中
    if (openid) {
      formData.openid = openid;
    }

    // 确保url以/开头
    const apiUrl = url.startsWith("/") ? url : "/" + url;

    uni.uploadFile({
      url: BASE_URL + apiUrl,
      filePath,
      name,
      formData,
      success: (res) => {
        try {
          const data = JSON.parse(res.data);
          if (data.code === 200) {
            resolve(data);
          } else {
            reject(new Error(data.msg || "上传失败"));
          }
        } catch (error) {
          reject(new Error("解析响应数据失败"));
        }
      },
      fail: (error) => {
        reject(error);
      },
    });
  });
};

export default {
  request,
  get,
  post,
  upload,
};

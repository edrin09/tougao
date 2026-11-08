/**
 * 上传模块API
 */

import { get, upload } from '../utils/request';

/**
 * 获取上传签名
 * @param {String} fileType - 文件类型：image/video
 * @param {String} fileName - 文件名
 * @returns {Promise} - 返回Promise对象
 */
export const getUploadSign = (fileType, fileName) => {
  return get('/api/upload/sign', { fileType, fileName });
};

/**
 * 上传图片
 * @param {String} filePath - 本地图片路径
 * @param {Object} formData - 额外的表单数据
 * @returns {Promise} - 返回Promise对象
 */
export const uploadImage = (filePath, formData = {}) => {
  return upload("/api/upload/image", filePath, "file", formData);
};

/**
 * 选择并上传图片
 * @param {Object} options - 选择图片的配置参数
 * @returns {Promise} - 返回上传后的图片URL
 */
export const chooseAndUploadImage = (options = {}) => {
  return new Promise((resolve, reject) => {
    uni.chooseImage({
      count: options.count || 1,
      sizeType: options.sizeType || ['original', 'compressed'],
      sourceType: options.sourceType || ['album', 'camera'],
      success: (res) => {
        const tempFilePath = res.tempFilePaths[0];
        
        // 显示上传中
        uni.showLoading({
          title: '上传中...'
        });
        
        // 调用上传API
        uploadImage(tempFilePath)
          .then(result => {
            uni.hideLoading();
            resolve(result.data.url);
          })
          .catch(err => {
            uni.hideLoading();
            reject(err);
          });
      },
      fail: (err) => {
        reject(err);
      }
    });
  });
};

export default {
  getUploadSign,
  uploadImage,
  chooseAndUploadImage
}; 
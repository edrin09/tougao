/**
 * 投稿模块API
 */

import { get, post } from '../utils/request';

/**
 * 创建投稿
 * @param {Object} postData - 投稿数据
 * @param {String} postData.wallOwnerId - 墙主ID
 * @param {String} postData.title - 投稿标题
 * @param {String} postData.content - 投稿内容
 * @param {Array} postData.images - 图片URL数组
 * @param {Array} postData.tags - 标签ID数组
 * @param {String} postData.bgColor - 背景颜色
 * @param {String} postData.openId - 用户ID
 * @returns {Promise} - 返回Promise对象
 */
export const createPost = (postData) => {
  console.log(postData, "postData");
  return post("/api/post/create", postData);
};

/**
 * 获取投稿列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {Number} params.status - 状态筛选
 * @param {String} params.userId - 用户ID
 * @param {String} params.wallOwnerId - 墙主ID
 * @param {Number} params.tagId - 标签ID
 * @param {String} params.keyword - 关键词搜索
 * @returns {Promise} - 返回Promise对象
 */
export const getPostList = (params = {}) => {
  return get('/api/post/list', params);
};

/**
 * 获取投稿详情
 * @param {String} out_trade_no - 投稿ID
 * @returns {Promise} - 返回Promise对象
 */
export const getPostDetail = (out_trade_no) => {
  return get('/api/post/detail', { out_trade_no });
};

/**
 * 审核投稿
 * @param {Object} reviewData - 审核数据
 * @param {String} reviewData.postId - 投稿ID
 * @param {Number} reviewData.status - 审核状态：1通过，-1拒绝
 * @param {String} reviewData.comment - 审核意见
 * @returns {Promise} - 返回Promise对象
 */
export const reviewPost = (reviewData) => {
  return post('/api/post/review', reviewData);
};

/**
 * 批量审核投稿
 * @param {Object} batchReviewData - 批量审核数据
 * @param {Array} batchReviewData.postIds - 投稿ID数组
 * @param {Number} batchReviewData.status - 审核状态：1通过，-1拒绝
 * @param {String} batchReviewData.comment - 审核意见
 * @returns {Promise} - 返回Promise对象
 */
export const batchReviewPosts = (batchReviewData) => {
  return post('/api/post/batchReview', batchReviewData);
};

/**
 * 转审核（将未通过的投稿重新转为待审核状态）
 * @param {Object} moveData - 转审核数据
 * @param {String} moveData.postId - 投稿ID
 * @param {String} moveData.comment - 转审核说明
 * @returns {Promise} - 返回Promise对象
 */
export const moveToReview = (moveData) => {
  return post('/api/post/moveToReview', moveData);
};

/**
 * 删除投稿
 * @param {String} postId - 投稿ID
 * @returns {Promise} - 返回Promise对象
 */
export const deletePost = (postId) => {
  return post('/api/post/delete', { postId });
};

/**
 * 获取墙主待审核投稿列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {String} params.wallOwnerId - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerPendingPosts = (params = {}) => {
  return get('/api/post/getPendingPosts', params);
};

/**
 * 获取墙主未通过投稿列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {String} params.wallOwnerId - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerRejectedPosts = (params = {}) => {
  return get('/api/post/getRejectedPosts', params);
};

/**
 * 获取墙主已通过投稿列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {String} params.wallOwnerId - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerApprovedPosts = (params = {}) => {
  return get('/api/post/getApprovedPosts', params);
};

/**
 * 获取我的投稿列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.pageSize - 每页数量
 * @param {String} params.status - 状态筛选 (pending/approved/rejected)
 * @returns {Promise} - 返回Promise对象
 */
export const getMyPosts = (params = {}) => {
  return get('/api/post/myPosts', params);
};

/**
 * 更新投稿
 * @param {Object} postData - 投稿数据
 * @param {String} postData.id - 投稿ID
 * @param {String} postData.title - 投稿标题
 * @param {String} postData.content - 投稿内容
 * @param {Array} postData.images - 图片URL数组
 * @param {String} postData.tag - 标签
 * @param {String} postData.contact - 联系方式
 * @returns {Promise} - 返回Promise对象
 */
export const updatePost = (postData) => {
  return post('/api/post/update', postData);
};

// **生成海报**
export const generatePoster = (postData) => {
  return post('/api/post/generatePoster', postData);
};

// batchMoveToReview
export const batchMoveToReview = (postData) => {
  return post('/api/post/batchMoveToReview', postData);
};

const postApi = {
  createPost,
  getPostList,
  getPostDetail,
  reviewPost,
  batchReviewPosts,
  moveToReview,
  deletePost,
  getWallOwnerPendingPosts,
  getWallOwnerRejectedPosts,
  getWallOwnerApprovedPosts,
  getMyPosts,
  updatePost,
  generatePoster,
  batchMoveToReview
};

export default postApi;
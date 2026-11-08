/**
 * 管理模块API
 */

import { get, post } from '../utils/request';

/**
 * 生成分享二维码
 * @param {String} page - 跳转页面路径
 * @param {String} scene - 场景参数
 * @returns {Promise} - 返回Promise对象
 */
export const generateQRCode = (page, scene) => {
  return get('/api/wallowner/generateQRCode', { page, scene });
};

/**
 * 生成短链接
 * @param {String} url - 原始链接
 * @returns {Promise} - 返回Promise对象
 */
export const generateShortLink = (url) => {
  return post('/api/wallowner/generateShortLink', { url });
};

/**
 * 生成墙主专属短链
 * @param {Object} linkData - 链接数据
 * @param {String} linkData.wall_id - 墙主用户ID
 * @param {String} linkData.customCode - 自定义短链代码
 * @param {Number} linkData.validDays - 有效期天数
 * @returns {Promise} - 返回Promise对象
 */
export const generateWallOwnerLink = (linkData) => {
  return post('/api/wallowner/generateWallOwnerLink', linkData);
};

/**
 * 获取墙主专属链接列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {String} params.keyword - 关键词搜索
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerLinks = (params = {}) => {
  return get('/api/wallowner/wallOwnerLinks', params);
};

/**
 * 获取数据统计
 * @param {Object} params - 查询参数
 * @param {String} params.startDate - 开始日期
 * @param {String} params.endDate - 结束日期
 * @param {String} params.wall_id - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const getStatistics = (params = {}) => {
  return get('/api/wallowner/statistics', params);
};

/**
 * 获取墙主设置
 * @param {String} wall_id - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const getWallSettings = (wall_id) => {
  const params = wall_id ? { wall_id } : {};
  return get('/api/wallowner/getSettings', params);
};

/**
 * 更新墙主设置
 * @param {Object} settingsData - 设置数据
 * @returns {Promise} - 返回Promise对象
 */
export const updateWallSettings = (settingsData) => {
  return post('/api/wallowner/updateWallSettings', settingsData);
};

/**
 * 申请成为墙主
 * @param {Object} applyData - 申请数据
 * @param {String} applyData.schoolName - 学校名称
 * @param {String} applyData.wallName - 墙名称
 * @param {String} applyData.description - 描述
 * @param {String} applyData.contactPhone - 联系电话
 * @param {String} applyData.contactWechat - 联系微信
 * @param {String} applyData.applyReason - 申请理由
 * @param {String} applyData.studentProofUrl - 学生证明材料URL
 * @returns {Promise} - 返回Promise对象
 */
export const applyWallOwner = (applyData) => {
  return post('/api/wallowner/applyWallOwner', applyData);
};

/**
 * 审核墙主申请
 * @param {Object} reviewData - 审核数据
 * @param {String} reviewData.applyId - 申请ID
 * @param {Number} reviewData.status - 审核状态
 * @param {String} reviewData.comment - 审核意见
 * @param {String} reviewData.wall_id - 墙主ID
 * @returns {Promise} - 返回Promise对象
 */
export const reviewWallOwnerApply = (reviewData) => {
  return post('/api/wallowner/reviewWallOwnerApply', reviewData);
};

/**
 * 获取墙主申请列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.size - 每页数量
 * @param {Number} params.status - 状态筛选
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerApplyList = (params = {}) => {
  return get('/api/wallowner/wallOwnerApplyList', params);
};

/**
 * 获取墙主的订阅消息模板
 * @returns {Promise} - 返回Promise对象
 */
export const getSubscribeTemplates = () => {
  return get('/api/wallowner/getSubscribeTemplates');
};

/**
 * 获取审核通知模板
 * @returns {Promise} - 返回Promise对象
 */
export const getReviewResultTemplate = () => {
  return get('/api/wallowner/getReviewResultTemplate');
};

/**
 * 获取回复通知
 */
export const getReplyNoticeTemplate = () => {
  return get('/api/wallowner/getReplyNoticeTemplate');
};

// 生成外链
export const generateShareLink = (data) => {
  return post('/api/wallowner/generateUrlLink', data);
};


/**
 * @function getChannelList 获取渠道列表
 * @returns {Promise}
 * 响应示例
 * {
    "code": 200,
    "msg": "获取成功",
    "data": [
        {
            "channels_id": 1,
            "channels_name": "微信"
        },
        {
            "channels_id": 2,
            "channels_name": "抖音"
        },
        {
            "channels_id": 3,
            "channels_name": "小红是"
        }
    ],
    "url": null,
    "wait": 3
}
 */
export const getChannelList = () => {
  return get('/api/wallowner/getChannelList');
};


/**
 * @function saveChannelInfo 保存墙主渠道配置
 * @param {Object} data 请求参数
 * @param {Array} data.channels 渠道
 * 参数示例
 * [{channels_id,channel_account,channel_fans_numbers,channel_fans_costs}]
 * @returns {Promise}
 */
export const saveChannelInfo = (data) => {
  return post('/api/wallowner/saveChannelInfo', data);
};

/**
 * @function detail 获取墙主佣金明细
 * @param {Object}  params 请求参数
 * @param {String}  params.page	页码
 * @param {String}  params.limit	分页大小
 * @param {String}  params.start_date 开始时间
 * @param {String}  params.end_date	结束时间
 * @param {String}  params.keyword	搜索词
 * @returns {Promise}
 * 响应示例
 * {
    "code": 200,
    "msg": "获取成功",
    "data": {
        "list": [
            {
                "post_id": "post_685d2b39a810b",
                "title": "六级资料！",
                "images": [
                    "https://tougao.lin-xin.com/public/uploads/images/20250626/b4b20c1d408d95a1320a104d4bcfef41.jpg",
                    "https://tougao.lin-xin.com/public/uploads/images/20250626/ed0da299f847a3b84626d2f4c4526e14.jpg",
                    "https://tougao.lin-xin.com/public/uploads/images/20250626/7164d3ff65f231b4bc3f699252f5d307.jpg",
                    "https://tougao.lin-xin.com/public/uploads/images/20250626/4eae3a8c815bfcb2681249aa15dadf4c.jpg"
                ],
                "commission": 5.2
            }
        ],
        "total": 1,
        "page": 1,
        "limit": 10,
        "hasMore": false
    },
    "url": null,
    "wait": 3
}
 */
export const detail = (params) => {
  return get('/api/wallowner/detail', params);
};
const wallownerApi = {
  generateQRCode,
  generateShortLink,
  generateWallOwnerLink,
  getWallOwnerLinks,
  getStatistics,
  getWallSettings,
  updateWallSettings,
  applyWallOwner,
  reviewWallOwnerApply,
  getWallOwnerApplyList,
  getSubscribeTemplates,
  getReviewResultTemplate,
  getReplyNoticeTemplate,
  generateShareLink,
  getChannelList,
  saveChannelInfo,
  detail
};

export default wallownerApi;
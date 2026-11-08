/**
 * 用户模块API
 */

import {
	get,
	post
} from '../utils/request';

/**
 * 微信登录
 * @param {String} code - 微信登录临时凭证
 * @returns {Promise} - 返回Promise对象
 */
export const login = (code) => {
	return post('/api/user/login', {
		code
	});
};

/**
 * 微信登录
 * @param {String} open_id - 用户OpenID
 * @returns {Promise} - 返回Promise对象
 */
export const loginWithOpenid = (open_id) => {
	return post('/api/user/loginWithOpenid', {
		open_id
	});
};


/**
 * 更新用户信息
 * @param {Object} userInfo - 用户信息
 * @param {String} userInfo.nickname - 用户昵称
 * @param {String} userInfo.avatar - 用户头像URL
 * @param {Number} userInfo.wall_id - 绑定的墙ID
 * @param {Number} userInfo.community_id - 绑定的学校ID
 * @returns {Promise} - 返回Promise对象
 */
export const updateUserInfo = (data) => {
	return post('/api/user/update', data);
};

/**
 * 获取用户信息
 * @returns {Promise} - 返回Promise对象
 */
export const getUserInfo = () => {
	return get('/api/user/info');
};

/**
 * 获取墙主设置
 * @returns {Promise} - 返回Promise对象
 */
export const getWallOwnerSetting = () => {
	return get('/api/wallowner/wallSettings');
};

/**
 * 获取随机头像
 * @returns {Promise} - 返回Promise对象
 */
export const getRandomAvatar = () => {
	return get('/api/user/randomAvatar');
};

/**
 * 获取默认墙主
 * @returns {Promise} - 返回Promise对象
 */
export const getDefaultWallMaster = () => {
	return get('/api/user/getDefaultWallMaster');
};

// /user/getAnonymousPostSetting
export const getAnonymousPostSetting = () => {
	return get('/api/user/getAnonymousPostSetting');
};

/**
 * 获取协议内容
 * @param {Object} params - 查询参数
 * @param {Number} params.type - 标签ID
 * @returns {Promise} - 返回Promise对象
 */
export const getUserAgreement = (params = {}) => {
	return get('/api/user/getUserAgreement', params);
};

/**
 * @function getWallList 获取墙列表
 * @param {Object} params - 查询参数
 * @param {Number} params.community_id - 学校id（必填）
 * @param {Number} params.page - 页码（可选，默认1）
 * @param {Number} params.limit - 每页数量（可选，默认10）
 * @returns {Promise}
 * 响应示例：
 * {
		"code": 200,
		"msg": "获取成功",
		"data": {
			"list": [
				{
					"id": 1,
					"wall_name": "默笛1",
					"community_id": 1
				},
				{
					"id": 2,
					"wall_name": "渭南师范学院万能墙",
					"community_id": 1
				}
			],
			"total": 10,
			"page": "1",
			"limit": "10",
			"hasMore": false
		},
		"url": null,
		"wait": 3
	}
 */
export const getWallList = (params = {}) => {
	// 确保传递学校ID参数
	if (!params.community_id) {
		console.warn('getWallList: community_id 参数缺失');
	}
	return get('/api/user/getWallList', params);
};

/**
 * @function getCommunityList 获取学校列表
 * @param {Object} params - 查询参数
 * @param {Number} params.page - 页码
 * @param {Number} params.limit - 每页数量
 * @returns {Promise} 
 * 响应示例：
 * {
		"code": 200,
		"msg": "获取成功",
		"data": {
			"list": [
				{
					"community_id": 1,
					"community_name": "默认学校"
				},
				{
					"community_id": 2,
					"community_name": "渭南师范学院"
				}
			],
			"total": 10,
			"page": "1",
			"limit": "10",
			"hasMore": false
		},
		"url": null,
		"wait": 3
	}
 */
export const getCommunityList = (params = {}) => {
	return get('/api/user/getCommunityList', params);
};

/**
 * @function getWallInfo 获取墙详细信息
 * @param {Object} params - 查询参数
 * @param {Number} params.wall_id - 墙ID
 * @returns {Promise}
 * 响应示例：
 * {
		"code": 200,
		"msg": "获取成功",
		"data": {
			"id": 1,
			"wall_name": "默笛1",
			"community_id": 1,
			"community_name": "默认学校"
		}
	}
 */
export const getWallInfo = (params = {}) => {
	return get('/api/user/getWallInfo', params);
};

export default {
	login,
	loginWithOpenid,
	updateUserInfo,
	getUserInfo,
	getRandomAvatar,
	getDefaultWallMaster,
	getAnonymousPostSetting,
	getUserAgreement,
	getWallList,
	getCommunityList,
	getWallInfo
};
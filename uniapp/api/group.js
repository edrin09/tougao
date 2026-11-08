/**
 * 群聊设置相关API
 */
import { get, post } from '../utils/request';

export default {
  // 获取群聊列表
  getGroupList: (data) => {
    return get('/api/groupchat/list', data);
  },

  // 创建群聊
  createGroup: (data) => {
    return post('/api/groupchat/create', data);
  },

  // 更新群聊
  updateGroup: (data) => {
    return post('/api/groupchat/update', data);
  },

  // 删除群聊
  deleteGroup: (data) => {
    return post('/api/groupchat/delete', data);
  },

  // 获取群聊详情
  getGroupDetail: (data) => {
    return get('/api/groupchat/detail', data);
  },

  // 批量更新权重
  batchUpdateWeight: (data) => {
    return post('/api/groupchat/batchUpdateWeight', data);
  }
};

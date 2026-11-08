import { get, post } from '../utils/request';

const comment = {
  // 获取评论列表
  getCommentList(params) {
    return get('/api/comment/list', params);
  },

  // 添加评论
  addComment(data) {
    return post('/api/comment/add', data);
  },

  // 删除评论
  deleteComment(data) {
    return post('/api/comment/delete', data);
  },

  // 获取墙主信息
  getWallInfo(params) {
    return get('/api/comment/wallInfo', params);
  },

  // 获取管理员评论列表
  getList(params) {
    return get('/admin/comment/list', params);
  },

  // 审核评论 - 通过
  approve(commentId) {
    return post(`/admin/comment/review/${commentId}`, { action: 'approve' });
  },

  // 审核评论 - 驳回
  reject(commentId) {
    return post(`/admin/comment/review/${commentId}`, { action: 'reject' });
  },

  // 恢复评论
  restore(commentId) {
    return post(`/admin/comment/restore/${commentId}`);
  },

  // 批量审核评论
  batchApprove(data) {
    return post('/admin/comment/batch-review', data);
  },

  // 获取评论统计
  getStats() {
    return get('/admin/comment/stats');
  },

  // 初始化测试数据
  initTestData() {
    return post('/admin/comment/init-test-data');
  }
};

export default comment;

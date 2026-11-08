import { get, post } from '../utils/request';

const dashboard = {
  // 获取仪表盘统计数据
  getStats() {
    return get('/admin/dashboard/stats');
  },

  // 获取最近活动
  getRecentActivity() {
    return get('/admin/dashboard/recent-activity');
  },

  // 获取系统信息
  getSystemInfo() {
    return get('/admin/dashboard/system-info');
  }
};

export default dashboard;

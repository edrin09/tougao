import { get, post } from '../utils/request';

// 获取最新的通知
export const getLatestNotifications = (limit = 5, type = 1) => {
  return get('/api/notification/latest', { limit, type });
};


const notificationApi = {
  getLatestNotifications
};

export default notificationApi;


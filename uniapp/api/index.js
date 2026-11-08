/**
 * API模块入口文件
 * 统一导出所有API
 */

import userApi from './user';
import postApi from './post';
import uploadApi from './upload';
import wallownerApi from './wallowner';
import commentApi from './comment';
import dashboardApi from './dashboard';
import groupApi from './group';
import notificationApi from './notification';
import guideApi from './guide';
import withdrawApi from './withdraw';

export const api = {
  user: userApi,
  post: postApi,
  upload: uploadApi,
  wallowner: wallownerApi,
  comment: commentApi,
  dashboard: dashboardApi,
  group: groupApi,
  notification: notificationApi,
  guide: guideApi,
  withdraw:withdrawApi
};

export default api;
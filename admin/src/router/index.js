import { createRouter, createWebHashHistory } from 'vue-router';
import { useAuthStore } from '../store/auth';

const routes = [
  // 空白布局 - 用于登录等认证页面
  {
    path: '/login',
    component: () => import('../layouts/BlankLayout.vue'),
    children: [
      { path: '', component: () => import('../views/Login.vue') }
    ]
  },
  // 管理后台布局 - 需要认证的业务页面
  {
    path: '/',
    component: () => import('../layouts/AdminLayout.vue'),
    meta: { requiresAuth: true },
    children: [
      { path: '', redirect: '/dashboard' },
      { path: 'dashboard', component: () => import('../views/Dashboard.vue') },
      { path: 'wall-master-apply', component: () => import('../views/WallMasterApply.vue') },
      { path: 'post-manage', component: () => import('../views/PostManage.vue') },
      { path: 'comment-manage', component: () => import('../views/CommentManage.vue') },
      { path: 'notification-manager', component: () => import('../views/NotificationManage.vue') },
      { path: 'change-password', component: () => import('../views/ChangePassword.vue') },
      { path: 'tutorial', component: () => import('../views/Tutorial.vue') },
      { path: 'user-agreement', component: () => import('../views/UserAgreement.vue') },
      { path: 'withdraw-deposit', component: () => import('../views/WallMasterWithdraw.vue') },
      { path: 'user-manage', component: () => import('../views/UserManage.vue') },
      { path: 'community-manage', component: () => import('../views/CommunityManage.vue') },
      { path: 'channels-manage', component: () => import('../views/ChannelsManage.vue') },
    ]
  }
];

const router = createRouter({
  history: createWebHashHistory(),
  routes
});

// 路由守卫
router.beforeEach((to) => {
  const auth = useAuthStore();
  if (to.meta.requiresAuth && !auth.token) {
    return '/login';
  }
});

export default router; 
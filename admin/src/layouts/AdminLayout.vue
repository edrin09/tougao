<template>
  <el-config-provider namespace="el">
    <el-container class="admin-layout">
      <!-- 侧边栏 -->
      <el-aside width="220px" class="admin-sidebar">
        <div class="logo-area">
          <h2 class="logo-title">校园墙管理</h2>
        </div>
        <el-menu :default-active="activeMenu" @select="handleSelect" router class="sidebar-menu"
          background-color="#f8f9fa" text-color="#495057" active-text-color="#007bff">

          <el-menu-item index="/dashboard">
            <el-icon>
              <Odometer />
            </el-icon>
            <span>仪表盘</span>
          </el-menu-item>
          <el-menu-item index="/user-manage">
            <el-icon>
              <List />
            </el-icon>
            <span>墙列表</span>
          </el-menu-item>
          <el-menu-item index="/wall-master-apply">
            <el-icon>
              <Avatar />
            </el-icon>
            <span>墙主申请</span>
          </el-menu-item>
          <el-menu-item index="/withdraw-deposit">
            <el-icon>
              <Money />
            </el-icon>
            <span>墙主提现</span>
          </el-menu-item>

          <el-menu-item index="/post-manage">
            <el-icon>
              <Document />
            </el-icon>
            <span>投稿管理</span>
          </el-menu-item>

          <el-menu-item index="/comment-manage">
            <el-icon>
              <ChatSquare />
            </el-icon>
            <span>评论管理</span>
          </el-menu-item>

          <el-menu-item index="/notification-manager">
            <el-icon>
              <Bell />
            </el-icon>
            <span>通知管理</span>
          </el-menu-item>

          <el-menu-item index="/tutorial">
            <el-icon>
              <Document />
            </el-icon>
            <span>使用教程</span>
          </el-menu-item>

          <el-menu-item index="/user-agreement">
            <el-icon>
              <EditPen />
            </el-icon>
            <span>用户协议</span>
          </el-menu-item>

          <el-menu-item index="/community-manage">
            <el-icon>
              <House />
            </el-icon>
            <span>社区管理</span>
          </el-menu-item>

          <el-menu-item index="/channels-manage">
            <el-icon>
              <Menu />
            </el-icon>
            <span>渠道管理</span>
          </el-menu-item>
        </el-menu>
      </el-aside>

      <!-- 主体区域 -->
      <el-container>
        <!-- 顶部导航 -->
        <el-header class="admin-header">
          <div class="header-left">
            <el-breadcrumb separator="/">
              <el-breadcrumb-item :to="{ path: '/dashboard' }">首页</el-breadcrumb-item>
              <el-breadcrumb-item v-if="breadcrumbTitle">{{ breadcrumbTitle }}</el-breadcrumb-item>
            </el-breadcrumb>
          </div>

          <div class="header-right">
            <span class="welcome-text">欢迎，{{ authStore.user.nickname || '管理员' }}</span>
            <el-dropdown @command="handleCommand" class="user-dropdown">
              <span class="el-dropdown-link">
                <el-avatar :size="32" :src="authStore.user.avatar">
                  <el-icon>
                    <User />
                  </el-icon>
                </el-avatar>
                <el-icon class="el-icon--right">
                  <ArrowDown />
                </el-icon>
              </span>
              <template #dropdown>
                <el-dropdown-menu>
                  <el-dropdown-item command="password">
                    <el-icon>
                      <Lock />
                    </el-icon>修改密码
                  </el-dropdown-item>
                  <el-dropdown-item divided command="logout">
                    <el-icon>
                      <SwitchButton />
                    </el-icon>退出登录
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
          </div>
        </el-header>

        <!-- 主内容区 -->
        <el-main class="admin-main">
          <router-view />
        </el-main>
      </el-container>
    </el-container>
  </el-config-provider>
</template>

<script setup>
  import { ref, watch, computed } from 'vue';
  import { useRoute, useRouter } from 'vue-router';
  import { useAuthStore } from '../store/auth';
  import { ElMessageBox } from 'element-plus';

  const route = useRoute();
  const router = useRouter();
  const authStore = useAuthStore();

  const activeMenu = ref(route.path);

  // 面包屑标题映射
  const breadcrumbMap = {
    '/dashboard': '仪表盘',
    '/wall-master-apply': '墙主申请',
    '/post-manage': '投稿管理',
    '/comment-manage': '评论管理',
    '/notification-manager': '通知管理',
    '/tutorial': '使用教程'
  };

  const breadcrumbTitle = computed(() => breadcrumbMap[route.path]);

  watch(
    () => route.path,
    (val) => {
      activeMenu.value = val;
    }
  );

  function handleSelect(index) {
    router.push(index);
  }

  async function handleCommand(cmd) {
    switch (cmd) {
      case 'password':
        router.push('/change-password');
        break;
      case 'logout':
        try {
          await ElMessageBox.confirm('确定要退出登录吗？', '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
          });
          authStore.logout();
          router.push('/login');
        } catch (e) {
          // 用户取消
        }
        break;
    }
  }
</script>

<style scoped>
  .admin-layout {
    height: 100vh;
  }

  .admin-sidebar {
    box-shadow: 2px 0 6px rgba(0, 21, 41, 0.1);
    background-color: #f8f9fa;
  }

  .logo-area {
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 1px solid #e9ecef;
    background-color: #fff;
  }

  .logo-title {
    font-size: 16px;
    font-weight: 600;
    color: #495057;
    margin: 0;
  }

  .sidebar-menu {
    border-right: none;
  }

  .admin-header {
    background-color: #fff;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    box-shadow: 0 1px 4px rgba(0, 21, 41, 0.08);
  }

  .header-left {
    flex: 1;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 16px;
  }

  .welcome-text {
    color: #666;
    font-size: 14px;
  }

  .user-dropdown {
    cursor: pointer;
  }

  .el-dropdown-link {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #495057;
    text-decoration: none;
  }

  .admin-main {
    background-color: #f5f5f5;
    padding: 24px;
    overflow-y: auto;
  }
</style>
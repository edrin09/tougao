<template>
  <div class="dashboard">
    <!-- 统计卡片 -->
    <el-row :gutter="20" class="stats-row">
      <el-col :span="6">
        <el-card class="stat-card user-card">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon size="32">
                <User />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.userCount || 0 }}</div>
              <div class="stat-label">用户总数</div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="6">
        <el-card class="stat-card post-card">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon size="32">
                <Document />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.postCount || 0 }}</div>
              <div class="stat-label">投稿总数</div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="6">
        <el-card class="stat-card apply-card">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon size="32">
                <Clock />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.pendingApply || 0 }}</div>
              <div class="stat-label">待审墙主申请</div>
            </div>
          </div>
        </el-card>
      </el-col>

      <el-col :span="6">
        <el-card class="stat-card comment-card">
          <div class="stat-content">
            <div class="stat-icon">
              <el-icon size="32">
                <ChatSquare />
              </el-icon>
            </div>
            <div class="stat-info">
              <div class="stat-number">{{ stats.commentCount || 0 }}</div>
              <div class="stat-label">评论总数</div>
            </div>
          </div>
        </el-card>
      </el-col>
    </el-row>

    <!-- 快速操作 -->
    <el-row :gutter="20" class="quick-actions">
      <el-col :span="12">
        <el-card header="快速操作">
          <div class="action-grid">
            <el-button type="primary" size="large" @click="$router.push('/wall-master-apply')" class="action-btn">
              <el-icon>
                <Avatar />
              </el-icon>
              审核墙主申请
            </el-button>

            <el-button type="success" size="large" @click="$router.push('/post-manage')" class="action-btn">
              <el-icon>
                <Document />
              </el-icon>
              管理投稿
            </el-button>

            <el-button type="warning" size="large" @click="$router.push('/comment-manage')" class="action-btn">
              <el-icon>
                <ChatSquare />
              </el-icon>
              管理评论
            </el-button>

            <el-button type="info" size="large" @click="$router.push('/notification-manager')" class="action-btn">
              <el-icon>
                <Bell />
              </el-icon>
              发布通知
            </el-button>
          </div>
        </el-card>
      </el-col>

      <el-col :span="12">
        <el-card header="系统信息">
          <el-descriptions :column="1" border>
            <el-descriptions-item label="当前版本">v2.0.0</el-descriptions-item>
            <el-descriptions-item label="运行时间">{{ uptime }}</el-descriptions-item>
            <el-descriptions-item label="最后登录">{{ lastLoginTime }}</el-descriptions-item>
            <el-descriptions-item label="系统状态">
              <el-tag type="success">正常运行</el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </el-card>
      </el-col>
    </el-row>

    <!-- 最近活动 -->
    <el-row :gutter="20">
      <el-col :span="24">
        <el-card header="最近活动" v-loading="loading">
          <el-timeline v-if="activities.length">
            <el-timeline-item v-for="activity in activities" :key="activity.id" :timestamp="activity.time"
              type="primary">
              {{ activity.content }}
            </el-timeline-item>
          </el-timeline>
          <el-empty v-else description="暂无活动记录" />
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
  import { onMounted, reactive, ref, computed } from 'vue';
  import { ElMessage } from 'element-plus';
  import { API } from '../api';
  import { useAuthStore } from '../store/auth';
  import { systemStore } from '../store/system';

  const authStore = useAuthStore();
  const loading = ref(false);
  const system = systemStore();

  const stats = reactive({
    userCount: 0,
    postCount: 0,
    pendingApply: 0,
    commentCount: 0
  });

  const activities = ref([]);

  // 计算运行时间（示例）
  const uptime = computed(() => {
    const days = Math.floor(Math.random() * 30) + 1;
    return `${days} 天`;
  });

  // 最后登录时间
  const lastLoginTime = computed(() => {
    return new Date().toLocaleString();
  });

  async function loadDashboardData() {
    try {
      loading.value = true;
      const res = await API.dashboard.getStats();

      if (res.code === 200 || res.code === 1) {
        const d = res.data || {};
        // 兼容不同字段命名
        stats.userCount = d.userCount ?? d.user_total ?? d.totalUser ?? 0;
        stats.postCount = d.postCount ?? d.post_total ?? d.totalPost ?? 0;
        stats.pendingApply = d.pendingApply ?? d.pending_apply ?? d.pendingApplyCount ?? 0;
        stats.commentCount = d.commentCount ?? d.comment_total ?? d.totalComment ?? 0;
      }

      // 模拟最近活动数据（实际项目中从API获取）
      activities.value = [
        { id: 1, content: '用户张三申请成为墙主', time: '2024-01-15 14:30' },
        { id: 2, content: '新投稿「校园生活分享」已发布', time: '2024-01-15 13:20' },
        { id: 3, content: '评论审核通过 5 条', time: '2024-01-15 12:10' },
        { id: 4, content: '系统通知已发布', time: '2024-01-15 11:00' },
      ];
    } catch (error) {
      console.error('加载仪表盘数据失败:', error);
      ElMessage.error('加载数据失败');
    } finally {
      loading.value = false;
    }
  }
  async function getSysConfig() {
    const res = await API.admin.config();
    if (res.code === 200) {
      system.setConfig(res.data.config);
    }
  }
  onMounted(() => {
    loadDashboardData();
    getSysConfig();
  });
</script>

<style scoped>
  .dashboard {
    padding: 0;
  }

  .stats-row {
    margin-bottom: 20px;
  }

  .stat-card {
    cursor: pointer;
    transition: all 0.3s ease;
    border: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  }

  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  }

  .stat-content {
    display: flex;
    align-items: center;
    padding: 10px 0;
  }

  .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
  }

  .user-card .stat-icon {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
  }

  .post-card .stat-icon {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
  }

  .apply-card .stat-icon {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
  }

  .comment-card .stat-icon {
    background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
    color: white;
  }

  .stat-info {
    flex: 1;
  }

  .stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #303133;
    line-height: 1;
    margin-bottom: 4px;
  }

  .stat-label {
    font-size: 14px;
    color: #909399;
  }

  .quick-actions {
    margin-bottom: 20px;
  }

  .action-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
  }

  .action-btn {
    height: 60px;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    border-radius: 8px;
  }

  :deep(.el-card__header) {
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    font-weight: 600;
    color: #495057;
  }

  :deep(.el-card__body) {
    padding: 20px;
  }

  :deep(.el-timeline) {
    padding-left: 20px;
  }
</style>
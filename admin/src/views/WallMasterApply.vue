<template>
  <div class="wall-master-apply">
    <!-- 顶部筛选 -->
    <el-card class="filter-card" shadow="never">
      <template #header>
        <div class="flex items-center">
          <el-icon class="mr-2">
            <Filter />
          </el-icon>
          <span class="font-semibold">筛选条件</span>
        </div>
      </template>

      <div class="flex items-center flex-wrap gap-4">
        <el-radio-group v-model="searchForm.status" @change="handleStatusChange">
          <el-radio-button label="all">全部</el-radio-button>
          <el-radio-button label="0">待审核</el-radio-button>
          <el-radio-button label="1">已通过</el-radio-button>
          <el-radio-button label="-1">已驳回</el-radio-button>
        </el-radio-group>
        <div class="flex-1" />
        <span class="text-sm text-gray-600">共 {{ total }} 条记录</span>
      </div>
    </el-card>

    <!-- 列表 -->
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="flex items-center justify-between">
          <div class="flex items-center font-semibold">
            <el-icon class="mr-2">
              <User />
            </el-icon>
            申请列表
          </div>
          <el-button @click="loadData" :loading="loading">
            <el-icon>
              <Refresh />
            </el-icon>刷新
          </el-button>
        </div>
      </template>

      <el-table :data="applyList" v-loading="loading" stripe class="w-full">
        <el-table-column type="index" label="#" width="60" />

        <el-table-column label="申请人" width="200">
          <template #default="{ row }">
            <div class="flex items-center space-x-3">
              <el-avatar :src="row.avatar" :size="40" @click="previewImage(row.avatar)">
                <img src="https://via.placeholder.com/40x40/409EFF/FFFFFF?text=?" />
              </el-avatar>
              <span>{{ row.nickname || '未知用户' }}</span>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="申请时间" width="180">
          <template #default="{ row }">
            <el-icon class="mr-1 text-gray-500">
              <Clock />
            </el-icon>{{ formatDate(row.applyTime) }}
          </template>
        </el-table-column>

        <el-table-column label="VIP状态" width="120">
          <template #default="{ row }">
            <el-tag v-if="row.isVip === 1" type="warning"><el-icon class="mr-1">
                <Star />
              </el-icon>VIP</el-tag>
            <el-tag v-else type="info"><el-icon class="mr-1">
                <User />
              </el-icon>普通</el-tag>
          </template>
        </el-table-column>

        <!--        <el-table-column label="默认墙主" width="120">-->
        <!--          <template #default="{ row }">-->
        <!--            <el-tag v-if="row.isDefaultWallMaster === 1 && row.status === 1" type="primary">-->
        <!--              <el-icon class="mr-1">-->
        <!--                <Management />-->
        <!--              </el-icon>默认墙主-->
        <!--            </el-tag>-->
        <!--            <el-tag v-else-if="row.status === 1" type="info">-->
        <!--              <el-icon class="mr-1">-->
        <!--                <User />-->
        <!--              </el-icon>普通墙主-->
        <!--            </el-tag>-->
        <!--            <span v-else>-</span>-->
        <!--          </template>-->
        <!--        </el-table-column>-->

        <el-table-column label="审核状态" width="120">
          <template #default="{ row }">
            <el-tag :type="formatStatus(row.status).type">
              {{ formatStatus(row.status).text }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="280" fixed="right">
          <template #default="{ row }">
            <div class="flex space-x-2">
              <el-button size="small" type="primary" @click="viewDetail(row)">查看详情</el-button>
              <el-button v-if="row.status == 0" size="small" type="success"
                @click="openReview(row, 'approve')">通过</el-button>
              <el-button v-if="row.status == 0" size="small" type="danger"
                @click="openReview(row, 'reject')">驳回</el-button>
              <el-button size="small" type="danger" @click="deleteApply(row)">删除</el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="flex justify-end mt-4">
        <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :page-sizes="[10, 20, 50, 100]"
          :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="handleSizeChange"
          @current-change="handleCurrentChange" />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="申请详情" width="800px">
      <div v-if="currentApply" class="space-y-4">
        <!-- 用户信息 -->
        <el-card>
          <div class="flex flex-col items-center p-4">
            <el-avatar :src="currentApply.avatar" :size="80" class="mb-3" @click="previewImage(currentApply.avatar)">
              <img src="https://via.placeholder.com/80x80/409EFF/FFFFFF?text=?" />
            </el-avatar>
            <div class="text-lg font-semibold">{{ currentApply.nickname || '未知用户' }}</div>
            <el-tag v-if="currentApply.isVip === 1" type="warning" class="mt-2">
              <el-icon class="mr-1">
                <Star />
              </el-icon>VIP用户
            </el-tag>
            <el-tag v-else type="info" class="mt-2">
              <el-icon class="mr-1">
                <User />
              </el-icon>普通用户
            </el-tag>
          </div>
          <el-descriptions :column="2" border>
            <el-descriptions-item label="申请ID">{{ currentApply.applyId }}</el-descriptions-item>
            <el-descriptions-item label="用户ID">{{ currentApply.userId }}</el-descriptions-item>
            <el-descriptions-item label="OpenID" :span="2">{{ currentApply.openid || '未提供' }}</el-descriptions-item>
            <el-descriptions-item label="申请时间">{{ formatDate(currentApply.applyTime) }}</el-descriptions-item>
            <el-descriptions-item label="审核状态" :span="1">
              <el-tag :type="formatStatus(currentApply.status).type">{{ formatStatus(currentApply.status).text
              }}</el-tag>
            </el-descriptions-item>
          </el-descriptions>
        </el-card>
        <el-card>
          <el-table v-if="currentApply.status == 1" :data="contributeData" style="width: 100%">
            <el-table-column prop="date" label="投稿渠道" width="180" />
            <el-table-column prop="name" label="账号" width="180" />
            <el-table-column prop="address" label="粉丝数" />
            <el-table-column prop="address" label="渠道费用" />
          </el-table>
        </el-card>
        <!-- VIP状态 -->
        <el-card>
          <template #header>
            <el-icon class="mr-2">
              <Star />
            </el-icon>VIP状态设置
          </template>
          <el-switch v-model="vipStatus" active-text="VIP用户" inactive-text="普通用户" @change="handleVipToggle" />
          <div class="text-xs text-gray-500 mt-2"><el-icon class="mr-1">
              <Info />
            </el-icon>VIP用户将拥有更多的功能权限</div>
        </el-card>
        <el-card v-if="currentApply.status == 1">
          <template #header>
            <el-icon class="mr-2">
              <Management />
            </el-icon>分成比例设置
          </template>
          <el-input-number v-model="currentApply.shareRatio" @change="handleRatioChange" :step="1" :min="0" :max="100"
            label="分成比例">
            <template #suffix>
              <span>%</span>
            </template>
          </el-input-number>
        </el-card>
        <!-- 社区绑定 -->
        <el-card>
          <template #header>
            <el-icon class="mr-2">
              <Star />
            </el-icon>社区绑定
          </template>
          <el-select v-model="currentApply.community_id" filterable remote reserve-keyword placeholder="请选择社区"
            :remote-method="loadCommunityList" :loading="loading" @change="handleCommunityChange" style="width: 240px">
            <el-option v-for="item in communityList" :key="item.community_id" :label="item.community_name"
              :value="item.community_id" />
          </el-select>
        </el-card>
        <div v-if="currentApply.reviewComment" class="p-2 bg-gray-50 rounded">
          <el-icon class="mr-1">
            <Document />
          </el-icon>审核意见：
          <div class="mt-1">{{ currentApply.reviewComment }}</div>
        </div>
      </div>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="() => { detailVisible = false; loadData() }">关闭</el-button>
          <el-button v-if="currentApply && currentApply.status == 0" type="danger"
            @click="openReview(currentApply, 'reject')">驳回申请</el-button>
          <el-button v-if="currentApply && currentApply.status == 0" type="success"
            @click="openReview(currentApply, 'approve')">通过申请</el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 审核意见对话框 -->
    <el-dialog v-model="reviewDialogVisible" :title="reviewAction === 'approve' ? '通过申请' : '驳回申请'" width="400px">
      <el-form :model="reviewForm" label-width="80px">
        <el-form-item label="审核意见">
          <el-input v-model="reviewForm.comment" type="textarea" :rows="4"
            :placeholder="reviewAction === 'approve' ? '请输入通过理由（可选）' : '请输入驳回理由'" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="reviewDialogVisible = false">取消</el-button>
          <el-button :type="reviewAction === 'approve' ? 'success' : 'danger'" :loading="reviewLoading"
            @click="submitReview">{{ reviewAction === 'approve' ? '确认通过' : '确认驳回' }}</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
  import { ref, reactive, onMounted } from 'vue';
  import { ElMessage, ElMessageBox } from 'element-plus';
  import { API } from '../api';

  // 响应式数据
  const loading = ref(false);
  const applyList = ref([]);
  const total = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(20);

  const searchForm = reactive({ status: 'all' });

  // 详情相关
  const detailVisible = ref(false);
  const currentApply = ref(null);
  const vipStatus = ref(false);
  const defaultWallMaster = ref(false);

  // 审核相关
  const reviewDialogVisible = ref(false);
  const reviewAction = ref(''); // approve / reject
  const reviewForm = reactive({ comment: '' });
  const reviewLoading = ref(false);

  const contributeData = ref([]);

  // 社区列表
  const communityList = ref([]);
  const currentCommunityId = ref(null);

  // 工具函数
  function formatDate(time) {
    if (!time) return '-';
    return new Date(time).toLocaleString();
  }

  function formatStatus(status) {
    const map = {
      0: { text: '待审核', type: 'warning' },
      1: { text: '已通过', type: 'success' },
      '-1': { text: '已驳回', type: 'danger' }
    };
    return map[status] || { text: '未知', type: 'info' };
  }

  function previewImage(url) {
    if (!url) return;
    ElMessage.info('图片预览功能');
  }

  function handleApiError(error, msg = '操作失败') {
    console.error(error);
    ElMessage.error(error?.message || error?.data?.message || msg);
  }
  function handleApiSuccess(msg) {
    ElMessage.success(msg);
  }

  // 加载列表
  async function loadData() {
    loading.value = true;
    try {
      const params = {
        page: currentPage.value,
        size: pageSize.value
      };
      if (searchForm.status !== 'all') params.status = searchForm.status;

      const res = await API.wallMasterApply.getList(params);
      let list = [], totalCount = 0;
      if (res.code === 200) {
        list = res.data?.list || [];
        totalCount = res.data?.total || 0;
      } else if (res.data?.code === 200) {
        list = res.data.data?.list || [];
        totalCount = res.data.data?.total || 0;
      }
      applyList.value = list;
      total.value = totalCount;
    } catch (e) {
      handleApiError(e, '加载申请列表失败');
    } finally {
      loading.value = false;
    }
  }

  function handleStatusChange() {
    currentPage.value = 1;
    loadData();
  }
  function handleSizeChange(size) {
    pageSize.value = size;
    currentPage.value = 1;
    loadData();
  }
  function handleCurrentChange(page) {
    currentPage.value = page;
    loadData();
  }

  // 查看详情
  async function viewDetail(row) {
    try {
      const res = await API.wallMasterApply.getDetail(row.applyId || row.id);
      if (res.code === 200) {
        currentApply.value = res.data?.applyDetail || res.data?.data?.applyDetail || res.data;
        vipStatus.value = currentApply.value.isVip === 1;
        defaultWallMaster.value = currentApply.value.isDefaultWallMaster === 1;
        detailVisible.value = true;
      }
    } catch (e) {
      handleApiError(e, '获取详情失败');
    }
  }

  // 审核
  function openReview(row, action) {
    currentApply.value = row;
    reviewAction.value = action === 'approve' ? 'approve' : 'reject';
    reviewForm.comment = '';
    reviewDialogVisible.value = true;
  }

  async function submitReview() {
    if (!currentApply.value) return;
    reviewLoading.value = true;
    try {
      const data = {
        applyId: currentApply.value.applyId,
        status: reviewAction.value === 'approve' ? 1 : -1,
        comment: reviewForm.comment
      };
      const res = await API.wallMasterApply.review(data);
      const success = res.code === 200 || res.data?.code === 200;
      if (success) {
        handleApiSuccess(reviewAction.value === 'approve' ? '申请已通过' : '申请已驳回');
        reviewDialogVisible.value = false;
        detailVisible.value = false;
        loadData();
      } else {
        handleApiError(res, '审核失败');
      }
    } catch (e) {
      handleApiError(e, '审核失败');
    } finally {
      reviewLoading.value = false;
    }
  }

  // VIP toggle
  async function handleVipToggle(val) {
    if (!currentApply.value) return;
    try {
      const res = await API.wallMasterApply.updateVipStatus({ applyId: currentApply.value.applyId, isVip: val ? 1 : 0 });
      if (res.code === 200 || res.data?.code === 200) {
        handleApiSuccess('VIP状态已更新');
        currentApply.value.isVip = val ? 1 : 0;
        loadData();
      } else {
        vipStatus.value = !val;
        handleApiError(res);
      }
    } catch (e) {
      vipStatus.value = !val;
      handleApiError(e, '更新VIP状态失败');
    }
  }
  // Default wall master toggle
  // async function handleDefaultToggle(val) {
  //   if (!currentApply.value) return;
  //   try {
  //     const res = await API.wallMasterApply.setDefaultWallMaster({ apply_id: currentApply.value.applyId, user_id: currentApply.value.userId });
  //     if (res.code === 200 || res.data?.code === 200) {
  //       handleApiSuccess('默认墙主状态已更新');
  //       currentApply.value.isDefaultWallMaster = val ? 1 : 0;
  //       loadData();
  //     } else {
  //       defaultWallMaster.value = !val;
  //       handleApiError(res);
  //     }
  //   } catch (e) {
  //     defaultWallMaster.value = !val;
  //     handleApiError(e, '更新默认墙主失败');
  //   }
  // }

  // 删除
  function deleteApply(row) {
    ElMessageBox.confirm('确定要删除此墙主申请吗？此操作不可恢复。', '删除确认', {
      confirmButtonText: '确定删除',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(async () => {
      try {
        const res = await API.wallMasterApply.delete(row.applyId);
        const success = res.code === 200 || res.data?.code === 200;
        if (success) {
          handleApiSuccess('删除成功');
          loadData();
        } else {
          handleApiError(res);
        }
      } catch (e) {
        handleApiError(e, '删除失败');
      }
    }).catch(() => { });
  }

  async function handleRatioChange(share_ratio) {
    const { code, data, message } = await API.wallMasterApply.setShareRatio({ apply_id: currentApply.value.applyId, share_ratio })
    if (code === 200 || data?.code === 200) {
      ElMessage.success(message || '设置成功');
    } else {
      ElMessage.error(message || '设置失败');
    }
  }

  async function handleCommunityChange(params) {
    const { code, data, message } = await API.wallMasterApply.saveWallInfo({ user_id: currentApply.value.userId, community_id: params })
    if (code === 200 || data?.code === 200) {
      ElMessage.success(message || '社区绑定成功');
    } else {
      ElMessage.error(message || '社区绑定失败');
    }
  }

  async function loadCommunityList(params) {
    const { code, data, message } = await API.user.getCommunityList({ keyword: params })
    if (code === 200 || data?.code === 200) {
      communityList.value = data.list
    } else {
      ElMessage.error(message || '社区列表获取失败');
    }
  }

  onMounted(loadData);
</script>

<style scoped>
  .wall-master-apply {
    padding: 0;
  }

  .filter-card {
    margin-bottom: 20px;
    padding: 12px 20px;
  }

  .table-card {
    margin-top: 0;
  }

  .space-x-2>*+* {
    margin-left: 0.5rem;
  }

  .space-x-3>*+* {
    margin-left: 0.75rem;
  }

  .text-gray-600 {
    color: #4b5563
  }

  .text-gray-500 {
    color: #6b7280
  }

  .text-xs {
    font-size: 0.75rem
  }

  .filter-card :deep(.el-card__body) {
    padding: 20px 20px 0 20px;
  }

  .table-card :deep(.el-card__header) {
    padding: 16px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
</style>
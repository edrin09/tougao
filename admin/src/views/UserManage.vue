<template>
  <div class="user-manage">
    <!-- 搜索区域 -->
    <el-card class="search-card" shadow="never">
      <template #header>
        <div class="flex items-center">
          <el-icon class="mr-2">
            <Filter />
          </el-icon>
          <span class="font-semibold">筛选条件</span>
        </div>
      </template>

      <el-form :model="searchForm" inline>
        <el-form-item>
          <el-input v-model="searchForm.keyword" placeholder="搜索用户名或ID..." style="width: 300px" clearable
            @keyup.enter="handleSearch">
            <template #append>
              <el-button @click="handleSearch">
                <el-icon>
                  <Search />
                </el-icon>
              </el-button>
            </template>
          </el-input>
        </el-form-item>
        <el-form-item>
          <el-select style="width:100px" v-model="searchForm.is_vip" placeholder="VIP状态" clearable
            @change="handleSearch">
            <el-option label="全部" value="" />
            <el-option label="是VIP" value="1" />
            <el-option label="不是VIP" value="0" />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-select style="width:100px" v-model="searchForm.status" placeholder="墙状态" clearable @change="handleSearch">
            <el-option label="全部" value="" />
            <el-option label="可用" value="1" />
            <el-option label="禁用" value="0" />
          </el-select>
        </el-form-item>

        <el-form-item>
          <div class="text-sm text-gray-600">
            共 {{ pagination.total }} 条记录
          </div>
        </el-form-item>
      </el-form>
    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="card-header">
          <div class="flex items-center">
            <el-icon class="mr-2">
              <List />
            </el-icon>
            <span class="font-semibold">墙列表</span>
          </div>
          <!-- <div class="header-actions">
            <el-button type="primary" size="small" @click="exportData" :loading="exportLoading">
              <el-icon>
                <Download />
              </el-icon>导出Excel
            </el-button>
          </div> -->
        </div>
      </template>

      <el-table :data="tableData" :lazy="true" @expand-change="handleTableExpand" v-loading="loading" stripe
        style="width: 100%">

        <el-table-column type="expand" width="60">
          <template #default="props">
            <div style="padding-left: 50px;">
              <el-table :data="props.row.user_list" :border="childBorder" v-loading="loadingChildren">
                <el-table-column label="头像" prop="name">
                  <template #default="{ row }">
                    <el-image :src="row.avatar" fit="cover"
                      style="width: 40px; height: 40px; cursor: pointer; border-radius: 4px;"
                      @click="previewImage(row.avatar)" :preview-disabled="true" />
                  </template>
                </el-table-column>
                <el-table-column label="是否墙主" prop="is_wall_master" width="100">
                  <template #default="{ row }">
                    {{ row.is_wall_master ? '是' : '否' }}
                  </template>
                </el-table-column>
                <el-table-column label="昵称" prop="nickname" />
                <el-table-column label="电话" prop="phone" />
                <el-table-column label="邮箱" prop="email" />
                <el-table-column label="学校" prop="school" />
                <el-table-column label="学院" prop="college" />
              </el-table>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="ID" prop="wall_id" width="160" />
        <el-table-column label="logo" width="120">
          <template #default="{ row }">
            <div class="flex items-center space-x-3">
              <el-avatar :src="row.logo_url" :size="40" class="cursor-pointer" @click="previewImage(row.logo_url)">
                <img src="https://via.placeholder.com/40x40/409EFF/FFFFFF?text=?" />
              </el-avatar>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="注册时间" prop="create_time" width="100" />
        <el-table-column label="所属社区" prop="community_name" width="100" />
        <el-table-column label="墙名称" prop="wall_name" width="100" />
        <el-table-column label="墙表述" show-overflow-tooltip prop="description" width="200" />
        <el-table-column label="是否VIP" prop="status" width="status">
          <template #default="{ row }">
            {{ row.is_vip ? '是' : '否' }}
          </template>
        </el-table-column>
        <el-table-column label="墙状态" prop="status" width="status">
          <template #default="{ row }">
            <el-tag :type="getWallStatusType(row.status)">
              {{ getWallStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="用户信息" width="200">
          <template #default="{ row }">
            <div class="flex items-center space-x-3">
              <el-avatar :src="row.avatar" :size="40" class="cursor-pointer" @click="previewImage(row.avatar)">
                <img src="https://via.placeholder.com/40x40/409EFF/FFFFFF?text=?" />
              </el-avatar>
              <div>
                <div class="font-medium">{{ row.nickname || '未知用户' }}</div>
              </div>
            </div>
          </template>
        </el-table-column>
        <!-- 
        <el-table-column label="注册时间" width="180">
          <template #default="{ row }">
            {{ formatTime(row.createTime) }}
          </template>
        </el-table-column> -->

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <div class="flex space-x-2">
              <el-button size="small" type="primary" @click="viewHistory(row)">提现历史</el-button>
              <!-- <el-button size="small" type="danger" @click="deleteUser(row)">删除</el-button> -->
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.currentPage" v-model:page-size="pagination.pageSize"
          :page-sizes="[10, 20, 50, 100]" :total="pagination.total" layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange" @current-change="handleCurrentChange" />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="详情" width="600px" destroy-on-close>

      <div v-if="currentRow" class="user-detail">
        <div class="user-avatar-section">
          <el-avatar :src="currentRow.logo_url" :size="80" class="detail-avatar"
            @click="previewImage(currentRow.logo_url)">
            <img src="https://via.placeholder.com/80x80/409EFF/FFFFFF?text=?" />
          </el-avatar>
          <div class="user-name">{{ currentRow.wall_name || '' }}</div>
        </div>

        <el-descriptions :column="2" border>
          <el-descriptions-item label="墙表述" :span="2">
            <span class="break-all">{{ currentRow.description }}</span>
          </el-descriptions-item>
          <el-descriptions-item label="注册时间">{{ formatTime(currentRow.create_time) }}</el-descriptions-item>
          <el-descriptions-item label="VIP状态">
            <el-tag v-if="currentRow.isVip === 1" type="warning">
              <el-icon class="mr-1">
                <Star />
              </el-icon>是VIP
            </el-tag>
            <el-tag v-else type="info">
              <el-icon class="mr-1">
                <User />
              </el-icon>不是VIP
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>
      </div>

      <!--      <template #footer>-->
      <!--        <div class="dialog-footer">-->
      <!--          <el-button @click="detailVisible = false">关闭</el-button>-->
      <!--          <el-button type="danger" @click="deleteUser(currentRow)">删除用户</el-button>-->
      <!--        </div>-->
      <!--      </template>-->
    </el-dialog>

    <!-- 提现历史 -->
    <el-dialog v-model="withdraw" title="提现历史" width="800px" destroy-on-close>

      <div v-if="currentRow" class="detail-content">
        <div class="reason-section">
          <el-tabs v-model="activeName" class="demo-tabs" @tab-change="handleTabClick">
            <el-tab-pane label="线上提现到微信零钱" name="wechat"></el-tab-pane>
            <el-tab-pane label="线下提现转账" name="offline"></el-tab-pane>
          </el-tabs>
          <el-table :data="historyTableData" v-loading="loading" stripe style="width: 100%">
            <el-table-column type="index" label="#" width="60" />
            <el-table-column label="账号" prop="bank_card" width="150" />
            <el-table-column label="开户行" prop="bank_name" width="120" />
            <el-table-column label="申请人" prop="payee_name" width="100" />
            <el-table-column label="提现金额" prop="cash_amount" width="90">
            </el-table-column>
            <el-table-column label="提现状态" width="120">
              <template #default="{ row }">
                <el-tag :type="getWithdrawStatusType(row.withdraw_status)">
                  {{ getWithdrawStatusText(row.withdraw_status) }}
                </el-tag>
              </template>
            </el-table-column>
            <el-table-column label="提现时间" width="120">
              <template #default="{ row }">
                <div class="apply-time">
                  <el-icon class="time-icon">
                    <Clock />
                  </el-icon>
                  {{ formatTime(row.create_time) }}
                </div>
              </template>
            </el-table-column>
            <el-table-column label="打款时间" width="120">
              <template #default="{ row }">
                <div class="apply-time">
                  <el-icon class="time-icon">
                    <Clock />
                  </el-icon>
                  {{ formatTime(row.cash_time) }}
                </div>
              </template>
            </el-table-column>
            <template v-if="activeName === 'offline'">
              <el-table-column label="打款凭证" width="100">
                <template #default="{ row }">
                  <div v-if="row.withdrawal_evidence" class="voucher-preview">
                    <el-image :src="row.withdrawal_evidence" fit="cover"
                      style="width: 60px; height: 60px; cursor: pointer; border-radius: 4px;" alt="打款凭证"
                      @click="previewVoucherImage(row.withdrawal_evidence)" :preview-disabled="true" />
                    <div class="preview-icon" @click="previewVoucherImage(row.withdrawal_evidence)">
                      <el-icon>
                        <ZoomIn />
                      </el-icon>
                    </div>
                  </div>
                  <span v-else class="text-muted">未上传</span>
                </template>
              </el-table-column>
            </template>
          </el-table>
        </div>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button @click="withdraw = false">关闭</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 图片预览器 -->
    <el-image-viewer v-if="imageViewerVisible" :url-list="imageList" :initial-index="imageIndex"
      @close="closeImageViewer" :hide-on-click-modal="true" :teleported="true" />
  </div>
</template>

<script setup>
  import { ref, reactive, onMounted, watchEffect } from 'vue';
  import { ElMessage, ElMessageBox, ElImageViewer } from 'element-plus';
  // import { Filter, Search, User, Clock, Management, Star, Crown, ZoomIn } from '@element-plus/icons-vue';
  import { API } from '../api';

  const loading = ref(false);
  const loadingChildren = ref(false);
  const exportLoading = ref(false);
  const detailVisible = ref(false);
  const withdraw = ref(false);
  const currentRow = ref(null);
  const activeName = ref('wechat');

  // 图片预览相关
  const imageViewerVisible = ref(false);
  const imageList = ref([]);
  const imageIndex = ref(0);

  const searchForm = reactive({
    keyword: '',
    status: '',
    is_vip: ''
  });

  const pagination = reactive({
    currentPage: 1,
    pageSize: 20,
    total: 0
  });

  // 状态映射 - 按原组件的数字状态
  const statusMap = {
    0: { text: '待审核', type: 'warning' },
    1: { text: '已通过', type: 'success' },
    '-1': { text: '已驳回', type: 'danger' },
  };
  const wallStatusMap = {
    0: { text: '禁用', type: 'danger' },
    1: { text: '可用', type: 'success' },
  };

  const withdrawStatusMap = {
    3: { text: '拒绝', type: 'danger' },
    5: { text: '完成', type: 'success' },
  }

  const tableData = ref([]);
  const historyTableData = ref([]);
  function getWithdrawStatusText(status) {
    return withdrawStatusMap[status]?.text || '未知';
  }

  function getWithdrawStatusType(status) {
    return withdrawStatusMap[status]?.type || 'info';
  }

  function getWallStatusText(status) {
    return wallStatusMap[status]?.text || '未知';
  }

  function getWallStatusType(status) {
    return wallStatusMap[status]?.type || 'info';
  }
  function getStatusType(status) {
    return statusMap[status]?.type || 'info';
  }
  function formatTime(time) {
    if (!time) return '-';
    return new Date(time).toLocaleString();
  }

  function truncateText(text, length) {
    if (!text) return '';
    return text.length > length ? text.substring(0, length) + '...' : text;
  }

  // 图片预览功能
  function previewImage(url) {
    if (!url) {
      ElMessage.warning('暂无图片可预览');
      return;
    }
    imageList.value = [url];
    imageIndex.value = 0;
    imageViewerVisible.value = true;
  }

  // 打款凭证预览功能
  function previewVoucherImage(url) {
    if (!url) {
      ElMessage.warning('暂无凭证图片可预览');
      return;
    }
    imageList.value = [url];
    imageIndex.value = 0;
    imageViewerVisible.value = true;
  }

  // 关闭图片预览器
  function closeImageViewer() {
    imageViewerVisible.value = false;
    imageList.value = [];
    imageIndex.value = 0;
  }

  function handleSearch() {
    pagination.currentPage = 1;
    loadData();
  }

  function handleSizeChange(size) {
    pagination.pageSize = size;
    pagination.currentPage = 1;
    loadData();
  }

  function handleCurrentChange(page) {
    pagination.currentPage = page;
    loadData();
  }

  watchEffect(() => {
    pagination.page = pagination.currentPage;
    pagination.size = pagination.pageSize;
  });

  function handleTableExpand(expandRow, expanded, c) {
    loadingChildren.value = true;
    setTimeout(() => {
      loadingChildren.value = false;
    }, 1000);
  }

  async function loadData() {
    try {
      loading.value = true;
      const params = {
        page: pagination.currentPage,
        size: pagination.pageSize,
        ...searchForm
      };

      const res = await API.level.getList(params);

      let list = [], totalCount = 0;

      if (res.code === 200) {
        list = res.data?.list || [];
        totalCount = res.data?.total || 0;
      } else if (res.data?.code === 200) {
        list = res.data.data?.list || [];
        totalCount = res.data.data?.total || 0;
      }

      // 字段兼容：将下划线命名转换为驼峰
      list = list.map(item => ({
        ...item,
        isWallMaster: item.isWallMaster ?? item.status ?? 0,
        isVip: item.isVip ?? item.is_vip ?? 0,
        isDefaultWallMaster: item.isDefaultWallMaster ?? item.is_default_wall_master ?? 0,
        createTime: item.createTime ?? item.create_time,
        lastLoginTime: item.lastLoginTime ?? item.last_login_time,
        userId: item.userId ?? item.user_id
      }));

      tableData.value = list;
      pagination.total = totalCount;
    } catch (error) {
      console.error('加载用户列表失败:', error);
      ElMessage.error('加载数据失败，请检查网络连接');
    } finally {
      loading.value = false;
    }
  }

  function viewDetail(row) {
    currentRow.value = row;
    detailVisible.value = true;
  }

  async function deleteUser(user) {
    try {
      await ElMessageBox.confirm('确定要删除这个用户吗？此操作不可恢复。', '确认删除', {
        confirmButtonText: '删除',
        cancelButtonText: '取消',
        type: 'warning'
      });

      const res = await API.userManager.delete(user.userId);

      const success = (res.code === 200) || (res.data?.code === 200);

      if (success) {
        ElMessage.success('删除成功');
        detailVisible.value = false;
        loadData();
      } else {
        const msg = res.message || res.data?.message || '删除失败';
        ElMessage.error(msg);
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除用户失败:', error);
        ElMessage.error('删除失败');
      }
    }
  }

  // 导出数据到Excel
  async function exportData() {
    try {
      exportLoading.value = true;

      // 获取所有数据用于导出
      const params = {
        page: 1,
        limit: pagination.total || 10000, // 导出所有数据
        ...searchForm
      };

      // 如果是'all'，不传 status参数
      if (params.status === 'all') {
        delete params.status;
      }

      const res = await API.user.getList(params);

      if (res.code === 200) {
        const data = res.data || {};
        const exportData = data.list || [];

        if (exportData.length === 0) {
          ElMessage.warning('没有数据可以导出');
          return;
        }

        // 处理导出数据格式
        const formattedData = exportData.map((item, index) => ({
          '序号': index + 1,
          '昵称': item.post_id,
          '用户微信名称': item.title || '',
          '用户手机号': item.content || '',
          '用户邮箱': item.tag || '无',
          // '墙名称': item.wall_name || '未知墙面',
          // '墙主ID': item.wall_owner_id || '',
          // '海报': item.post_image || '无',
          // '状态': getStatusText(item.status),
          // '提交时间': item.create_time || ''
        }));

        // 创建工作簿和工作表
        const worksheet = XLSX.utils.json_to_sheet(formattedData);
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, '投稿列表');

        // 设置列宽
        const colWidths = [
          { wch: 8 },  // 序号
          { wch: 12 }, // 投稿ID
          { wch: 20 }, // 标题
          { wch: 30 }, // 内容
          { wch: 12 }, // 标签
          // { wch: 15 }, // 墙名称
          // { wch: 12 }, // 墙主ID
          // { wch: 25 }, // 海报
          // { wch: 10 }, // 状态
          // { wch: 20 }  // 提交时间
        ];
        worksheet['!cols'] = colWidths;

        // 生成文件名
        const now = new Date();
        const fileName = `用户列表_${now.getFullYear()}${(now.getMonth() + 1).toString().padStart(2, '0')}${now.getDate().toString().padStart(2, '0')}_${now.getHours().toString().padStart(2, '0')}${now.getMinutes().toString().padStart(2, '0')}.xlsx`;

        // 下载文件
        XLSX.writeFile(workbook, fileName);

        ElMessage.success(`成功导出 ${exportData.length} 条数据`);
      } else {
        ElMessage.error(res.message || '导出失败');
      }
    } catch (error) {
      console.error('导出失败:', error);
      ElMessage.error('导出失败，请重试');
    } finally {
      exportLoading.value = false;
    }
  }

  function getStatusText(status) {
    return statusMap[status]?.text || '未知';
  }

  function viewHistory(row) {
    currentRow.value = row;
    withdraw.value = true;
    getWithDrawHistory({
      user_id: row.wall_owner_id,
      withdrawal_method: activeName.value
    })
  }

  function handleTabClick() {
    getWithDrawHistory({
      user_id: currentRow.value.wall_owner_id,
      withdrawal_method: activeName.value
    });
  }

  async function getWithDrawHistory(params) {
    const res = await API.Withdraw.historyList(params);
    if (res.code === 200) {
      const data = res.data || {};
      historyTableData.value = data.list || [];
    } else {
      ElMessage.error(res.message || '加载数据失败');
    }
  }

  onMounted(() => {
    loadData();
  });
</script>

<style scoped>
  .user-manage {
    padding: 0;
  }

  .search-card {
    margin-bottom: 20px;
  }

  .search-card :deep(.el-card__body) {
    padding: 20px;
  }

  .table-card :deep(.el-card__header) {
    padding: 16px 20px;
    background-color: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
  }

  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: #495057;
  }

  .space-y-1>*+* {
    margin-top: 0.25rem;
  }

  .space-x-2>*+* {
    margin-left: 0.5rem;
  }

  .space-x-3>*+* {
    margin-left: 0.75rem;
  }

  .flex {
    display: flex;
  }

  .items-center {
    align-items: center;
  }

  .header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .font-medium {
    font-weight: 500;
  }

  .font-semibold {
    font-weight: 600;
  }

  .text-sm {
    font-size: 0.875rem;
  }

  .text-xs {
    font-size: 0.75rem;
  }

  .text-gray-500 {
    color: #6b7280;
  }

  .text-gray-600 {
    color: #4b5563;
  }

  .text-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .text-muted {
    color: #909399;
    font-size: 12px;
  }

  .block {
    display: block;
  }

  .break-all {
    word-break: break-all;
  }

  .cursor-pointer {
    cursor: pointer;
  }

  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
  }

  .user-detail {
    padding: 0;
  }

  .user-avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 20px;
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
  }

  .detail-avatar {
    margin-bottom: 12px;
    cursor: pointer;
  }

  .user-name {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  /* 凭证预览样式 */
  .voucher-preview {
    position: relative;
    display: inline-block;
  }

  .voucher-preview:hover .preview-icon {
    opacity: 1;
  }

  .preview-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background-color: rgba(0, 0, 0, 0.6);
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    opacity: 0;
    transition: opacity 0.3s;
    font-size: 14px;
  }

  .apply-time {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #606266;
  }

  .time-icon {
    font-size: 14px;
  }
</style>
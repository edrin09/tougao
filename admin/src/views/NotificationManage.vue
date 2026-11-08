<template>
  <div class="notification-manager">
    <!-- 页面标题 -->
    <el-card class="notification-header-card">
      <div class="notification-header">
        <div class="notification-title-section">
          <h2 class="notification-title">通知管理</h2>
          <p class="notification-subtitle">管理系统通知消息</p>
        </div>
        <el-button type="primary" @click="showAddDialog = true">
          <el-icon class="button-icon"><Plus /></el-icon>
          添加通知
        </el-button>
      </div>
    </el-card>

    <!-- 搜索和操作栏 -->
    <el-card class="notification-search-card">
      <div class="notification-search-bar">
        <div class="notification-search-left">
          <el-input
            v-model="searchForm.keyword"
            placeholder="搜索通知内容..."
            style="width: 300px"
            clearable
            @keyup.enter="handleSearch">
            <template #prefix>
              <el-icon><Search /></el-icon>
            </template>
          </el-input>
          <el-select
            v-model="searchForm.type"
            placeholder="选择通知类型"
            style="width: 200px"
            clearable>
            <el-option
              v-for="item in typeOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value">
            </el-option>
          </el-select>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </div>
        <div class="notification-search-right">
          <el-button
            type="danger"
            :disabled="selectedIds.length === 0"
            @click="handleBatchDelete">
            <el-icon class="button-icon"><Delete /></el-icon>
            批量删除
          </el-button>
          <el-button @click="loadNotificationList">
            <el-icon class="button-icon"><Refresh /></el-icon>
            刷新
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 通知列表 -->
    <el-card class="notification-table-card">
      <el-table
        v-loading="loading"
        :data="notificationList"
        @selection-change="handleSelectionChange"
        stripe>

        <el-table-column type="selection" width="55" />

        <el-table-column prop="id" label="ID" width="80" />

        <el-table-column prop="type_text" label="类型" width="120">
          <template #default="{ row }">
            <el-tag :type="row.type === 1 ? 'primary' : 'success'">
              {{ row.type_text }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column prop="content" label="通知内容" min-width="300">
          <template #default="{ row }">
            <div class="notification-content">
              {{ row.content }}
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="created_at" label="创建时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.created_at) }}
          </template>
        </el-table-column>

        <el-table-column prop="updated_at" label="更新时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.updated_at) }}
          </template>
        </el-table-column>

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <div class="notification-actions">
              <el-button size="small" @click="viewDetail(row)">
                <el-icon class="button-icon"><View /></el-icon>
                查看
              </el-button>
              <el-button size="small" type="primary" @click="editNotification(row)">
                <el-icon class="button-icon"><Edit /></el-icon>
                编辑
              </el-button>
              <el-button size="small" type="danger" @click="deleteNotification(row)">
                <el-icon class="button-icon"><Delete /></el-icon>
                删除
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="notification-pagination">
        <el-pagination
          v-model:current-page="currentPage"
          v-model:page-size="pageSize"
          :page-sizes="[10, 20, 50, 100]"
          :total="total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange" />
      </div>
    </el-card>

    <!-- 添加/编辑通知对话框 -->
    <el-dialog
      v-model="showAddDialog"
      :title="editingNotification ? '编辑通知' : '添加通知'"
      width="600px"
      :before-close="handleDialogClose">

      <el-form ref="notificationFormRef" :model="notificationForm" :rules="formRules" label-width="80px">
        <el-form-item label="通知类型" prop="type">
          <el-select
            v-model="notificationForm.type"
            placeholder="请选择通知类型"
            style="width: 100%">
            <el-option
              v-for="item in typeOptions"
              :key="item.value"
              :label="item.label"
              :value="item.value">
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="通知内容" prop="content">
          <el-input
            v-model="notificationForm.content"
            type="textarea"
            :rows="6"
            placeholder="请输入通知内容..."
            maxlength="1000"
            show-word-limit />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleDialogClose">取消</el-button>
          <el-button
            type="primary"
            :loading="submitLoading"
            @click="submitNotification">
            {{ editingNotification ? '更新' : '添加' }}
          </el-button>
        </span>
      </template>
    </el-dialog>

    <!-- 查看通知详情对话框 -->
    <el-dialog
      v-model="showDetailDialog"
      title="通知详情"
      width="600px">

      <div v-if="currentNotification" class="notification-detail">
        <div class="notification-detail-item">
          <label class="notification-detail-label">通知ID</label>
          <p class="notification-detail-value">{{ currentNotification.id }}</p>
        </div>

        <div class="notification-detail-item">
          <label class="notification-detail-label">通知类型</label>
          <el-tag :type="currentNotification.type === 1 ? 'primary' : 'success'">
            {{ currentNotification.type_text }}
          </el-tag>
        </div>

        <div class="notification-detail-item">
          <label class="notification-detail-label">通知内容</label>
          <div class="notification-detail-content">
            <p class="notification-detail-text">{{ currentNotification.content }}</p>
          </div>
        </div>

        <div class="notification-detail-time-grid">
          <div class="notification-detail-item">
            <label class="notification-detail-label">创建时间</label>
            <p class="notification-detail-value">{{ formatDate(currentNotification.created_at) }}</p>
          </div>
          <div class="notification-detail-item">
            <label class="notification-detail-label">更新时间</label>
            <p class="notification-detail-value">{{ formatDate(currentNotification.updated_at) }}</p>
          </div>
        </div>
      </div>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="showDetailDialog = false">关闭</el-button>
          <el-button type="primary" @click="editNotification(currentNotification)">
            <el-icon class="button-icon"><Edit /></el-icon>
            编辑
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { API } from '../api';

// 响应式数据 - 严格按照原组件
const loading = ref(false);
const submitLoading = ref(false);
const notificationList = ref([]);
const total = ref(0);
const currentPage = ref(1);
const pageSize = ref(20);
const selectedIds = ref([]);
const typeOptions = ref([]);

const showAddDialog = ref(false);
const showDetailDialog = ref(false);
const editingNotification = ref(null);
const currentNotification = ref(null);

// 表单引用
const notificationFormRef = ref(null);

const searchForm = reactive({
  keyword: '',
  type: ''
});

const notificationForm = reactive({
  content: '',
  type: 1
});

const formRules = {
  type: [
    { required: true, message: '请选择通知类型', trigger: 'change' }
  ],
  content: [
    { required: true, message: '请输入通知内容', trigger: 'blur' },
    { min: 1, max: 1000, message: '通知内容长度在 1 到 1000 个字符', trigger: 'blur' }
  ]
};

// 时间格式化函数 - 按原组件Utils.formatDate
function formatDate(time) {
  if (!time) return '-';
  return new Date(time).toLocaleString();
}

// 错误处理 - 按原组件
function handleApiError(error, defaultMessage = '操作失败') {
  console.error('API错误:', error);
  const message = error?.message || error?.data?.message || defaultMessage;
  ElMessage.error(message);
}

// 成功处理 - 按原组件
function handleApiSuccess(message) {
  ElMessage.success(message);
}

// 加载通知列表 - 严格按照原组件
async function loadNotificationList() {
  loading.value = true;
  try {
    const params = {
      page: currentPage.value,
      limit: pageSize.value,
      keyword: searchForm.keyword,
      type: searchForm.type
    };

    const response = await API.notificationManager.getList(params);

    // 兼容不同的响应格式
    if (response.code === 200) {
      // 直接响应格式
      notificationList.value = response.data?.list || [];
      total.value = response.data?.total || 0;
    } else if (response.data?.code === 200) {
      // 嵌套响应格式
      notificationList.value = response.data.data?.list || [];
      total.value = response.data.data?.total || 0;
    } else if (response.data && Array.isArray(response.data.list)) {
      // 模拟数据格式
      notificationList.value = response.data.list || [];
      total.value = response.data.total || 0;
    } else {
      handleApiError(response.data || response);
    }
  } catch (error) {
    handleApiError(error, '加载通知列表失败');
  } finally {
    loading.value = false;
  }
}

// 加载通知类型列表 - 按原组件
async function loadTypeList() {
  try {
    const response = await API.notificationManager.getTypeList();
    
    // 兼容不同的响应格式
    if (response.code === 200) {
      typeOptions.value = response.data || [];
    } else if (response.data?.code === 200) {
      typeOptions.value = response.data.data || [];
    } else if (response.data && Array.isArray(response.data)) {
      typeOptions.value = response.data || [];
    }
  } catch (error) {
    console.error('加载通知类型列表失败:', error);
  }
}

// 搜索
function handleSearch() {
  currentPage.value = 1;
  loadNotificationList();
}

// 重置搜索
function handleReset() {
  searchForm.keyword = '';
  searchForm.type = '';
  currentPage.value = 1;
  loadNotificationList();
}

// 分页变化
function handleSizeChange(size) {
  pageSize.value = size;
  currentPage.value = 1;
  loadNotificationList();
}

function handleCurrentChange(page) {
  currentPage.value = page;
  loadNotificationList();
}

// 选择变化
function handleSelectionChange(selection) {
  selectedIds.value = selection.map(item => item.id);
}

// 查看详情 - 按原组件
async function viewDetail(notification) {
  try {
    const response = await API.notificationManager.getDetail(notification.id);
    
    // 兼容不同的响应格式
    if (response.code === 200) {
      currentNotification.value = response.data;
      showDetailDialog.value = true;
    } else if (response.data?.code === 200) {
      currentNotification.value = response.data.data;
      showDetailDialog.value = true;
    } else {
      handleApiError(response.data || response);
    }
  } catch (error) {
    handleApiError(error, '获取通知详情失败');
  }
}

// 编辑通知
function editNotification(notification) {
  editingNotification.value = notification;
  notificationForm.content = notification.content;
  notificationForm.type = notification.type;
  showAddDialog.value = true;
  showDetailDialog.value = false;
}

// 删除通知 - 按原组件
function deleteNotification(notification) {
  ElMessageBox.confirm(
    `确定要删除这条通知吗？`,
    '确认删除',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  ).then(async () => {
    try {
      const response = await API.notificationManager.delete(notification.id);
      
      // 兼容不同的响应格式
      if (response.code === 200 || response.data?.code === 200) {
        handleApiSuccess('删除成功');
        loadNotificationList();
      } else {
        handleApiError(response.data || response);
      }
    } catch (error) {
      handleApiError(error, '删除失败');
    }
  });
}

// 批量删除 - 按原组件
function handleBatchDelete() {
  if (selectedIds.value.length === 0) {
    ElMessage.warning('请选择要删除的通知');
    return;
  }

  ElMessageBox.confirm(
    `确定要删除选中的 ${selectedIds.value.length} 条通知吗？`,
    '确认批量删除',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  ).then(async () => {
    try {
      const response = await API.notificationManager.batchDelete({
        ids: selectedIds.value
      });
      
      // 兼容不同的响应格式
      if (response.code === 200 || response.data?.code === 200) {
        const message = response.message || response.data?.msg || response.data?.message || '批量删除成功';
        handleApiSuccess(message);
        selectedIds.value = [];
        loadNotificationList();
      } else {
        handleApiError(response.data || response);
      }
    } catch (error) {
      handleApiError(error, '批量删除失败');
    }
  });
}

// 提交通知 - 严格按照原组件
async function submitNotification() {
  try {
    if (!notificationFormRef.value) {
      ElMessage.error('表单引用未找到');
      return;
    }
    await notificationFormRef.value.validate();

    submitLoading.value = true;

    let response;
    if (editingNotification.value) {
      // 更新
      response = await API.notificationManager.update(editingNotification.value.id, {
        content: notificationForm.content,
        type: notificationForm.type
      });
    } else {
      // 添加
      response = await API.notificationManager.add({
        content: notificationForm.content,
        type: notificationForm.type
      });
    }

    // 兼容不同的响应格式
    if (response.code === 200 || response.data?.code === 200) {
      const message = editingNotification.value ? '更新成功' : '添加成功';
      handleApiSuccess(message);
      handleDialogClose();
      loadNotificationList();
    } else {
      handleApiError(response.data || response);
    }
  } catch (error) {
    if (error !== false) { // 不是表单验证错误
      handleApiError(error, editingNotification.value ? '更新失败' : '添加失败');
    }
  } finally {
    submitLoading.value = false;
  }
}

// 关闭对话框 - 按原组件
function handleDialogClose() {
  showAddDialog.value = false;
  editingNotification.value = null;
  notificationForm.content = '';
  notificationForm.type = 1;

  // 重置表单验证
  nextTick(() => {
    if (notificationFormRef.value) {
      notificationFormRef.value.resetFields();
    }
  });
}

// 组件挂载时加载数据
onMounted(() => {
  loadNotificationList();
  loadTypeList();
});
</script>

<style scoped>
.notification-manager {
  padding: 0;
}

.notification-header-card {
  margin-bottom: 20px;
}

.notification-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.notification-title-section {
  flex: 1;
}

.notification-title {
  margin: 0 0 8px 0;
  font-size: 24px;
  font-weight: 600;
  color: #495057;
}

.notification-subtitle {
  margin: 0;
  color: #6c757d;
  font-size: 14px;
}

.button-icon {
  margin-right: 6px;
}

.notification-search-card {
  margin-bottom: 20px;
}

.notification-search-bar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
}

.notification-search-left {
  display: flex;
  gap: 12px;
  align-items: center;
  flex-wrap: wrap;
}

.notification-search-right {
  display: flex;
  gap: 12px;
}

.notification-table-card {
  background: white;
}

.notification-content {
  line-height: 1.5;
  word-break: break-word;
}

.notification-actions {
  display: flex;
  gap: 8px;
}

.notification-pagination {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

/* 通知详情样式 - 严格按照原组件 */
.notification-detail {
  padding: 0;
}

.notification-detail-item {
  margin-bottom: 20px;
}

.notification-detail-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #495057;
  font-size: 14px;
}

.notification-detail-value {
  margin: 0;
  color: #606266;
  line-height: 1.5;
}

.notification-detail-content {
  padding: 12px;
  background-color: #f8f9fa;
  border-radius: 6px;
  border-left: 4px solid #409eff;
}

.notification-detail-text {
  margin: 0;
  line-height: 1.6;
  word-break: break-word;
  color: #495057;
}

.notification-detail-time-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
</style> 
<template>
  <div class="comment-manage">
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
          <el-radio-group v-model="searchForm.status" @change="handleStatusChange">
            <el-radio-button label="all">全部</el-radio-button>
            <el-radio-button label="1">正常</el-radio-button>
            <el-radio-button label="0">待审核</el-radio-button>
            <el-radio-button label="-1">已驳回</el-radio-button>
            <el-radio-button label="-2">已删除</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item>
          <el-input v-model="searchForm.keyword" placeholder="搜索墙主ID或评论内容..." style="width: 300px" clearable
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
              <ChatDotRound />
            </el-icon>
            <span class="font-semibold">评论列表</span>
          </div>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe style="width: 100%">

        <el-table-column type="index" label="#" width="60" />

        <el-table-column label="用户信息" width="150">
          <template #default="{ row }">
            <div class="space-y-1">
              <div class="text-sm font-medium">{{ row.nickname || '匿名用户' }}</div>
              <div class="text-xs text-gray-500">ID: {{ row.openid || row.user_id || 'N/A' }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="评论内容" width="300">
          <template #default="{ row }">
            <div class="space-y-1">
              <div class="text-sm">{{ truncateText(row.content, 80) }}</div>
              <div v-if="row.parent_id" class="text-xs text-gray-500 bg-gray-50 p-1 rounded">
                回复: {{ truncateText(row.parent_content, 40) }}
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="wall_owner_id" label="墙主ID" width="100" />

        <el-table-column label="发布时间" width="180">
          <template #default="{ row }">
            {{ (row.create_time) }}
          </template>
        </el-table-column>

        <el-table-column label="状态" width="100">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="250">
          <template #default="{ row }">
            <div v-if="row.status != -2" class="flex space-x-2">
              <el-button size="small" type="primary" @click="viewDetail(row)">查看详情</el-button>

              <el-button v-if="row.status == 1" size="small" type="danger" @click="deleteComment(row)">
                <el-icon>
                  <Delete />
                </el-icon>删除
              </el-button>

              <el-button v-if="row.status == -1" size="small" type="success" @click="restoreComment(row)">
                <el-icon>
                  <Refresh />
                </el-icon>恢复
              </el-button>
            </div>
            <div v-else class="flex justify-center">
              <span class="text-gray-400 text-sm">已删除</span>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="pagination-wrapper">
        <el-pagination v-model:current-page="pagination.page" v-model:page-size="pagination.size"
          :page-sizes="[10, 20, 50, 100]" :total="pagination.total" layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange" @current-change="handleCurrentChange" />
      </div>
    </el-card>

    <!-- 详情弹窗 -->
    <el-dialog v-model="detailVisible" title="评论详情" width="600px" destroy-on-close>

      <div v-if="currentRow" class="comment-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="评论ID">{{ currentRow.id }}</el-descriptions-item>
          <el-descriptions-item label="用户昵称">{{ currentRow.nickname || '匿名用户' }}</el-descriptions-item>
          <el-descriptions-item label="用户ID">{{ currentRow.openid || currentRow.user_id || 'N/A'
          }}</el-descriptions-item>
          <el-descriptions-item label="墙主ID">{{ currentRow.wall_owner_id }}</el-descriptions-item>
          <el-descriptions-item label="发布时间">{{ (currentRow.create_time) }}</el-descriptions-item>
          <el-descriptions-item label="状态">
            <el-tag :type="getStatusType(currentRow.status)">
              {{ getStatusText(currentRow.status) }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <div class="content-section">
          <h4>评论内容</h4>
          <div class="content-text">{{ currentRow.content }}</div>
        </div>

        <div v-if="currentRow.parent_id" class="parent-section">
          <h4>回复的「{{ currentRow.reply_to_nickname }}」的评论</h4>
          <div class="parent-content">{{ currentRow.parent_content || '原评论已删除' }}</div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="detailVisible = false">关闭</el-button>
          <el-button v-if="currentRow?.status == 0" type="success" @click="approveComment(currentRow)">
            通过
          </el-button>
          <el-button v-if="currentRow?.status == 0" type="warning" @click="rejectComment(currentRow)">
            驳回
          </el-button>
          <el-button v-if="currentRow?.status == 1" type="danger" @click="deleteComment(currentRow)">
            删除
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { ElMessage, ElMessageBox } from 'element-plus';
  import { API } from '../api';

  const loading = ref(false);
  const detailVisible = ref(false);
  const currentRow = ref(null);

  const searchForm = reactive({
    status: 'all',
    keyword: ''
  });
  const autoCheck = ref(false);

  const pagination = reactive({
    page: 1,
    size: 20,
    total: 0
  });

  const tableData = ref([]);

  // 评论状态映射 - 按原组件的数字状态
  const statusMap = {
    1: { text: '正常', type: 'success' },
    0: { text: '待审核', type: 'warning' },
    '-1': { text: '已驳回', type: 'danger' },
    '-2': { text: '已删除', type: 'info' }
  };


  const uploadConfig = ref(
    {
      uploadUrl: `${import.meta.env.VITE_BASE_API || '/api/admin'}/config/sensitiveUpload`,
      fileList: []
    }
  )

  async function toggleCheck(value) {
    try {
      const res = await API.comment.automaticCheck({ audit: value });
      if (res.code === 200) {
        ElMessage.success('设置成功');
      } else {
        ElMessage.error(res.message || '加载数据失败');
      }
    } catch (error) {
      autoCheck.value = false;
    }
  }

  function getStatusText(status) {
    return statusMap[status]?.text || '未知';
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

  function handleStatusChange() {
    pagination.page = 1;
    loadData();
  }

  function handleSearch() {
    pagination.page = 1;
    loadData();
  }

  function handleSizeChange(size) {
    pagination.size = size;
    pagination.page = 1;
    loadData();
  }

  function handleCurrentChange(page) {
    pagination.page = page;
    loadData();
  }

  async function loadData() {
    try {
      loading.value = true;
      const params = {
        page: pagination.page,
        limit: pagination.size,
        ...searchForm
      };

      // 如果是'all'，不传status参数
      if (params.status === 'all') {
        delete params.status;
      }

      const res = await API.comment.getList(params);

      if (res.code === 200) {
        const data = res.data || {};
        tableData.value = data.list || [];
        pagination.total = data.total || 0;
      } else {
        ElMessage.error(res.message || '加载数据失败');
      }
    } catch (error) {
      console.error('加载评论列表失败:', error);
      ElMessage.error('加载数据失败，请检查网络连接');
    } finally {
      loading.value = false;
    }
  }
  // const elUpload = ref(null)
  // async function handleExceed(files) {
  //   elUpload.value?.clearFiles()
  //   const file = files[0]
  //   file.uid = genFileId()
  //   elUpload.value?.handleStart(file)
  // }
  function genFileId() {
    return Date.now() + Math.random()
  }

  async function handleBeforeUpload(file) {

    if (file.type !== 'text/plain') {
      ElMessage.warning('只能上传txt文件')
      return false
    }
  }

  function viewDetail(row) {
    currentRow.value = row;
    detailVisible.value = true;
  }

  async function deleteComment(comment) {
    try {
      await ElMessageBox.confirm('确定要删除这条评论吗？', '确认删除', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      });

      const res = await API.comment.delete(comment.id);

      if (res.code === 200) {
        ElMessage.success('删除成功');
        detailVisible.value = false;
        loadData();
      } else {
        ElMessage.error(res.message || '删除失败');
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('删除评论失败:', error);
        ElMessage.error('删除失败');
      }
    }
  }

  async function approveComment(comment) {
    try {
      await ElMessageBox.confirm('确定要通过这条评论吗？', '确认通过', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      });

      const res = await API.comment.approve(comment.id);

      if (res.code === 200) {
        ElMessage.success('通过成功');
        detailVisible.value = false;
        loadData();
      } else {
        ElMessage.error(res.message || '操作失败');
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('通过评论失败:', error);
        ElMessage.error('操作失败');
      }
    }
  }

  async function rejectComment(comment) {
    try {
      await ElMessageBox.confirm('确定要驳回这条评论吗？', '确认驳回', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      });

      const res = await API.comment.reject(comment.id);

      if (res.code === 200) {
        ElMessage.success('驳回成功');
        detailVisible.value = false;
        loadData();
      } else {
        ElMessage.error(res.message || '操作失败');
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('驳回评论失败:', error);
        ElMessage.error('操作失败');
      }
    }
  }

  async function restoreComment(comment) {
    try {
      await ElMessageBox.confirm('确定要恢复这条评论吗？', '确认恢复', {
        confirmButtonText: '确定',
        cancelButtonText: '取消',
        type: 'warning'
      });

      const res = await API.comment.restore(comment.id);

      if (res.code === 200) {
        ElMessage.success('恢复成功');
        detailVisible.value = false;
        loadData();
      } else {
        ElMessage.error(res.message || '恢复失败');
      }
    } catch (error) {
      if (error !== 'cancel') {
        console.error('恢复评论失败:', error);
        ElMessage.error('恢复失败');
      }
    }
  }

  onMounted(() => {
    loadData();
  });
</script>

<style scoped>
  .comment-manage {
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

  .flex {
    display: flex;
  }

  .items-center {
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

  .text-gray-400 {
    color: #9ca3af;
  }

  .justify-center {
    justify-content: center;
  }

  .bg-gray-50 {
    background-color: #f9fafb;
  }

  .p-1 {
    padding: 0.25rem;
  }

  .rounded {
    border-radius: 0.25rem;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
  }

  .comment-detail {
    padding: 0;
  }

  .content-section,
  .parent-section {
    margin-top: 20px;
  }

  .content-section h4,
  .parent-section h4 {
    margin-bottom: 12px;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
  }

  .content-text,
  .parent-content {
    line-height: 1.6;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    word-break: break-word;
  }

  .parent-content {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }
</style>
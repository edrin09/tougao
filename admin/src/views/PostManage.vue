<template>
  <div class="post-manage">
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
            <el-radio-button label="0">待审核</el-radio-button>
            <el-radio-button label="1">已通过</el-radio-button>
            <el-radio-button label="-1">已驳回</el-radio-button>
          </el-radio-group>
        </el-form-item>

        <el-form-item>
          <el-input v-model="searchForm.keyword" placeholder="搜索标题或内容..." style="width: 300px" clearable
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
          <el-input v-model="searchForm.wall_owner_id" placeholder="搜索墙主ID..." style="width: 200px" clearable
            @keyup.enter="handleSearch" />
        </el-form-item>

        <el-form-item>
          <div class="text-sm text-gray-600">
            共 {{ pagination.total }} 条记录
          </div>
        </el-form-item>
        <el-form-item label="敏感词自动过滤">
          <el-switch v-model="autoCheck" inline-prompt class="mb-2" @change="toggleCheck"
            style="--el-switch-on-color: #13ce66; " active-text="开" inactive-text="关" />
        </el-form-item>
        <el-form-item>
          <el-upload :show-file-list="false" ref="elUpload" :file-list="uploadConfig.fileList" class="upload-demo"
            :action="uploadConfig.uploadUrl" :before-upload="handleBeforeUpload" :on-exceed="handleExceed"
            :on-success="() => ElMessage.success('上传成功！')" :limit="1">
            <el-button type="primary">
              <el-icon>
                <Upload />
              </el-icon>
            </el-button>
          </el-upload>
        </el-form-item>
      </el-form>

    </el-card>

    <!-- 数据表格 -->
    <el-card class="table-card" shadow="never">
      <template #header>
        <div class="card-header">
          <div class="flex items-center">
            <el-icon class="mr-2">
              <Document />
            </el-icon>
            <span class="font-semibold">投稿列表</span>
          </div>


          <div class="header-actions">
            <!-- 导出按钮 -->
            <el-button type="primary" size="small" @click="exportData" :loading="exportLoading">
              <el-icon>
                <Download />
              </el-icon>导出Excel
            </el-button>

            <!-- 批量操作按钮 -->
            <div v-if="selectedIds.length > 0 && searchForm.status === '0'" class="batch-actions">
              <el-button type="success" size="small" @click="batchApprove">
                <el-icon>
                  <Check />
                </el-icon>批量通过 ({{ selectedIds.length }})
              </el-button>
              <el-button type="danger" size="small" @click="batchReject">
                <el-icon>
                  <Close />
                </el-icon>批量驳回 ({{ selectedIds.length }})
              </el-button>
            </div>
          </div>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe style="width: 100%"
        @selection-change="handleSelectionChange">

        <el-table-column v-if="searchForm.status === '0'" type="selection" width="55" />

        <el-table-column type="index" label="#" width="60" />

        <el-table-column label="投稿信息" width="180">
          <template #default="{ row }">
            <div class="space-y-1">
              <div class="font-medium">{{ truncateText(row.title, 20) }}</div>
              <div class="text-sm text-gray-500">ID: {{ row.id }}</div>
              <div class="text-sm text-gray-500">标签: {{ row.tag || '无' }}</div>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="墙名称" width="150">
          <template #default="{ row }">
            <span class="font-medium text-blue-600">{{ row.wall_name || '未知墙面' }}</span>
          </template>
        </el-table-column>

        <el-table-column label="海报" width="100">
          <template #default="{ row }">
            <el-image v-if="row.post_image" :src="row.post_image" :preview-src-list="[row.post_image]"
              class="w-12 h-12 rounded cursor-pointer" :preview-teleported="true" :z-index="10000" fit="cover">
              <template #error>
                <div class="w-12 h-12 bg-gray-100 flex items-center justify-center rounded">
                  <el-icon class="text-gray-400">
                    <Picture />
                  </el-icon>
                </div>
              </template>
            </el-image>
            <div v-else class="w-12 h-12 bg-gray-100 flex items-center justify-center rounded">
              <el-icon class="text-gray-400">
                <Picture />
              </el-icon>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="海报(无联系方式)" width="100">
          <template #default="{ row }">
            <el-image v-if="row.no_contact_post_image" :src="row.no_contact_post_image" :preview-src-list="[row.no_contact_post_image]"
              class="w-12 h-12 rounded cursor-pointer" :preview-teleported="true" :z-index="10000" fit="cover">
              <template #error>
                <div class="w-12 h-12 bg-gray-100 flex items-center justify-center rounded">
                  <el-icon class="text-gray-400">
                    <Picture />
                  </el-icon>
                </div>
              </template>
            </el-image>
            <div v-else class="w-12 h-12 bg-gray-100 flex items-center justify-center rounded">
              <el-icon class="text-gray-400">
                <Picture />
              </el-icon>
            </div>
          </template>
        </el-table-column>

        <el-table-column label="内容预览" width="250">
          <template #default="{ row }">
            <div class="space-y-2">
              <div class="text-sm">{{ truncateText(row.content, 50) }}</div>
              <div v-if="row.images && row.images.length > 0" class="flex space-x-1">
                <el-image v-for="(img, index) in row.images.slice(0, 2)" :key="index" :src="img"
                  :preview-teleported="true" :z-index="10000" class="w-6 h-6 rounded cursor-pointer" fit="cover"
                  :preview-src-list="row.images" />
                <span v-if="row.images.length > 2" class="text-xs text-gray-500 self-center">
                  +{{ row.images.length - 2 }}
                </span>
              </div>
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="wall_owner_id" label="墙主ID" width="100" />

        <el-table-column label="提交时间" prop="create_time" width="180">
        </el-table-column>

        <el-table-column label="状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.status)">
              {{ getStatusText(row.status) }}
            </el-tag>
          </template>
        </el-table-column>

        <el-table-column label="操作" width="200">
          <template #default="{ row }">
            <div class="flex space-x-2">
              <el-button size="small" type="primary" @click="viewDetail(row)">查看详情</el-button>

              <el-button v-if="row.status == 0" size="small" type="success" @click="approvePost(row)">
                通过
              </el-button>

              <el-button v-if="row.status == 0" size="small" type="danger" @click="rejectPost(row)">
                驳回
              </el-button>
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
    <el-dialog v-model="detailVisible" title="投稿详情" width="800px" destroy-on-close>

      <div v-if="currentRow" class="post-detail">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="投稿ID">{{ currentRow.id }}</el-descriptions-item>
          <el-descriptions-item label="标题">{{ currentRow.title }}</el-descriptions-item>
          <el-descriptions-item label="墙主ID">{{ currentRow.wall_owner_id }}</el-descriptions-item>
          <el-descriptions-item label="墙名称">{{ currentRow.wall_name || '未知墙面' }}</el-descriptions-item>
          <el-descriptions-item label="标签">{{ currentRow.tag || '无' }}</el-descriptions-item>
          <el-descriptions-item label="提交时间">{{ (currentRow.create_time) }}</el-descriptions-item>
          <el-descriptions-item label="当前状态" :span="2">
            <el-tag :type="getStatusType(currentRow.status)">
              {{ getStatusText(currentRow.status) }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <div class="content-section">
          <h4>投稿内容</h4>
          <div class="content-text">{{ currentRow.content }}</div>
        </div>

        <div v-if="currentRow.post_image" class="poster-section">
          <h4>海报图片</h4>
          <el-image :src="currentRow.post_image" :preview-teleported="true" :z-index="10000" class="poster-image"
            :preview-src-list="[currentRow.post_image]" />
        </div>

        <div v-if="currentRow.images && currentRow.images.length" class="images-section">
          <h4>投稿图片</h4>
          <div class="image-gallery">
            <el-image v-for="(img, index) in currentRow.images" :key="index" :src="img" :preview-teleported="true"
              :z-index="10000" :preview-src-list="currentRow.images" class="preview-image" />
          </div>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="detailVisible = false">关闭</el-button>
          <el-button v-if="currentRow?.status == 0" type="success" @click="approvePost(currentRow)">
            通过审核
          </el-button>
          <el-button v-if="currentRow?.status == 0" type="danger" @click="rejectPost(currentRow)">
            驳回审核
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 审核对话框 -->
    <el-dialog v-model="reviewVisible" :title="reviewAction === 'approve' ? '通过审核' : '驳回审核'" width="500px"
      destroy-on-close>

      <el-form :model="reviewForm" label-width="80px">
        <el-form-item label="审核意见">
          <el-input v-model="reviewForm.comment" type="textarea" :rows="4" placeholder="请输入审核意见..." />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="reviewVisible = false">取消</el-button>
          <el-button :type="reviewAction === 'approve' ? 'success' : 'danger'" @click="submitReview"
            :loading="reviewLoading">
            确认{{ reviewAction === 'approve' ? '通过' : '驳回' }}
          </el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
  import { ref, reactive, onMounted, watch } from 'vue';
  import { ElMessage, ElMessageBox } from 'element-plus';
  import { API } from '../api';
  import { systemStore } from '../store/system';
  import * as XLSX from 'xlsx';

  const loading = ref(false);
  const detailVisible = ref(false);
  const reviewVisible = ref(false);
  const reviewLoading = ref(false);
  const exportLoading = ref(false);
  const currentRow = ref(null);
  const selectedIds = ref([]);
  const reviewAction = ref('');
  const isBatchOperation = ref(false);
  const autoCheck = ref(false);//敏感词自动过滤
  const system = systemStore();

  watch(() => system.sysConfig, (val) => {
    if (val) {
      autoCheck.value = Boolean(Number(val.sensitive_word_filtering.value));
    }
  }, {
    immediate: true
  })

  const uploadConfig = ref(
    {
      uploadUrl: `${import.meta.env.VITE_BASE_API || '/api/admin'}/config/sensitiveUpload`,
      fileList: []
    }
  )

  const searchForm = reactive({
    status: 'all',
    keyword: '',
    wall_owner_id: ''
  });

  const reviewForm = reactive({
    comment: ''
  });

  const pagination = reactive({
    page: 1,
    size: 20,
    total: 0
  });

  const tableData = ref([]);

  // 状态映射 - 按原组件的数字状态
  const statusMap = {
    0: { text: '待审核', type: 'warning' },
    1: { text: '已通过', type: 'success' },
    '-1': { text: '已驳回', type: 'danger' },
  };

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

  function handleSelectionChange(selection) {
    selectedIds.value = selection;
  }

  async function toggleCheck(value) {
    try {
      const res = await API.post.sensitiveWordFiltering({ audit: value });
      if (res.code === 200) {
        ElMessage.success('设置成功');
        let sysConfig = system.sysConfig
        if (sysConfig) {
          sysConfig.sensitive_word_filtering.value = Number(value)
          system.setConfig(sysConfig)
        }

      } else {
        ElMessage.error(res.message || '设置失败');
      }
    } catch (error) {
      autoCheck.value = false;
    }
  }
  // const elUpload = ref(null)
  // async function handleExceed(files) {
  //   elUpload.value?.clearFiles()
  //   const file = files[0]
  //   file.uid = genFileId()
  //   elUpload.value?.handleStart(file)
  // }
  async function handleBeforeUpload(file) {
    if (file.type !== 'text/plain') {
      ElMessage.warning('只能上传txt文件')
      return false
    }
  }

  function handleStatusChange() {
    pagination.page = 1;
    selectedIds.value = [];
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

      const res = await API.post.getList(params);

      if (res.code === 200) {
        const data = res.data || {};
        tableData.value = data.list || [];
        pagination.total = data.total || 0;
      } else {
        ElMessage.error(res.message || '加载数据失败');
      }
    } catch (error) {
      console.error('加载投稿列表失败:', error);
      ElMessage.error('加载数据失败，请检查网络连接');
    } finally {
      loading.value = false;
    }
  }

  function viewDetail(row) {
    currentRow.value = row;
    detailVisible.value = true;
  }

  function approvePost(post) {
    currentRow.value = post;
    reviewAction.value = 'approve';
    reviewForm.comment = '';
    isBatchOperation.value = false;
    reviewVisible.value = true;
    detailVisible.value = false;
  }

  function rejectPost(post) {
    currentRow.value = post;
    reviewAction.value = 'reject';
    reviewForm.comment = '';
    isBatchOperation.value = false;
    reviewVisible.value = true;
    detailVisible.value = false;
  }

  function batchApprove() {
    if (selectedIds.value.length === 0) {
      ElMessage.warning('请选择要操作的投稿');
      return;
    }
    reviewAction.value = 'approve';
    reviewForm.comment = '';
    isBatchOperation.value = true;
    reviewVisible.value = true;
  }

  function batchReject() {
    if (selectedIds.value.length === 0) {
      ElMessage.warning('请选择要操作的投稿');
      return;
    }
    reviewAction.value = 'reject';
    reviewForm.comment = '';
    isBatchOperation.value = true;
    reviewVisible.value = true;
  }

  async function submitReview() {
    try {
      reviewLoading.value = true;

      if (isBatchOperation.value) {
        // 批量操作
        const postIds = selectedIds.value.map(post => post.post_id || post.id);

        if (postIds.length === 0 || postIds.some(id => !id)) {
          ElMessage.error('无法获取有效的投稿ID');
          return;
        }

        console.log('批量审核投稿IDs:', postIds, '操作:', reviewAction.value);

        const res = await API.post.batchReview({
          post_ids: postIds,
          action: reviewAction.value,
          comment: reviewForm.comment || '无备注'
        });

        if (res.code === 200) {
          ElMessage.success(`批量${reviewAction.value === 'approve' ? '通过' : '驳回'}成功`);
          selectedIds.value = [];
          reviewVisible.value = false;
          loadData();
        } else {
          ElMessage.error(res.message || '操作失败');
        }
      } else {
        // 单个操作
        const postId = currentRow.value.post_id || currentRow.value.id;

        if (!postId) {
          ElMessage.error('无法获取投稿ID');
          return;
        }

        console.log('审核投稿ID:', postId, '操作:', reviewAction.value);

        const res = await API.post.review(postId, {
          action: reviewAction.value,
          comment: reviewForm.comment || '无备注'
        });

        if (res.code === 200) {
          ElMessage.success(`${reviewAction.value === 'approve' ? '通过' : '驳回'}成功`);
          reviewVisible.value = false;
          loadData();
        } else {
          ElMessage.error(res.message || '操作失败');
        }
      }
    } catch (error) {
      console.error('审核失败:', error);
      ElMessage.error('操作失败');
    } finally {
      reviewLoading.value = false;
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

      // 如果是'all'，不传status参数
      if (params.status === 'all') {
        delete params.status;
      }

      const res = await API.post.getList(params);

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
          '投稿ID': item.post_id,
          '标题': item.title || '',
          '内容': item.content || '',
          '标签': item.tag || '无',
          '墙名称': item.wall_name || '未知墙面',
          '墙主ID': item.wall_owner_id || '',
          '海报': item.post_image || '无',
          '状态': getStatusText(item.status),
          '提交时间': item.create_time || ''
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
          { wch: 15 }, // 墙名称
          { wch: 12 }, // 墙主ID
          { wch: 25 }, // 海报
          { wch: 10 }, // 状态
          { wch: 20 }  // 提交时间
        ];
        worksheet['!cols'] = colWidths;

        // 生成文件名
        const now = new Date();
        const fileName = `投稿列表_${now.getFullYear()}${(now.getMonth() + 1).toString().padStart(2, '0')}${now.getDate().toString().padStart(2, '0')}_${now.getHours().toString().padStart(2, '0')}${now.getMinutes().toString().padStart(2, '0')}.xlsx`;

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

  onMounted(() => {
    loadData();
  });
</script>

<style scoped>
  .post-manage {
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

  .header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
  }

  .batch-actions {
    display: flex;
    gap: 8px;
    margin-left: 12px;
    padding-left: 12px;
    border-left: 1px solid #e9ecef;
  }

  .space-y-1>*+* {
    margin-top: 0.25rem;
  }

  .space-y-2>*+* {
    margin-top: 0.5rem;
  }

  .space-x-1>*+* {
    margin-left: 0.25rem;
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

  .text-gray-400 {
    color: #9ca3af;
  }

  .text-gray-500 {
    color: #6b7280;
  }

  .text-gray-600 {
    color: #4b5563;
  }

  .text-blue-600 {
    color: #2563eb;
  }

  .bg-gray-100 {
    background-color: #f3f4f6;
  }

  .w-6 {
    width: 1.5rem;
  }

  .h-6 {
    height: 1.5rem;
  }

  .w-12 {
    width: 3rem;
  }

  .h-12 {
    height: 3rem;
  }

  .rounded {
    border-radius: 0.25rem;
  }

  .cursor-pointer {
    cursor: pointer;
  }

  .self-center {
    align-self: center;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
  }

  .post-detail {
    padding: 0;
  }

  .content-section,
  .poster-section,
  .images-section {
    margin-top: 20px;
  }

  .content-section h4,
  .poster-section h4,
  .images-section h4 {
    margin-bottom: 12px;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
  }

  .content-text {
    line-height: 1.6;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    word-break: break-word;
  }

  .poster-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 6px;
  }

  .image-gallery {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
  }

  .preview-image {
    width: 100px;
    height: 100px;
    border-radius: 6px;
    cursor: pointer;
  }

  .dialog-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
  }

  .el-form--inline .el-form-item {
    margin-right: 18px;
  }
</style>
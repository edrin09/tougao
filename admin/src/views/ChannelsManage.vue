<template>
  <div class="notification-manager">
    <!-- 页面标题 -->
    <el-card class="notification-header-card">
      <div class="notification-header">
        <div class="notification-title-section">
          <h2 class="notification-title">渠道管理</h2>
        </div>
        <el-button type="primary" @click="showAddDialog = true">
          <el-icon class="button-icon">
            <Plus />
          </el-icon>
          添加渠道
        </el-button>
      </div>
    </el-card>

    <!-- 搜索和操作栏 -->
    <el-card class="notification-search-card">
      <div class="notification-search-bar">
        <div class="notification-search-left">
          <el-input v-model="searchForm.keyword" placeholder="搜索渠道名称..." style="width: 300px" clearable
            @keyup.enter="handleSearch">
            <template #prefix>
              <el-icon>
                <Search />
              </el-icon>
            </template>
          </el-input>
          <el-button type="primary" @click="handleSearch">搜索</el-button>
          <el-button @click="handleReset">重置</el-button>
        </div>
        <div class="notification-search-right">
          <el-button @click="loadChannelsList">
            <el-icon class="button-icon">
              <Refresh />
            </el-icon>
            刷新
          </el-button>
        </div>
      </div>
    </el-card>

    <!-- 通知列表 -->
    <el-card class="notification-table-card">
      <el-table v-loading="loading" :data="channelsListWithStatus" @selection-change="handleSelectionChange" stripe>

        <el-table-column type="selection" width="55" />

        <el-table-column prop="channels_id" label="ID" width="80" />

        <el-table-column prop="content" label="渠道名称" min-width="300">
          <template #default="{ row }">
            <div class="notification-content">
              {{ row.channels_name }}
            </div>
          </template>
        </el-table-column>

        <el-table-column prop="content" label="状态" min-width="300">
          <template #default="{ row }">
            <el-switch :model-value="row.statusBoolean" @change="(value) => handleToggle(row, value)" />
          </template>
        </el-table-column>

        <el-table-column prop="updated_at" label="更新时间" width="180">
          <template #default="{ row }">
            {{ formatDate(row.update_time) }}
          </template>
        </el-table-column>

        <el-table-column label="操作" width="200" fixed="right">
          <template #default="{ row }">
            <div class="notification-actions">
              <el-button size="small" type="primary" @click="editNotification(row)">
                <el-icon class="button-icon">
                  <Edit />
                </el-icon>
                编辑
              </el-button>
            </div>
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <div class="notification-pagination">
        <el-pagination v-model:current-page="currentPage" v-model:page-size="pageSize" :page-sizes="[10, 20, 50, 100]"
          :total="total" layout="total, sizes, prev, pager, next, jumper" @size-change="handleSizeChange"
          @current-change="handleCurrentChange" />
      </div>
    </el-card>

    <!-- 添加/编辑通知对话框 -->
    <el-dialog v-model="showAddDialog" :title="editingNotification ? '编辑渠道' : '添加渠道'" width="600px"
      :before-close="handleDialogClose">

      <el-form ref="notificationFormRef" :model="notificationForm" :rules="formRules" label-width="80px">
        <el-form-item label="渠道名称" prop="channels_name">
          <el-input v-model="notificationForm.channels_name" type="text" placeholder="请输入渠道名称..." maxlength="100"
            show-word-limit />
        </el-form-item>
      </el-form>

      <template #footer>
        <span class="dialog-footer">
          <el-button @click="handleDialogClose">取消</el-button>
          <el-button type="primary" :loading="submitLoading" @click="submitNotification">
            {{ editingNotification ? '更新' : '添加' }}
          </el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
  import { nextTick, onMounted, reactive, ref, computed } from 'vue';
  import { ElMessage } from 'element-plus';
  import { API } from '../api';

  // 响应式数据 - 严格按照原组件
  const loading = ref(false);
  const submitLoading = ref(false);
  const notificationList = ref([]);
  const total = ref(0);
  const currentPage = ref(1);
  const pageSize = ref(20);
  const selectedIds = ref([]);
  const currentApply = ref(null);
  const Status = ref(false);

  const showAddDialog = ref(false);
  const showDetailDialog = ref(false);
  const editingNotification = ref(null);

  // 表单引用
  const notificationFormRef = ref(null);

  const searchForm = reactive({
    keyword: '',
    type: ''
  });

  const notificationForm = reactive({
    channels_name: '', // 修正字段名
    type: 1
  });

  // 修正表单验证规则
  const formRules = {
    channels_name: [
      { required: true, message: '请输入渠道名称', trigger: 'blur' },
      { min: 1, max: 100, message: '渠道名称长度在 1 到 100 个字符', trigger: 'blur' }
    ],
  };

  // 关键解决方案：使用计算属性处理状态类型转换
  const channelsListWithStatus = computed(() => {
    return notificationList.value.map(item => ({
      ...item,
      // 将数字状态转换为布尔值，避免类型不一致导致的change事件触发
      statusBoolean: Boolean(item.status === 1 || item.status === '1' || item.status === true)
    }));
  });

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
  async function loadChannelsList() {
    loading.value = true;
    try {
      const params = {
        page: currentPage.value,
        limit: pageSize.value,
        keyword: searchForm.keyword,
      };

      const response = await API.channelsManager.getList(params);

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
      handleApiError(error, '加载渠道列表失败');
    } finally {
      loading.value = false;
    }
  }

  // 搜索
  function handleSearch() {
    currentPage.value = 1;
    loadChannelsList();
  }

  // 重置搜索
  function handleReset() {
    searchForm.keyword = '';
    searchForm.type = '';
    currentPage.value = 1;
    loadChannelsList();
  }

  // 分页变化
  function handleSizeChange(size) {
    pageSize.value = size;
    currentPage.value = 1;
    loadChannelsList();
  }

  function handleCurrentChange(page) {
    currentPage.value = page;
    loadChannelsList();
  }

  // 选择变化
  function handleSelectionChange(selection) {
    selectedIds.value = selection.map(item => item.id);
  }

  // 编辑通知
  function editNotification(notification) {
    editingNotification.value = notification;
    notificationForm.channels_name = notification.channels_name;
    showAddDialog.value = true;
    showDetailDialog.value = false;
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
        response = await API.channelsManager.update(editingNotification.value.channels_id, {
          channels_name: notificationForm.channels_name,
        });
      } else {
        // 添加
        response = await API.channelsManager.add({
          channels_name: notificationForm.channels_name,
        });
      }

      // 兼容不同的响应格式
      if (response.code === 200 || response.data?.code === 200) {
        const message = editingNotification.value ? '更新成功' : '添加成功';
        handleApiSuccess(message);
        handleDialogClose();
        loadChannelsList();
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

  // 关闭对话框 - 修正重置逻辑
  function handleDialogClose() {
    showAddDialog.value = false;
    editingNotification.value = null;
    notificationForm.channels_name = ''; // 修正字段名
    notificationForm.type = 1;

    // 重置表单验证
    nextTick(() => {
      if (notificationFormRef.value) {
        notificationFormRef.value.resetFields();
      }
    });
  }

  // 状态切换处理 - 修改参数接收方式
  async function handleToggle(row, newValue) {
    try {
      const res = await API.channelsManager.updateStatus({
        channels_id: row.channels_id,
        status: newValue ? 1 : 0
      });

      if (res.code === 200 || res.data?.code === 200) {
        // 更新原始数据中的状态
        const originalItem = notificationList.value.find(item => item.channels_id === row.channels_id);
        if (originalItem) {
          originalItem.status = newValue ? 1 : 0;
        }
        handleApiSuccess('状态已更新');
      } else {
        handleApiError(res.data || res);
      }
    } catch (error) {
      handleApiError(error, '状态更新失败');
    }
  }

  // 组件挂载时加载数据
  onMounted(() => {
    loadChannelsList();
  });
</script>
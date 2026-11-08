<template>
  <div class="wall-master-manage">
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
          <el-radio-group v-model="searchForm.withdraw_status" @change="handleStatusChange">
            <el-radio-button label="all">全部</el-radio-button>
            <el-radio-button label="0">待审核</el-radio-button>
            <el-radio-button label="2">待打款</el-radio-button>
            <el-radio-button label="3">审核拒绝</el-radio-button>
            <el-radio-button label="5">已完成</el-radio-button>
            <el-radio-button label="10">已取消</el-radio-button>
          </el-radio-group>
        </el-form-item>
        <el-form-item>
          <el-select style="width:100px" v-model="searchForm.withdrawal_method" placeholder="提现方式" clearable
            @change="loadData">
            <el-option label="全部" value="" />
            <el-option label="零钱提现" value="wechat" />
            <el-option label="线下提现" value="offline" />
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
            <span class="font-semibold">申请列表</span>
          </div>
        </div>
      </template>

      <el-table :data="tableData" v-loading="loading" stripe style="width: 100%">

         <!--<el-table-column type="index" label="#" width="60" /> -->
        <el-table-column label="提现类型" width="90">
          <template #default="{ row }">
            {{ row.withdrawal_method === 'wechat' ? '微信零钱' : '线下转账' }}
          </template>
        </el-table-column>
        <el-table-column label="提现状态" width="120">
          <template #default="{ row }">
            <el-tag :type="getStatusType(row.withdraw_status)">
              {{ getStatusText(row.withdraw_status) }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="提现金额" width="90">
          <template #default="{ row }">
            ¥{{ row.cash_amount || '0.00' }}
          </template>
        </el-table-column>
        <el-table-column label="提现时间" width="100">
          <template #default="{ row }">
            <div class="apply-time">
              {{ formatTime(row.create_time) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="打款时间" width="100">
          <template #default="{ row }">
            <div class="apply-time">
              {{ formatTime(row.cash_time) }}
            </div>
          </template>
        </el-table-column>
        <el-table-column label="用户信息" width="150">
          <template #default="{ row }">
            <div class="user-info">
              <el-avatar :src="row.user.avatar" :size="40" class="user-avatar" @click="previewImage(row.user.avatar)">
                <img src="https://via.placeholder.com/40x40/409EFF/FFFFFF?text=?" />
              </el-avatar>
              <span class="user-name">{{ row.user.nickname || '未知用户' }}</span>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="卡号/openid" prop="bank_card" width="250" />
        <el-table-column label="开户行" prop="bank_name" width="120" />
        <el-table-column label="姓名" prop="payee_name" width="100" />
        <el-table-column label="审核意见" prop="reject_remarks" width="200" />
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
        <el-table-column label="操作" width="250">
          <template #default="{ row }">
            <div class="action-buttons">
              <el-upload ref="uploadRef" :action="uploadUrl" :data="{
                withdrawal_id: row.withdrawal_id,
                withdraw_status: 5,
              }" :headers="uploadHeaders" :show-file-list="false" :before-upload="beforeUpload"
                :on-success="handleUploadSuccess" :on-error="handleUploadError" :loading="uploadLoading"
                accept="image/*">
                <el-button size="small" v-if="row.withdraw_status == 2" type="primary">上传打款凭证</el-button>
              </el-upload>
              <el-button v-if="row.withdraw_status == 0" size="small" type="success" @click="approveApply(row)">
                通过
              </el-button>
              <el-button v-if="row.withdraw_status == 0" size="small" type="danger" @click="rejectApply(row)">
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
    <el-dialog v-model="detailVisible" title="提现详情" width="800px" destroy-on-close>

      <div v-if="currentRow" class="detail-content">
        <!-- 用户信息卡片 -->
        <el-card class="detail-card">
          <div class="detail-user-section">
            <div class="detail-avatar-section">
              <el-avatar :src="currentRow.avatar" :size="80" class="detail-avatar-large detail-avatar-margin"
                @click="previewImage(currentRow.avatar)">
                <img src="https://via.placeholder.com/80x80/409EFF/FFFFFF?text=?" />
              </el-avatar>
              <div class="detail-user-name">{{ currentRow.nickname || '未知用户' }}</div>
              <el-tag v-if="currentRow.isVip === 1" type="warning" class="detail-tag-margin">
                <el-icon class="icon-margin-right">
                  <Star />
                </el-icon>
                VIP用户
              </el-tag>
              <el-tag v-else type="info" class="detail-tag-margin">
                <el-icon class="icon-margin-right">
                  <User />
                </el-icon>
                普通用户
              </el-tag>
            </div>

            <div class="detail-descriptions">
              <el-descriptions :column="2" border>
                <el-descriptions-item label="申请ID">
                  <span class="text-ellipsis" :title="currentRow.applyId">
                    {{ currentRow.applyId }}
                  </span>
                </el-descriptions-item>
                <el-descriptions-item label="申请人昵称">
                  {{ currentRow.nickname || '未知用户' }}
                </el-descriptions-item>
                <el-descriptions-item label="申请时间">
                  {{ formatTime(currentRow.applyTime) }}
                </el-descriptions-item>
                <el-descriptions-item label="当前状态">
                  <el-tag :type="getStatusType(currentRow.withdraw_status)">
                    {{ getStatusText(currentRow.withdraw_status) }}
                  </el-tag>
                </el-descriptions-item>
                <el-descriptions-item label="VIP状态">
                  {{ currentRow.isVip ? '是VIP' : '非VIP' }}
                </el-descriptions-item>
<!--                <el-descriptions-item label="默认墙主">-->
<!--                  {{ currentRow.isDefaultWallMaster ? '是' : '否' }}-->
<!--                </el-descriptions-item>-->
              </el-descriptions>
            </div>
          </div>
        </el-card>

        <!-- 提现历史 -->
        <div class="reason-section">
          <el-tabs v-model="activeName" class="demo-tabs" @tab-click="handleTabClick">
            <el-tab-pane label="线上提现到微信零钱" name="Wallet"></el-tab-pane>
            <el-tab-pane label="线下提现转账" name="transfer"></el-tab-pane>
          </el-tabs>
          <el-table :data="historyTableData" v-loading="loading" stripe style="width: 100%">
            <el-table-column type="index" label="#" width="60" />
            <el-table-column label="提现金额" width="90">
              <template #default="{ row }">
                ¥{{ row.cash_amount || '0.00' }}
              </template>
            </el-table-column>
            <el-table-column label="提现状态" width="120">
              <template #default="{ row }">
                <el-tag :type="getStatusType(row.withdraw_status)">
                  {{ getStatusText(row.withdraw_status) }}
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
            <template v-if="activeName === 'transfer'">
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
              <el-table-column label="操作" width="100" v-if="activeName === 'transfer'">
                <template #default="{ row }">
                  <div class="action-buttons">
                    <el-button size="small" type="primary" @click="handleVoucher(row)">上传打款凭证</el-button>
                  </div>
                </template>
              </el-table-column>
            </template>
          </el-table>
        </div>
      </div>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="detailVisible = false">关闭</el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 审核对话框 -->
    <el-dialog v-model="reviewVisible" :title="reviewAction === 'approve' ? '通过申请' : '驳回申请'" width="500px"
      destroy-on-close>

      <el-form :model="reviewForm" label-width="80px">
        <el-form-item label="审核意见">
          <el-input v-model="reviewForm.reject_remarks" type="textarea" :rows="4" placeholder="请输入审核意见..." />
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

    <!-- 上传打款凭证对话框 -->
    <el-dialog v-model="uploadVisible" title="上传打款凭证" width="500px" destroy-on-close>
      <el-form :model="uploadForm" label-width="100px">
        <el-form-item label="提现信息">
          <div class="withdraw-info">
            <p><strong>申请人：</strong>{{ uploadForm.nickname || '未知用户' }}</p>
            <p><strong>提现金额：</strong>¥{{ uploadForm.amount || '0.00' }}</p>
            <p><strong>提现时间：</strong>{{ formatTime(uploadForm.create_time) }}</p>
          </div>
        </el-form-item>

        <el-form-item label="打款凭证" required>
          <el-upload ref="uploadRef" class="voucher-uploader" list-type="picture-card" :action="uploadUrl" :data="{
            withdrawal_id: uploadForm.withdrawal_id,
            withdraw_status: uploadForm.withdraw_status,
          }" :headers="uploadHeaders" :show-file-list="false" :before-upload="beforeUpload"
            :on-success="handleUploadSuccess" :on-error="handleUploadError" :loading="uploadLoading" accept="image/*">
            <div v-if="uploadForm.withdrawal_evidence" class="uploaded-image">
              <el-image :src="uploadForm.withdrawal_evidence" fit="cover"
                style="width: 150px; height: 150px; cursor: pointer;"
                @click="previewVoucherImage(uploadForm.withdrawal_evidence)" :preview-disabled="true" />
              <div class="image-overlay">
                <el-button type="primary" size="small" @click.stop="reUpload">重新上传</el-button>
                <el-button type="info" size="small"
                  @click.stop="previewVoucherImage(uploadForm.withdrawal_evidence)">预览</el-button>
              </div>
            </div>
            <div v-else class="upload-placeholder">
              <el-icon class="upload-icon" v-loading="uploadLoading">
                <Plus />
              </el-icon>
              <div class="upload-text">点击上传打款凭证</div>
              <div class="upload-tip">支持 jpg/png 格式，大小不超过 2MB</div>
            </div>
          </el-upload>
        </el-form-item>

        <el-form-item label="备注">
          <el-input v-model="uploadForm.remark" type="textarea" :rows="3" placeholder="可填写备注信息（选填）" maxlength="200"
            show-word-limit />
        </el-form-item>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button @click="uploadVisible = false">取消</el-button>
          <el-button type="primary" @click="submitVoucher" :loading="submitLoading"
            :disabled="!uploadForm.withdrawal_evidence">
            确认提交
          </el-button>
        </div>
      </template>
    </el-dialog>

    <!-- 图片预览器 -->
    <el-image-viewer v-if="imageViewerVisible" :url-list="imageList" :initial-index="imageIndex"
      @close="closeImageViewer" :hide-on-click-modal="true" :teleported="true" />
  </div>
</template>

<script setup>
  import { ref, reactive, onMounted, computed } from 'vue';
  import { ElMessage, ElMessageBox, ElImageViewer } from 'element-plus';
  import { Filter, List, Clock, Star, User, Plus, ZoomIn } from '@element-plus/icons-vue';
  import { API } from '../api';
  import { useAuthStore } from '../store/auth';

  const authStore = useAuthStore();

  const loading = ref(false);
  const detailVisible = ref(false);
  const reviewVisible = ref(false);
  const reviewLoading = ref(false);
  const uploadVisible = ref(false);
  const uploadLoading = ref(false);
  const submitLoading = ref(false);
  const currentRow = ref(null);
  const reviewAction = ref('');
  const uploadRef = ref(null);
  const fileList = ref([]);

  // 图片预览相关
  const imageViewerVisible = ref(false);
  const imageList = ref([]);
  const imageIndex = ref(0);

  const activeName = ref('Wallet');

  const searchForm = reactive({
    withdraw_status: 'all',
    withdrawal_method: '',
  });

  const reviewForm = reactive({
    reject_remarks: ''
  });

  const uploadForm = reactive({
    withdrawal_id: null,
    withdraw_status: '',
    nickname: '',
    amount: '',
    create_time: '',
    withdrawal_evidence: '',
    remark: ''
  });

  const pagination = reactive({
    page: 1,
    size: 20,
    total: 0
  });

  const tableData = ref([]);
  const historyTableData = ref([]);

  // 上传配置
  const uploadUrl = computed(() => {
    const baseApi = import.meta.env.VITE_BASE_API || 'http://localhost:3000/api';
    return `${baseApi}/admin/withdraw/review`;
  });

  const uploadHeaders = computed(() => {
    const headers = {};
    if (authStore.token) {
      headers.Authorization = `Bearer ${authStore.token}`;
      headers.token = authStore.token;
    }
    return headers;
  });

  // 状态映射 - 按原组件的数字状态
  const statusMap = {
    0: { text: '待审核', type: 'warning' },
    2: { text: '待打款', type: 'info' },
    3: { text: '已驳回', type: 'danger' },
    5: { text: '已完成', type: 'success' },
    10: { text: '已取消', type: 'success' },
  };

  function getStatusText(withdraw_status) {
    return statusMap[withdraw_status]?.text || '未知';
  }

  function getStatusType(withdraw_status) {
    return statusMap[withdraw_status]?.type || 'info';
  }

  function formatTime(time) {
    if (!time) return '-';
    return new Date(time).toLocaleString();
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

  function handleStatusChange() {
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

      // 如果是'all'，不传 status参数
      if (params.withdraw_status === 'all') {
        delete params.withdraw_status;
      }

      const res = await API.Withdraw.getList(params);

      if (res.code === 200) {
        const data = res.data || {};
        tableData.value = data.list || [];
        pagination.total = data.total || 0;
      } else {
        ElMessage.error(res.message || '加载数据失败');
      }
    } catch (error) {
      console.error('加载申请列表失败:', error);
      ElMessage.error('加载数据失败，请检查网络连接');
    } finally {
      loading.value = false;
    }
  }

  function viewDetail(row) {
    currentRow.value = { ...row };
    detailVisible.value = true;
    getWithDrawHistory();
  }

  // 完善的上传打款凭证方法
  async function handleVoucher(row) {
    currentRow.value = row;
    // 初始化上传表单数据
    uploadForm.withdrawal_id = row.withdrawal_id || row.applyId;
    uploadForm.withdraw_status = 5;
    uploadForm.nickname = row.payee_name || '未知用户';
    uploadForm.amount = row.cash_amount || '0.00';
    uploadForm.create_time = row.create_time;
    uploadForm.withdrawal_evidence = row.withdrawal_evidence || '';
    uploadForm.remark = '';
    uploadVisible.value = true;
  }

  // 图片上传前的验证
  function beforeUpload(file) {
    const isImage = file.type.startsWith('image/');
    const isLt2M = file.size / 1024 / 1024 < 2;

    if (!isImage) {
      ElMessage.error('只能上传图片格式的文件!');
      return false;
    }
    if (!isLt2M) {
      ElMessage.error('图片大小不能超过 2MB!');
      return false;
    }

    uploadLoading.value = true;
    return true;
  }

  // 上传成功回调
  function handleUploadSuccess(response, file) {
    uploadLoading.value = false;

    if (response.code === 200 || response.success) {
      // 根据实际API响应结构调整
      const imageUrl = response.data?.url || response.data?.path || response.url;
      uploadForm.withdrawal_evidence = imageUrl;
      ElMessage.success('打款完成');
      loadData()
    } else {
      ElMessage.error(response.message || '图片上传失败');
    }
  }

  // 上传失败回调
  function handleUploadError(error) {
    uploadLoading.value = false;
    console.error('图片上传失败:', error);
    ElMessage.error('图片上传失败，请重试');
  }

  // 重新上传
  function reUpload() {
    uploadForm.withdrawal_evidence = '';
    // 触发文件选择
    if (uploadRef.value) {
      uploadRef.value.$el.querySelector('input[type="file"]').click();
    }
  }

  // 提交打款凭证
  async function submitVoucher() {
    if (!uploadForm.withdrawal_evidence) {
      ElMessage.warning('请先上传打款凭证');
      return;
    }

    try {
      submitLoading.value = true;

      const data = {
        id: uploadForm.id,
        withdrawal_evidence: uploadForm.withdrawal_evidence,
        remark: uploadForm.remark,
        cash_time: new Date().toISOString()
      };

      // 这里需要根据实际API调整
      const res = await API.Withdraw.uploadVoucher(data);

      if (res.code === 200) {
        ElMessage.success('打款凭证上传成功');
        uploadVisible.value = false;

        // 刷新数据
        loadData();
        if (detailVisible.value) {
          getWithDrawHistory();
        }
      } else {
        ElMessage.error(res.message || '提交失败');
      }
    } catch (error) {
      console.error('提交打款凭证失败:', error);
      ElMessage.error('提交失败，请重试');
    } finally {
      submitLoading.value = false;
    }
  }

  function handleTabClick(tab) {
    activeName.value = tab.name;
    getWithDrawHistory();
  }

  async function getWithDrawHistory(params) {
    try {
      const queryParams = {
        type: activeName.value,
        userId: currentRow.value?.userId,
        ...params
      };

      const res = await API.Withdraw.historyList(queryParams);
      if (res.code === 200) {
        const data = res.data || {};
        historyTableData.value = data.list || [];
      } else {
        ElMessage.error(res.message || '加载数据失败');
      }
    } catch (error) {
      console.error('加载提现历史失败:', error);
      ElMessage.error('加载提现历史失败');
    }
  }

  function approveApply(apply) {
    currentRow.value = apply;
    reviewAction.value = 'approve';
    reviewForm.reject_remarks = '';
    reviewVisible.value = true;
    detailVisible.value = false;
  }

  function rejectApply(apply) {
    currentRow.value = apply;
    reviewAction.value = 'reject';
    reviewForm.reject_remarks = '';
    reviewVisible.value = true;
    detailVisible.value = false;
  }

  async function submitReview() {
    try {
      reviewLoading.value = true;
      let withdraw_status ='';
      const applyId = currentRow.value.withdrawal_id || currentRow.value.id;
      if(currentRow.value.withdrawal_method === 'wechat'){
        withdraw_status = reviewAction.value === 'approve' ? 5 : 3;
      }
      if(currentRow.value.withdrawal_method === 'offline'){
        withdraw_status = reviewAction.value === 'approve' ? 2 : 3;
      }
      let params = {
        withdrawal_id: applyId,
        withdraw_status: withdraw_status,
      }

      if (withdraw_status===3 && !reviewForm.reject_remarks) {
        ElMessage.warning('请填写驳回理由');
        return;
      }
      params.reject_remarks = reviewForm.reject_remarks

      const res = await API.Withdraw.review(params);

      if (res.code === 200) {
        ElMessage.success(`${reviewAction.value === 'approve' ? '通过' : '驳回'}成功`);
        reviewVisible.value = false;
        loadData();
      } else {
        ElMessage.error(res.msg || '操作失败');
      }
    } catch (error) {
      console.error('审核失败:', error);
      ElMessage.error('操作失败');
    } finally {
      reviewLoading.value = false;
    }
  }

  onMounted(() => {
    loadData();
  });
</script>

<style scoped>
  .wall-master-manage {
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

  .user-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .user-avatar {
    cursor: pointer;
  }

  .user-name {
    font-weight: 500;
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

  .tag-icon {
    margin-right: 4px;
  }

  .text-muted {
    color: #909399;
    font-size: 12px;
  }

  .action-buttons {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .flex {
    display: flex;
  }

  .items-center {
    align-items: center;
  }

  .font-semibold {
    font-weight: 600;
  }

  .text-sm {
    font-size: 0.875rem;
  }

  .text-gray-600 {
    color: #4b5563;
  }

  .mr-2 {
    margin-right: 0.5rem;
  }

  .pagination-wrapper {
    display: flex;
    justify-content: flex-end;
    margin-top: 20px;
  }

  .detail-content {
    padding: 0;
  }

  .detail-card {
    margin-bottom: 20px;
  }

  .detail-user-section {
    display: flex;
    gap: 20px;
  }

  .detail-avatar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    min-width: 120px;
  }

  .detail-avatar-large {
    cursor: pointer;
  }

  .detail-avatar-margin {
    margin-bottom: 12px;
  }

  .detail-user-name {
    font-size: 18px;
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
  }

  .detail-tag-margin {
    margin-bottom: 4px;
  }

  .icon-margin-right {
    margin-right: 4px;
  }

  .detail-descriptions {
    flex: 1;
  }

  .text-ellipsis {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 200px;
    display: inline-block;
  }

  .reason-section {
    margin-top: 20px;
  }

  .reason-section h4 {
    margin-bottom: 12px;
    color: #495057;
    font-size: 14px;
    font-weight: 600;
  }

  .reason-content {
    line-height: 1.6;
    padding: 12px;
    background-color: #f8f9fa;
    border-radius: 6px;
    word-break: break-word;
    min-height: 60px;
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

  /* 上传组件样式 */
  .withdraw-info {
    background-color: #f8f9fa;
    padding: 16px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }

  .withdraw-info p {
    margin: 0 0 8px 0;
    color: #495057;
  }

  .withdraw-info p:last-child {
    margin-bottom: 0;
  }

  .voucher-uploader {
    width: 100%;
  }

  .voucher-uploader :deep(.el-upload) {
    border: 1px dashed #d9d9d9;
    border-radius: 6px;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    transition: all 0.3s;
    width: 150px;
    height: 150px;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .voucher-uploader :deep(.el-upload:hover) {
    border-color: #409eff;
  }

  .upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    text-align: center;
  }

  .upload-icon {
    font-size: 28px;
    color: #8c939d;
    margin-bottom: 8px;
  }

  .upload-text {
    color: #606266;
    font-size: 14px;
    margin-bottom: 4px;
  }

  .upload-tip {
    color: #909399;
    font-size: 12px;
    line-height: 1.4;
  }

  .uploaded-image {
    position: relative;
    width: 150px;
    height: 150px;
  }

  .uploaded-image:hover .image-overlay {
    opacity: 1;
  }

  .image-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s;
    gap: 8px;
  }
</style>
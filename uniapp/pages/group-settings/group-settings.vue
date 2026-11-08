<template>
  <view class="group-settings-page">
    <!-- 导航栏 -->
    <uv-navbar
      title="群聊设置"
      bgColor="#f0f6fb"
      :border="false"
      :titleStyle="{ color: '#333333', fontSize: '18px', fontWeight: '500' }"
      leftIcon="arrow-left"
      leftIconColor="#333333"
      :safeAreaInsetTop="true"
      :fixed="true"
      :placeholder="true"
      :height="44"
      @leftClick="goBack"
    />

    <!-- 群聊列表 -->
    <view class="group-list" v-if="groupList.length > 0">
      <view
        v-for="group in groupList"
        :key="group.id"
        class="group-item"
        @click="editGroup(group)"
      >
        <view class="group-info">
          <image
            v-if="group.group_avatar"
            :src="group.group_avatar"
            class="group-avatar"
            mode="aspectFill"
          />
          <view v-else class="group-avatar-placeholder">
            <text class="avatar-text">{{ group.group_name.charAt(0) }}</text>
          </view>
          <view class="group-details">
            <text class="group-name">{{ group.group_name }}</text>
            <text class="group-desc">{{
              group.group_description || "暂无简介"
            }}</text>
          </view>
        </view>
        <view class="group-actions">
          <view
            class="action-icon qrcode-icon"
            @click.stop="previewQrcode(group)"
          >
            <image src="../../static/images/qrcode.svg" mode="widthFix" />
          </view>
          <view
            class="action-icon delete-icon"
            @click.stop="deleteGroup(group)"
          >
            <uv-icon name="trash" size="20" color="#999"></uv-icon>
          </view>
        </view>
      </view>
    </view>
    <!-- 空状态 -->
    <view v-else class="empty-state">
      <text class="empty-text">暂无群聊设置</text>
      <text class="empty-desc">点击按钮创建新的群聊</text>
    </view>
    <!-- 新建群聊按钮 -->
    <view class="create-group-btn" @click="showCreateModal">
      <text class="btn-text">新建群聊</text>
    </view>

    <!-- 创建/编辑群聊弹窗 - 使用uv-popup -->
    <uv-popup ref="groupPopup" @change="onPopupChange">
      <view class="popup-content">
        <!-- 弹窗标题 -->
        <view class="popup-header">
          <view></view>
          <text class="popup-title">{{
            isEdit ? "编辑群聊" : "新建群聊"
          }}</text>
          <view class="close-btn" @click="closeModal">
            <text class="close-text">×</text>
          </view>
        </view>

        <!-- 表单内容 -->
        <view class="form-content">
          <!-- 群聊名称 -->
          <view class="setting-item">
            <view class="setting-item-left">
              <view class="setting-item-title">群聊名称</view>
            </view>
            <view class="setting-item-right">
              <input
                v-model="formData.group_name"
                placeholder="请输入群聊名称"
                class="setting-input-inline"
                maxlength="50"
              />
            </view>
          </view>

          <!-- 群聊简介 -->
          <view class="setting-item">
            <view class="setting-item-left">
              <view class="setting-item-title">群聊简介</view>
            </view>
            <view class="setting-item-right">
              <input
                v-model="formData.group_description"
                placeholder="请输入群聊简介"
                class="setting-input-inline"
                maxlength="200"
              />
            </view>
          </view>

          <!-- 权重 -->
          <view class="setting-item">
            <view class="setting-item-left">
              <view class="setting-item-title">权重</view>
              <view class="setting-item-desc"></view>
            </view>
            <view class="setting-item-right">
              <input
                v-model.number="formData.weight"
                type="number"
                placeholder="数值越大排序越靠前"
                class="setting-input-inline"
              />
            </view>
          </view>

          <!-- 群聊头像 -->
          <view class="setting-item">
            <view class="setting-item-left">
              <view class="setting-item-title">群聊头像</view>
              <view class="setting-item-desc">建议使用正方形图片</view>
            </view>
            <view class="setting-item-right">
              <view class="upload-image" @click="uploadAvatar">
                <uv-icon
                  v-if="!formData.group_avatar"
                  name="camera"
                  size="24"
                  color="#999999"
                ></uv-icon>
                <image
                  v-else
                  :src="formData.group_avatar"
                  class="preview-image"
                  mode="aspectFill"
                />
              </view>
            </view>
          </view>

          <!-- 群聊二维码 -->
          <view class="setting-item">
            <view class="setting-item-left">
              <view class="setting-item-title">群聊二维码</view>
              <view class="setting-item-desc">用户可扫码加入群聊</view>
            </view>
            <view class="setting-item-right">
              <view class="upload-image" @click="uploadQrcode">
                <uv-icon
                  v-if="!formData.group_qrcode"
                  name="camera"
                  size="24"
                  color="#999999"
                ></uv-icon>
                <image
                  v-else
                  :src="formData.group_qrcode"
                  class="preview-image"
                  mode="aspectFill"
                />
              </view>
            </view>
          </view>

          <!-- 操作按钮 -->
          <view class="modal-actions">
            <view class="submit-btn" @click="submitForm">
              <text class="submit-text">{{
                isEdit ? "确认更新" : "确认创建"
              }}</text>
            </view>
          </view>
        </view>
      </view>
    </uv-popup>
  </view>
</template>

<script>
import api from "../../api";

export default {
  data() {
    return {
      groupList: [],
      isEdit: false,
      formData: {
        id: null,
        group_name: "",
        group_description: "",
        group_avatar: "",
        group_qrcode: "",
        weight: 0,
      },
      wallOwnerId: "",
    };
  },

  onLoad(options) {
    // 从缓存中获取墙主ID，而不是从参数中获取
    this.wallOwnerId = options.wallOwnerId;
    this.loadGroupList();
  },

  methods: {
    // 加载群聊列表
    async loadGroupList() {
      try {
        const res = await api.group.getGroupList({
          wallOwnerId: this.wallOwnerId,
        });
        if (res.code === 200) {
          this.groupList = res.data || [];
        } else {
          uni.showToast({
            title: res.message || "加载失败",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("加载群聊列表失败:", error);
        uni.showToast({
          title: "网络错误",
          icon: "none",
        });
      }
    },

    // 显示创建弹窗
    showCreateModal() {
      console.log("点击新建群聊按钮");
      this.isEdit = false;
      this.resetForm();
      this.$refs.groupPopup.open("bottom");
    },

    // 编辑群聊
    editGroup(group) {
      this.isEdit = true;
      this.formData = { ...group };
      this.$refs.groupPopup.open("bottom");
    },

    // 预览二维码
    previewQrcode(group) {
      if (group.group_qrcode) {
        uni.previewImage({
          urls: [group.group_qrcode],
          current: group.group_qrcode,
        });
      } else {
        uni.showToast({
          title: "该群聊暂未设置二维码",
          icon: "none",
        });
      }
    },

    // 删除群聊
    deleteGroup(group) {
      uni.showModal({
        title: "确认删除",
        content: `确定要删除群聊"${group.group_name}"吗？`,
        success: async (res) => {
          if (res.confirm) {
            try {
              uni.showLoading({ title: "删除中..." });

              const response = await api.group.deleteGroup({
                id: group.id,
                wallOwnerId: this.wallOwnerId,
              });

              if (response.code === 200) {
                uni.showToast({
                  title: "删除成功",
                  icon: "success",
                });
                this.loadGroupList();
              } else {
                uni.showToast({
                  title: response.message || "删除失败",
                  icon: "none",
                });
              }
            } catch (error) {
              console.error("删除群聊失败:", error);
              uni.showToast({
                title: "删除失败",
                icon: "none",
              });
            } finally {
              uni.hideLoading();
            }
          }
        },
      });
    },

    // 弹窗状态变化
    onPopupChange(e) {
      console.log("弹窗状态改变：", e);
      if (!e.show) {
        this.resetForm();
      }
    },

    // 关闭弹窗
    closeModal() {
      this.$refs.groupPopup.close();
    },

    // 重置表单
    resetForm() {
      this.formData = {
        id: null,
        group_name: "",
        group_description: "",
        group_avatar: "",
        group_qrcode: "",
        weight: 0,
      };
    },

    // 上传头像
    uploadAvatar() {
      uni.chooseImage({
        count: 1,
        success: async (res) => {
          try {
            uni.showLoading({ title: "上传中..." });
            const uploadRes = await api.upload.uploadImage(
              res.tempFilePaths[0]
            );
            if (uploadRes.code === 200) {
              this.formData.group_avatar = uploadRes.data.url;
              uni.showToast({
                title: "上传成功",
                icon: "success",
              });
            }
          } catch (error) {
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
          } finally {
            uni.hideLoading();
          }
        },
      });
    },

    // 上传二维码
    uploadQrcode() {
      uni.chooseImage({
        count: 1,
        success: async (res) => {
          try {
            uni.showLoading({ title: "上传中..." });
            const uploadRes = await api.upload.uploadImage(
              res.tempFilePaths[0]
            );
            if (uploadRes.code === 200) {
              this.formData.group_qrcode = uploadRes.data.url;
              uni.showToast({
                title: "上传成功",
                icon: "success",
              });
            }
          } catch (error) {
            uni.showToast({
              title: "上传失败",
              icon: "none",
            });
          } finally {
            uni.hideLoading();
          }
        },
      });
    },

    // 提交表单
    async submitForm() {
      if (!this.validateForm()) {
        return;
      }

      try {
        uni.showLoading({ title: this.isEdit ? "更新中..." : "创建中..." });

        const data = {
          ...this.formData,
          wallOwnerId: this.wallOwnerId,
        };

        let res;
        if (this.isEdit) {
          res = await api.group.updateGroup(data);
        } else {
          res = await api.group.createGroup(data);
        }

        if (res.code === 200) {
          uni.showToast({
            title: this.isEdit ? "更新成功" : "创建成功",
            icon: "success",
          });
          this.$refs.groupPopup.close();
          this.loadGroupList();
        } else {
          uni.showToast({
            title: res.message || "操作失败",
            icon: "none",
          });
        }
      } catch (error) {
        console.error("提交失败:", error);
        uni.showToast({
          title: "操作失败",
          icon: "none",
        });
      } finally {
        uni.hideLoading();
      }
    },

    // 表单验证
    validateForm() {
      if (!this.formData.group_name.trim()) {
        uni.showToast({
          title: "请输入群聊名称",
          icon: "none",
        });
        return false;
      }

      return true;
    },

    // 返回上一页
    goBack() {
      uni.navigateBack();
    },
  },
};
</script>

<style scoped>
.group-settings-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

/* 新建群聊按钮 */
.create-group-btn {
  margin: 20px;
  background: #2979ff;
  border-radius: 16px;
  padding: 18px;
  text-align: center;
  box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
  transition: all 0.3s ease;
}

.create-group-btn:active {
  transform: translateY(2px);
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-text {
  color: white;
  font-size: 16px;
  font-weight: 600;
  letter-spacing: 0.5px;
}

/* 群聊列表 */
.group-list {
  padding: 0 20px;
}

.group-item {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(10px);
  border-radius: 16px;
  padding: 20px;
  margin-bottom: 16px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  transition: all 0.3s ease;
}

.group-item:active {
  transform: translateY(2px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.group-info {
  display: flex;
  align-items: center;
  flex: 1;
}

.group-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  margin-right: 16px;
  object-fit: cover;
  border: 3px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.group-avatar-placeholder {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  margin-right: 16px;
  border: 3px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.avatar-text {
  color: white;
  font-size: 20px;
  font-weight: bold;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.group-details {
  flex: 1;
}

.group-name {
  display: block;
  font-size: 16px;
  font-weight: 500;
  color: #333;
  margin-bottom: 4px;
}

.group-desc {
  font-size: 14px;
  color: #666;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.group-actions {
  display: flex;
  gap: 8px;
}

.action-icon {
  padding: 8px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
}

.qrcode-icon:active {
  background-color: #e0e0e0;
}
.qrcode-icon {
  width: 20px;
}

.delete-icon:active {
  background-color: #ffebee;
}

/* 空状态 */
.empty-state {
  text-align: center;
  padding: 80px 20px;
}

.empty-text {
  display: block;
  font-size: 16px;
  color: #999;
  margin-bottom: 8px;
}

.empty-desc {
  font-size: 14px;
  color: #ccc;
}

/* 弹窗样式 */
.popup-content {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
  border-radius: 24px 24px 0 0;
  max-height: 80vh;
  overflow: hidden;
  box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.1);
}

.popup-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 24px 20px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  background: rgba(255, 255, 255, 0.8);
  backdrop-filter: blur(10px);
}

.popup-title {
  font-size: 20px;
  font-weight: 600;
  color: #333;
  letter-spacing: 0.3px;
}

.close-btn {
  width: 36px;
  height: 36px;
  border-radius: 18px;
  background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
}

.close-btn:active {
  transform: scale(0.95);
  background: linear-gradient(135deg, #e8e8e8 0%, #d8d8d8 100%);
}

.close-text {
  font-size: 22px;
  color: #666;
  font-weight: 300;
}

/* 表单样式 */
.form-content {
  max-height: 50vh;
  overflow-y: auto;
  width: 96%;
  margin: 0 auto;
}

/* 设置项样式 - 参照参考图片 */
.setting-item {
  display: flex;
  background-color: #ffffff;
  padding: 2px 10px;
  margin-bottom: 10px;
  position: relative;
  align-items: center;
  border: 1px solid #eee;
}

.setting-item:after {
  content: "";
  position: absolute;
  left: 15px;
  right: 15px;
  bottom: 0;
  height: 1px;
  background-color: #f5f5f5;
}

.setting-item:last-child:after {
  display: none;
}

.setting-item-left {
  flex: 0 0 auto;
  min-width: 80px;
}

.setting-item-right {
  flex: 1;
  display: flex;
  justify-content: flex-end;
  align-items: center;
}

.setting-item-title {
  font-size: 15px;
  color: #333;
  margin-bottom: 5px;
}

.setting-item-desc {
  font-size: 12px;
  color: #999;
}

.setting-input {
  font-size: 14px;
  color: #666;
  width: 100%;
  height: 36px;
  padding: 0 5px;
  background-color: transparent;
  border: none;
}

.setting-textarea {
  font-size: 14px;
  color: #666;
  width: 100%;
  min-height: 60px;
  padding: 5px;
  border-bottom: 1px solid #eee;
  background-color: transparent;
  border: none;
  border-bottom: 1px solid #eee;
  resize: none;
}

.setting-input-inline {
  font-size: 14px;
  color: #666;
  width: 100%;
  height: 36px;
  padding: 0 5px;
  text-align: left;
  background-color: transparent;
  border: none;
}

.setting-textarea-inline {
  font-size: 14px;
  color: #666;
  width: 100%;
  min-height: 60px;
  padding: 5px;
  text-align: right;
  background-color: transparent;
  border: none;
  resize: none;
}

.weight-input {
  width: 60px;
  height: 32px;
  text-align: center;
  border: 1px solid #e0e0e0;
  border-radius: 4px;
  font-size: 14px;
  background-color: #fafafa;
}

/* 上传区域 - 优化样式 */
.upload-image {
  width: 50px;
  height: 50px;
  background: linear-gradient(135deg, #f8f8f8 0%, #f0f0f0 100%);
  border-radius: 12px;
  display: flex;
  justify-content: center;
  align-items: center;
  overflow: hidden;
  border: 2px solid rgba(255, 255, 255, 0.8);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  transition: all 0.3s ease;
}

.upload-image:active {
  background: linear-gradient(135deg, #e8e8e8 0%, #d8d8d8 100%);
  transform: scale(0.95);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
}

.preview-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 10px;
}

/* 模态框操作按钮 */
.modal-actions {
  padding: 20px;
  border-top: 1px solid #f0f0f0;
  background-color: #fafafa;
}

.submit-btn {
  background-color: #2979ff;
  border-radius: 12px;
  padding: 16px;
  text-align: center;
}

.submit-text {
  color: white;
  font-size: 16px;
  font-weight: 500;
}
</style>

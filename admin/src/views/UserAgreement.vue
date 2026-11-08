<template>
  <div class="tutorial-manage">
    <el-card shadow="never" style="position: relative;">
      <el-tabs v-model="activeName" class="demo-tabs" @tab-change="handleStatusChange">
        <el-tab-pane label="普通用户" name="1"></el-tab-pane>
        <el-tab-pane label="墙主" name="2"></el-tab-pane>
      </el-tabs>
      <el-button type="primary" size="small" class="btn-save" @click="handleSave" :loading="saving">
        <el-icon>
          <DocumentChecked />
        </el-icon>保存
      </el-button>
      <Toolbar :editor="editorRef" :default-config="toolbarConfig" style="border-bottom:1px solid #dcdfe6;" />
      <Editor v-model="html" :default-config="editorConfig" :mode="'default'" @onCreated="handleCreated"
        style="height: 550px; border: 1px solid #dcdfe6; border-top:none;" />
    </el-card>
  </div>
</template>

<script setup>
  import { ref, onMounted } from 'vue'
  import { ElMessage } from 'element-plus'
  import { EditPen, DocumentChecked } from '@element-plus/icons-vue'
  import { Editor, Toolbar } from '@wangeditor/editor-for-vue'
  import { API } from '@/api'

  const html = ref('')
  const saving = ref(false)
  const editorRef = ref(null)
  const editorConfig = {
    placeholder: '请输入使用教程...',
    MENU_CONF: {
      uploadImage: {
        server: `${import.meta.env.VITE_BASE_API || '/qiang/public/index.php/api'}/upload/image`,
        // 根据已有上传接口调整
      }
    }
  }
  const toolbarConfig = {}

  // tabs切换
  const activeName = ref('1')

  onMounted(async () => {
    getAgreement(activeName.value)
  })

  function handleStatusChange(value) {
    getAgreement(value)
  }

  function handleCreated(editor) {
    editorRef.value = editor
  }

  async function getAgreement(value) {
    const res = await API.agreement.get({ id: value })
    if (res.code === 200 && res.data) {
      html.value = res.data.content || ''
    }
  }

  const handleSave = async () => {
    if (saving.value) return
    saving.value = true
    try {
      const res = await API.agreement.save({ id: activeName.value, content: html.value })
      if (res.code === 200) {
        ElMessage.success('保存成功')
      } else {
        ElMessage.error(res.message || '保存失败')
      }
    } catch (e) {
      console.error(e)
    } finally {
      saving.value = false
    }
  }
</script>

<style scoped>
  .tutorial-manage {
    padding: 20px;
  }

  .btn-save {
    position: absolute;
    right: 20px;
    top: 30px;
  }
</style>
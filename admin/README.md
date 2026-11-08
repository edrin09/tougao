# 校园墙后台管理系统 v2.0

基于 Vue 3 + Vite + Element Plus 的现代化管理后台，无 CDN 依赖，支持离线使用。

## 🚀 技术栈

- **Vue 3** - 渐进式 JavaScript 框架
- **Vite** - 下一代前端构建工具
- **Element Plus** - Vue 3 组件库
- **Vue Router** - 官方路由管理器
- **Pinia** - Vue 状态管理
- **Axios** - HTTP 客户端

## 📁 项目结构

```
src/
├── api/              # API 接口封装
│   └── index.js      # 统一 API 配置
├── layouts/          # 布局组件
│   ├── AdminLayout.vue    # 后台主布局
│   └── BlankLayout.vue    # 空白布局(登录页)
├── router/           # 路由配置
│   └── index.js      # 路由定义
├── store/            # 状态管理
│   └── auth.js       # 认证状态
├── views/            # 页面组件
│   ├── Login.vue          # 登录页面
│   ├── Dashboard.vue      # 仪表盘
│   ├── WallMasterApply.vue   # 墙主申请管理
│   ├── PostManage.vue        # 投稿管理
│   ├── CommentManage.vue     # 评论管理
│   └── NotificationManager.vue # 通知管理
├── App.vue           # 根组件
└── main.js           # 应用入口
```

## 🛠️ 开发指南

### 环境准备

node版本：20.0.0

```bash
# 安装依赖
yarn install

# 启动开发服务器
yarn dev

# 构建生产版本
yarn build
```

### 路由架构

采用嵌套路由设计：
- `BlankLayout` - 认证页面布局（登录）
- `AdminLayout` - 业务页面布局（侧边栏+顶栏）

### API 配置

API 基础地址: `/qiang/public/index.php/api`

自动功能：
- Token 自动注入请求头
- 401/403 自动跳转登录
- 统一错误处理

### 状态管理

使用 Pinia 管理：
- `authStore` - 用户认证状态
- 支持 localStorage 持久化

## 🎯 功能模块

### 1. 认证系统
- [x] 用户登录/登出
- [x] Token 管理
- [x] 路由守卫

### 2. 仪表盘
- [x] 数据统计卡片
- [x] 快速操作入口
- [x] 系统信息展示
- [x] 活动时间线

### 3. 墙主申请管理
- [x] 申请列表查看
- [x] 状态筛选搜索
- [x] 审核通过/拒绝
- [x] 申请详情查看
- [x] 分页支持

### 4. 投稿管理
- [x] 投稿列表
- [x] 内容审核
- [x] 状态管理

### 5. 评论管理
- [x] 评论列表
- [x] 内容审核
- [x] 批量操作

### 6. 通知管理
- [x] 通知创建/编辑
- [x] 草稿/发布状态
- [x] 通知类型分类
- [x] 富文本内容支持

## 🎨 UI 设计

### 设计原则
- **现代化** - 采用渐变色彩和卡片设计
- **一致性** - 统一的颜色体系和间距规范
- **响应式** - 适配不同屏幕尺寸
- **易用性** - 清晰的信息层级和操作流程

### 主题色彩
- 主色调：`#667eea` 到 `#764ba2` 渐变
- 成功色：`#67C23A`
- 警告色：`#E6A23C`
- 危险色：`#F56C6C`

## 🔧 配置说明

### Vite 配置
```javascript
// vite.config.js
export default {
  server: {
    port: 3000,
    proxy: {
      '/qiang': 'http://localhost:8080' // 代理到后端
    }
  }
}
```

### 环境变量
```bash
# 开发环境
VITE_API_BASE_URL=/qiang/public/index.php/api

# 生产环境
VITE_API_BASE_URL=https://your-domain.com/qiang/public/index.php/api
```

## 📦 部署指南

### 1. 构建项目
```bash
yarn build
```

### 2. 部署到服务器
将 `dist` 目录上传到服务器的 `public/admin` 目录

### 3. 服务器配置
确保服务器支持 SPA 路由回退到 `index.html`

## 🔍 API 接口说明

### 认证相关
```javascript
POST /admin/login          // 管理员登录
POST /admin/logout         // 退出登录
GET  /admin/profile        // 获取用户信息
```

### 数据管理
```javascript
GET  /admin/dashboardStats           // 仪表盘统计
GET  /admin/wallMasterApplyList      // 墙主申请列表
POST /admin/reviewWallMasterApply    // 审核墙主申请
GET  /admin/post/list                // 投稿列表
GET  /admin/comment/list             // 评论列表
GET  /admin/notification/list        // 通知列表
```

## 📝 开发注意事项

1. **组件命名** - 使用 PascalCase
2. **API 调用** - 统一使用 try-catch 处理异常
3. **状态管理** - 复杂状态使用 Pinia，简单状态使用 ref/reactive
4. **样式写法** - 优先使用 scoped 样式，深度选择器使用 :deep()
5. **类型安全** - 注意 API 响应数据的空值判断

## 🆘 常见问题

### Q: 如何修改 API 基础地址？
A: 修改 `src/api/index.js` 中的 `baseURL` 配置

### Q: 如何添加新的页面？
A: 1. 在 `src/views/` 创建组件 2. 在 `src/router/index.js` 添加路由

### Q: 如何自定义主题？
A: 修改 Element Plus 的 CSS 变量或使用 scss 变量覆盖

## 📄 更新日志

### v2.0.0 (2024-01-15)
- 🎉 项目重构，采用 Vue 3 + Vite
- ✨ 新增嵌套路由架构
- 🎨 全新 UI 设计
- 🚀 性能优化，无 CDN 依赖
- 📱 响应式布局支持

---

**开发团队** | 如需技术支持，请联系项目维护者 
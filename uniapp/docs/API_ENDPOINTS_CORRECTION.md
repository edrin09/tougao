# API 接口地址修正说明

## 📋 问题说明

管理后台应该使用带有 `admin` 前缀的API地址，而不是直接调用用户API接口。

## 🔧 修正内容

### **1. API接口地址对比**

| 功能 | ❌ 错误地址 | ✅ 正确地址 |
|------|------------|------------|
| 获取默认墙主 | `/api/user/getDefaultWallMaster` | `/api/admin/getDefaultWallMaster` |
| 设置默认墙主 | `/api/user/setDefaultWallMaster` | `/api/admin/setDefaultWallMaster` |

### **2. 使用场景区分**

#### **小程序端API** (无admin前缀)
```javascript
// 小程序使用的API地址
GET  /api/user/getDefaultWallMaster  // 获取默认墙主
POST /api/user/setDefaultWallMaster  // 设置默认墙主（一般不开放给小程序）
```

#### **管理后台API** (带admin前缀)
```javascript
// 管理后台使用的API地址
GET  /api/admin/getDefaultWallMaster  // 获取默认墙主
POST /api/admin/setDefaultWallMaster  // 设置默认墙主
```

## 🗂️ 文件修正清单

### **1. 后端路由配置**
**文件**: `route/admin.php`
```php
// 新增管理后台路由
Route::get('getDefaultWallMaster', 'admin/Manager/getDefaultWallMaster');
Route::post('setDefaultWallMaster', 'admin/Manager/setDefaultWallMaster');
```

### **2. 后端控制器**
**文件**: `application/admin/controller/Manager.php`
```php
// 新增管理后台方法
public function setDefaultWallMaster(Request $request)
public function getDefaultWallMaster(Request $request)
```

### **3. 前端API配置**
**文件**: `public/admin/js/api.js`
```javascript
// 墙主申请管理API
wallMasterApply: {
    // ... 其他API
    getDefaultWallMaster: () => axios.get(`${API_BASE_URL}/admin/getDefaultWallMaster`),
    setDefaultWallMaster: (data) => axios.post(`${API_BASE_URL}/admin/setDefaultWallMaster`, data)
}
```

### **4. 前端组件调用**
**文件**: `public/admin/js/components/wall-master-apply.js`
```javascript
// 使用正确的API
const response = await API.wallMasterApply.setDefaultWallMaster({
    apply_id: currentApply.value.applyId || currentApply.value.id,
    user_id: currentApply.value.userId
});
```

## 🔒 权限控制差异

### **小程序API特点**
- ✅ 开放给小程序用户调用
- ✅ 获取默认墙主信息（只读）
- ❌ 一般不允许设置默认墙主
- 🔍 基于openid或用户身份验证

### **管理后台API特点**
- 🔐 需要管理员权限
- ✅ 获取默认墙主信息
- ✅ 设置默认墙主（管理功能）
- 📝 记录操作日志
- 🔍 基于管理员session验证

## 📊 API响应格式

### **获取默认墙主**
```json
{
  "code": 200,
  "message": "获取默认墙主成功",
  "data": {
    "apply_id": 123,
    "user_id": "user_xxx",
    "nickname": "墙主昵称",
    "avatar": "头像URL",
    "is_wall_master": 1,
    "is_default_wall_master": 1,
    "is_vip": 0,
    "status": 1,
    "create_time": "2025-01-27 16:00:00"
  }
}
```

### **设置默认墙主**
```json
{
  "code": 200,
  "message": "设置默认墙主成功",
  "data": {
    "apply_id": 123,
    "user_id": "user_xxx",
    "nickname": "墙主昵称",
    "is_default_wall_master": 1
  }
}
```

## 🧪 测试验证

### **1. 管理后台测试**
```bash
# 访问管理后台测试页面
http://your-domain/admin/test_default_wall_master.html

# 测试API地址
GET  http://your-domain/api/admin/getDefaultWallMaster
POST http://your-domain/api/admin/setDefaultWallMaster
```

### **2. 小程序API测试**
```bash
# 小程序API地址
GET  http://your-domain/api/user/getDefaultWallMaster
POST http://your-domain/api/user/setDefaultWallMaster
```

## 🎯 修正效果

### **修正前问题**
- ❌ 管理后台调用用户API
- ❌ 权限控制混乱
- ❌ 无法区分调用来源
- ❌ 缺少管理员操作日志

### **修正后优势**
- ✅ 清晰的API职责分离
- ✅ 严格的权限控制
- ✅ 完整的操作日志记录
- ✅ 便于后续功能扩展

## 📝 开发规范

### **API设计原则**
1. **职责分离**: 管理后台API与用户API分离
2. **权限控制**: 不同API有不同的权限要求
3. **日志记录**: 管理操作需要记录日志
4. **统一前缀**: 管理后台API统一使用 `/admin/` 前缀

### **命名规范**
```
用户API:     /api/user/[功能名称]
管理后台API: /api/admin/[功能名称]
```

### **调用规范**
```javascript
// 管理后台组件中
API.wallMasterApply.setDefaultWallMaster(data)  // ✅ 正确

// 小程序中
api.user.getDefaultWallMaster()  // ✅ 正确
```

---

**✅ API接口地址修正完成！**

现在管理后台使用正确的admin前缀API地址，权限控制更加清晰和安全。

# 默认墙主功能实现文档（申请表版本）

## 📋 功能概述

为校园墙系统添加默认墙主功能，解决小程序首页硬编码墙主ID的问题，改为通过API动态获取默认墙主。

**重要说明**：此版本在**墙主申请表**中添加默认墙主字段，而非用户表。

## 🎯 实现目标

1. **数据库层面**：墙主申请表添加 `is_default_wall_master` 字段
2. **后端API**：提供获取和设置默认墙主的接口
3. **小程序端**：首页改为API调用获取默认墙主
4. **管理后台**：提供设置默认墙主的界面

## 🗄️ 数据库变更

### 1. 新增字段（申请表）
```sql
ALTER TABLE `cw_wall_master_apply`
ADD COLUMN `is_default_wall_master` tinyint(1) NOT NULL DEFAULT 0
COMMENT '是否为默认墙主：0=否，1=是'
AFTER `is_vip`;
```

### 2. 添加索引
```sql
ALTER TABLE `cw_wall_master_apply`
ADD INDEX `idx_is_default_wall_master` (`is_default_wall_master`);
```

### 3. 数据迁移
- 自动将硬编码用户的申请设置为默认墙主（如果存在且已通过审核）
- 如果硬编码用户申请不存在，设置第一个通过审核的申请为默认墙主

## 🔧 后端实现

### 1. 模型更新
**文件**: `application/common/model/WallMasterApply.php`

```php
// 新增方法
public function getDefaultWallMasterApply()
public function setDefaultWallMasterApply($applyId)
public function isDefaultWallMasterApply()
```

**主要方法说明**：
- `getDefaultWallMasterApply()`: 获取默认墙主申请（包含用户信息）
- `setDefaultWallMasterApply($applyId)`: 设置指定申请为默认墙主
- `isDefaultWallMasterApply()`: 检查当前申请是否为默认墙主

### 2. API接口
**文件**: `application/api/controller/User.php`

#### 获取默认墙主
```php
/**
 * 获取默认墙主信息
 * GET /api/user/getDefaultWallMaster
 */
public function getDefaultWallMaster(Request $request)
```

#### 设置默认墙主
```php
/**
 * 设置默认墙主（管理员接口）
 * POST /api/user/setDefaultWallMaster
 * @param apply_id 申请ID（优先）
 * @param user_id 用户ID（兼容旧接口）
 */
public function setDefaultWallMaster(Request $request)
```

### 3. 路由配置
**文件**: `route/api.php`

```php
Route::group('api/user', function () {
    Route::get('getDefaultWallMaster', 'api/user/getDefaultWallMaster');
    Route::post('setDefaultWallMaster', 'api/user/setDefaultWallMaster');
})->allowCrossDomain();
```

## 📱 小程序端实现

### 1. API接口定义
**文件**: `api/user.js`

```javascript
/**
 * 获取默认墙主
 */
export const getDefaultWallMaster = () => {
  return get('/api/user/getDefaultWallMaster');
};
```

### 2. 首页逻辑更新
**文件**: `pages/index/index.vue`

```javascript
// 获取墙主id Url参数
async getWallOwnerId(options) {
  let wallOwnerId = options.wallOwnerId;

  if (!wallOwnerId) {
    // 没有墙主ID时，从API获取默认墙主
    const defaultWallMaster = await this.getDefaultWallMaster();
    if (defaultWallMaster) {
      wallOwnerId = defaultWallMaster.user_id;
    }
  }

  return wallOwnerId;
}

// 获取默认墙主
async getDefaultWallMaster() {
  const result = await api.user.getDefaultWallMaster();
  return result.code === 200 ? result.data : null;
}
```

## 🖥️ 管理后台实现

### 1. API接口
**文件**: `public/admin/js/api.js`

```javascript
user: {
    getDefaultWallMaster: () => axios.get(`${API_BASE_URL}/user/getDefaultWallMaster`),
    setDefaultWallMaster: (data) => axios.post(`${API_BASE_URL}/user/setDefaultWallMaster`, data)
}
```

### 2. 墙主申请管理组件
**文件**: `public/admin/js/components/wall-master-apply.js`

新增默认墙主设置卡片：
```html
<!-- 默认墙主设置 -->
<el-card v-if="currentApply.status == 1" class="detail-card">
    <template #header>
        <div class="detail-time">
            <el-icon class="icon-margin-right-lg"><Management /></el-icon>
            <span class="detail-section-title">默认墙主设置</span>
        </div>
    </template>
    <el-switch
        v-model="defaultWallMasterStatus"
        active-text="默认墙主"
        inactive-text="普通墙主"
        @change="handleDefaultWallMasterChange">
    </el-switch>
</el-card>
```

## 🧪 测试验证

### 1. 测试页面
**访问**: `http://your-domain/admin/test_default_wall_master.html`

### 2. 测试内容
- ✅ 数据库迁移执行
- ✅ 获取默认墙主API
- ✅ 设置默认墙主API
- ✅ 小程序API调用
- ✅ 管理后台设置功能

### 3. 验证步骤
1. 执行数据库迁移
2. 测试获取默认墙主接口
3. 测试设置默认墙主接口
4. 验证小程序首页逻辑
5. 验证管理后台设置功能

## 📁 文件变更清单

### 数据库文件
- `database/migrations/add_default_wall_master_field.sql` - 数据库迁移文件

### 后端文件
- `application/common/model/User.php` - 用户模型更新
- `application/api/controller/User.php` - 用户控制器新增接口
- `route/api.php` - 路由配置更新

### 小程序文件
- `api/user.js` - API接口定义
- `pages/index/index.vue` - 首页逻辑更新

### 管理后台文件
- `public/admin/js/api.js` - API接口定义
- `public/admin/js/components/wall-master-apply.js` - 墙主申请管理组件

### 测试文件
- `public/admin/test_default_wall_master.html` - 功能测试页面
- `docs/DEFAULT_WALL_MASTER_IMPLEMENTATION.md` - 实现文档

## 🔄 业务逻辑

### 1. 默认墙主设置逻辑
- 系统中只能有一个默认墙主
- 设置新的默认墙主时，自动清除其他用户的默认墙主标记
- 只有已通过审核的墙主才能设置为默认墙主

### 2. 小程序获取逻辑
- 优先使用URL参数中的墙主ID
- 如果没有指定墙主ID，则获取默认墙主
- 如果没有默认墙主，显示引导页面

### 3. 管理后台设置
- 在墙主申请详情中提供默认墙主设置开关
- 只对已通过审核的申请显示设置选项
- 实时更新设置状态

## 🚀 部署步骤

### 1. 数据库迁移
```bash
# 执行SQL文件
mysql -u username -p database_name < database/migrations/add_default_wall_master_field.sql
```

### 2. 代码部署
- 上传所有变更的文件
- 清理缓存（如有）

### 3. 功能验证
- 访问测试页面验证功能
- 测试小程序首页逻辑
- 验证管理后台设置功能

## ⚠️ 注意事项

1. **数据一致性**: 确保只有一个默认墙主
2. **权限控制**: 只有管理员可以设置默认墙主
3. **错误处理**: 妥善处理API调用失败的情况
4. **向后兼容**: 保持原有硬编码逻辑作为备选方案

## 🎯 功能优势

1. **动态配置**: 不再需要修改代码来更换默认墙主
2. **管理便捷**: 通过管理后台即可设置
3. **逻辑清晰**: 统一的默认墙主获取逻辑
4. **扩展性好**: 为后续功能扩展奠定基础

---

**✅ 默认墙主功能实现完成！**

现在系统支持动态设置默认墙主，小程序首页将自动获取并使用默认墙主信息。

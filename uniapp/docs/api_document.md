# 校园墙应用API文档

## 基础信息

- **基础URL**: `https://api.campus-wall.com`
- **请求格式**: JSON
- **响应格式**: JSON
- **认证方式**: OpenID认证，在请求参数中添加 `openid` 参数
- **状态码**:
  - 200: 成功
  - 400: 请求参数错误
  - 401: 未授权
  - 403: 权限不足
  - 404: 资源不存在
  - 500: 服务器内部错误

## 1. 用户模块

### 1.1 微信授权登录

**接口地址**: `/api/user/login`

**请求方式**: POST

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| code | String | 是 | 微信登录临时凭证 |

**响应示例**:

```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "userInfo": {
      "userId": "user_12345",
      "openId": "oxxxxxxxxxxxxxxxs",
      "nickname": "用户昵称",
      "avatar": "https://xxx.com/avatar.png",
      "role": "user"
    }
  }
}
```

### 1.2 更新用户信息

**接口地址**: `/api/user/update`

**请求方式**: POST

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 用户的OpenID |
| nickname | String | 否 | 用户昵称 |
| avatar | String | 否 | 头像URL |

**响应示例**:

```json
{
  "code": 200,
  "message": "更新成功",
  "data": {
    "userInfo": {
      "userId": "user_12345",
      "nickname": "新昵称",
      "avatar": "https://xxx.com/new_avatar.png"
    }
  }
}
```

### 1.3 获取用户信息

**接口地址**: `/api/user/info`

**请求方式**: GET

**请求头**: 需携带token

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 用户的OpenID |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "userInfo": {
      "userId": "user_12345",
      "openId": "oxxxxxxxxxxxxxxxs",
      "nickname": "用户昵称",
      "avatar": "https://xxx.com/avatar.png",
      "role": "user",
      "createTime": "2023-01-01 12:00:00"
    }
  }
}
```

## 2. 投稿模块

### 2.1 创建投稿

**接口地址**: `/api/post/create`

**请求方式**: POST

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 用户的OpenID |
| wallOwnerId | String | 是 | 墙主用户ID |
| title | String | 是 | 投稿标题，不超过50字 |
| content | String | 是 | 投稿内容，不超过500字 |
| images | Array | 否 | 图片URL数组，最多9张 |
| tags | Array | 否 | 标签ID数组 |
| bgColor | String | 否 | 背景颜色，默认白色 |

**请求示例**:

```json
{
  "openid": "oxxxxxxxxxxxxxxxs",
  "wallOwnerId": "user_abcde",
  "title": "寻物启事",
  "content": "今天在教学楼丢失了一把钥匙，上面有一个小熊挂件，如有拾到请联系我，感谢！",
  "images": ["https://xxx.com/image1.jpg", "https://xxx.com/image2.jpg"],
  "tags": [2],
  "bgColor": "#f8f9fa"
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "投稿成功，等待审核",
  "data": {
    "postId": "post_12345"
  }
}
```

### 2.2 获取投稿列表

**接口地址**: `/api/post/list`

**请求方式**: GET

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | Number | 否 | 页码，默认1 |
| size | Number | 否 | 每页数量，默认10 |
| status | Number | 否 | 状态筛选：0待审核，1已通过，-1未通过，不传则查询全部 |
| userId | String | 否 | 用户ID，不传则查询所有用户的投稿 |
| wallOwnerId | String | 否 | 墙主ID，查询特定墙主的投稿 |
| tagId | Number | 否 | 标签ID，根据标签筛选 |
| keyword | String | 否 | 关键词搜索，搜索标题和内容 |

**响应示例**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "total": 100,
    "page": 1,
    "size": 10,
    "list": [
      {
        "postId": "post_12345",
        "userId": "user_12345",
        "wallOwnerId": "user_abcde",
        "nickname": "用户昵称",
        "avatar": "https://xxx.com/avatar.png",
        "title": "寻物启事",
        "content": "今天在教学楼丢失了一把钥匙...",
        "images": ["https://xxx.com/image1.jpg", "https://xxx.com/image2.jpg"],
        "tags": [2],
        "bgColor": "#f8f9fa",
        "status": 1,
        "createTime": "2023-01-01 12:00:00",
        "reviewTime": "2023-01-01 13:00:00"
      }
      // ... 更多投稿
    ]
  }
}
```

### 2.3 获取投稿详情

**接口地址**: `/api/post/detail`

**请求方式**: GET

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| postId | String | 是 | 投稿ID |

**响应示例**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "postDetail": {
      "postId": "post_12345",
      "userId": "user_12345",
      "wallOwnerId": "user_abcde",
      "nickname": "用户昵称",
      "avatar": "https://xxx.com/avatar.png",
      "title": "寻物启事",
      "content": "今天在教学楼丢失了一把钥匙，上面有一个小熊挂件，如有拾到请联系我，感谢！",
      "images": ["https://xxx.com/image1.jpg", "https://xxx.com/image2.jpg"],
      "tags": [
        { "id": 2, "name": "失物招领" }
      ],
      "bgColor": "#f8f9fa",
      "status": 1,
      "createTime": "2023-01-01 12:00:00",
      "reviewTime": "2023-01-01 13:00:00",
      "reviewComment": "通过审核"
    }
  }
}
```

### 2.4 审核投稿

**接口地址**: `/api/post/review`

**请求方式**: POST

**权限要求**: 该墙墙主权限（只有投稿所属墙墙主可以审核该墙内的投稿）

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 墙主的OpenID |
| postId | String | 是 | 投稿ID |
| status | Number | 是 | 审核状态：1通过，-1拒绝 |
| comment | String | 否 | 审核意见 |

**响应示例**:

```json
{
  "code": 200,
  "message": "审核成功",
  "data": {
    "success": true
  }
}
```

### 2.5 批量审核投稿

**接口地址**: `/api/post/batchReview`

**请求方式**: POST

**权限要求**: 该墙墙主权限（只有投稿所属墙墙主可以批量审核该墙内的投稿）

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 墙主的OpenID |
| postIds | Array | 是 | 投稿ID数组 |
| status | Number | 是 | 审核状态：1通过，-1拒绝 |
| comment | String | 否 | 审核意见 |

**请求示例**:

```json
{
  "openid": "oxxxxxxxxxxxxxxxs",
  "postIds": ["post_12345", "post_12346", "post_12347"],
  "status": 1,
  "comment": "内容符合要求，审核通过"
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "批量审核成功",
  "data": {
    "success": true,
    "total": 3,
    "successCount": 3,
    "failCount": 0
  }
}
```

### 2.6 删除投稿

**接口地址**: `/api/post/delete`

**请求方式**: POST

**权限要求**: 投稿作者或该墙墙主

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 用户的OpenID |
| postId | String | 是 | 投稿ID |

**响应示例**:

```json
{
  "code": 200,
  "message": "删除成功",
  "data": {
    "success": true
  }
}
```

## 3. 标签模块

### 3.1 获取标签列表

**接口地址**: `/api/tag/list`

**请求方式**: GET

**请求参数**: 无

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "tags": [
      { "id": 1, "name": "通知公告", "useCount": 120 },
      { "id": 2, "name": "失物招领", "useCount": 85 },
      { "id": 3, "name": "二手交易", "useCount": 64 },
      { "id": 4, "name": "求助互助", "useCount": 42 },
      { "id": 5, "name": "活动宣传", "useCount": 37 },
      { "id": 6, "name": "日常分享", "useCount": 93 }
    ]
  }
}
```

### 3.2 管理标签

**接口地址**: `/api/tag/manage`

**请求方式**: POST

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| openid | String | 是 | 管理员的OpenID |
| action | String | 是 | 操作类型：add/edit/delete |
| tagId | Number | 否 | 标签ID（edit和delete时必填） |
| tagName | String | 否 | 标签名称（add和edit时必填） |

**请求示例**:

```json
{
  "openid": "oxxxxxxxxxxxxxxxs",
  "action": "add",
  "tagName": "校园活动"
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "success": true,
    "tagId": 7
  }
}
```

## 4. 上传模块

### 4.1 获取上传签名

**接口地址**: `/api/upload/sign`

**请求方式**: GET

**请求头**: 需携带token

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| fileType | String | 是 | 文件类型：image/video |
| fileName | String | 是 | 文件名 |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取签名成功",
  "data": {
    "uploadUrl": "https://upload.xxx.com",
    "sign": "签名字符串",
    "key": "存储路径key",
    "policy": "policy字符串"
  }
}
```

### 4.2 上传图片

**接口地址**: `/api/upload/image`

**请求方式**: POST

**请求头**: 需携带token，Content-Type: multipart/form-data

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| file | File | 是 | 图片文件，大小不超过2MB |

**响应示例**:

```json
{
  "code": 200,
  "message": "上传成功",
  "data": {
    "url": "https://xxx.com/uploads/images/20230101/abcd1234.jpg"
  }
}
```

## 5. 管理模块

### 5.1 生成分享二维码

**接口地址**: `/api/admin/generateQRCode`

**请求方式**: GET

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | String | 是 | 跳转页面路径 |
| scene | String | 是 | 场景参数 |

**响应示例**:

```json
{
  "code": 200,
  "message": "生成成功",
  "data": {
    "qrcodeUrl": "https://xxx.com/uploads/qrcode/abcd1234.jpg"
  }
}
```

### 5.2 生成短链接

**接口地址**: `/api/admin/generateShortLink`

**请求方式**: POST

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| url | String | 是 | 原始链接 |

**响应示例**:

```json
{
  "code": 200,
  "message": "生成成功",
  "data": {
    "shortUrl": "https://s.campus-wall.com/abcde",
    "originalUrl": "https://xxx.com/page?id=12345",
    "expireTime": "2024-01-01 00:00:00"
  }
}
```

### 5.3 生成墙主专属短链

**接口地址**: `/api/admin/generateWallOwnerLink`

**请求方式**: POST

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| wallOwnerId | String | 是 | 墙主用户ID |
| customCode | String | 否 | 自定义短链代码，不传则自动生成 |
| validDays | Number | 否 | 有效期天数，默认365天，0表示永不过期 |

**请求示例**:

```json
{
  "wallOwnerId": "user_12345",
  "customCode": "mywall",
  "validDays": 180
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "生成成功",
  "data": {
    "shortUrl": "https://s.campus-wall.com/mywall",
    "qrCodeUrl": "https://xxx.com/uploads/qrcode/mywall.jpg",
    "wallOwnerName": "张三的校园墙",
    "expireTime": "2024-06-30 00:00:00",
    "visitCount": 0
  }
}
```

### 5.4 获取墙主专属链接列表

**接口地址**: `/api/admin/wallOwnerLinks`

**请求方式**: GET

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | Number | 否 | 页码，默认1 |
| size | Number | 否 | 每页数量，默认10 |
| keyword | String | 否 | 关键词搜索 |

**响应示例**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "total": 5,
    "page": 1,
    "size": 10,
    "list": [
      {
        "id": "link_12345",
        "shortCode": "mywall",
        "shortUrl": "https://s.campus-wall.com/mywall",
        "wallOwnerId": "user_12345",
        "wallOwnerName": "张三的校园墙",
        "createTime": "2023-12-31 12:00:00",
        "expireTime": "2024-06-30 00:00:00",
        "visitCount": 120,
        "status": 1
      }
      // ... 其他链接
    ]
  }
}
```

### 5.5 获取数据统计

**接口地址**: `/api/admin/statistics`

**请求方式**: GET

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| startDate | String | 否 | 开始日期，格式：YYYY-MM-DD |
| endDate | String | 否 | 结束日期，格式：YYYY-MM-DD |
| wallOwnerId | String | 否 | 墙主ID，查询特定墙主的统计数据 |

**响应示例**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "userCount": 1000,
    "newUserCount": 50,
    "postCount": 120,
    "pendingReviewCount": 15,
    "dailyStats": [
      {
        "date": "2023-01-01",
        "newUsers": 10,
        "posts": 5,
        "reviews": 4
      }
      // ... 更多日期统计
    ],
    "tagStats": [
      {
        "tagId": 1,
        "tagName": "通知公告",
        "count": 35
      }
      // ... 更多标签统计
    ]
  }
}
```

### 5.6 获取墙主设置

**接口地址**: `/api/admin/wallSettings`

**请求方式**: GET

**请求头**: 需携带token

**权限要求**: 墙主/管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| wallOwnerId | String | 否 | 墙主ID，不传则获取当前用户的墙主设置 |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "settings": {
      "wallName": "张三的校园墙",
      "description": "欢迎大家在这里分享校园生活",
      "logoUrl": "https://xxx.com/logo.jpg",
      "themeColor": "#2979ff",
      "bannerUrl": "https://xxx.com/banner.jpg",
      "qrcodeUrl": "https://xxx.com/qrcode.jpg",
      "autoReview": false,
      "reviewRules": {
        "keywords": ["敏感词1", "敏感词2"],
        "imageCheck": true
      },
      "notificationConfig": {
        "newPost": true,
        "reviewResult": true
      },
      "postCategories": [
        {"id": 1, "name": "校园活动"},
        {"id": 2, "name": "学习互助"}
      ],
      "customFields": [
        {"id": "contact", "name": "联系方式", "required": true}
      ],
      "contactInfo": "微信: campus123",
      "createTime": "2023-01-01 12:00:00",
      "updateTime": "2023-02-01 15:30:00",
      "status": 1
    }
  }
}
```

### 5.7 更新墙主设置

**接口地址**: `/api/admin/updateWallSettings`

**请求方式**: POST

**请求头**: 需携带token

**权限要求**: 墙主/管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| wallOwnerId | String | 否 | 墙主ID，不传则更新当前用户的墙主设置 |
| wallName | String | 否 | 校园墙名称 |
| description | String | 否 | 校园墙描述 |
| logoUrl | String | 否 | logo图片URL |
| themeColor | String | 否 | 主题色 |
| bannerUrl | String | 否 | banner图片URL |
| qrcodeUrl | String | 否 | 专属二维码图片URL |
| autoReview | Boolean | 否 | 是否自动审核 |
| reviewRules | Object | 否 | 审核规则配置 |
| notificationConfig | Object | 否 | 通知设置 |
| postCategories | Array | 否 | 自定义投稿分类 |
| customFields | Array | 否 | 自定义投稿字段 |
| contactInfo | String | 否 | 联系方式 |
| status | Number | 否 | 状态：1启用，0禁用 |

**请求示例**:

```json
{
  "wallName": "张三的科技墙",
  "themeColor": "#1976D2",
  "autoReview": true,
  "reviewRules": {
    "keywords": ["敏感词1", "敏感词2", "敏感词3"],
    "imageCheck": true
  },
  "notificationConfig": {
    "newPost": true,
    "reviewResult": false
  }
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "更新成功",
  "data": {
    "success": true,
    "settings": {
      "wallName": "张三的科技墙",
      "themeColor": "#1976D2",
      "autoReview": true,
      "reviewRules": {
        "keywords": ["敏感词1", "敏感词2", "敏感词3"],
        "imageCheck": true
      },
      "notificationConfig": {
        "newPost": true,
        "reviewResult": false
      },
      // ... 其他设置项
    }
  }
}
```

### 5.8 申请成为墙主

**接口地址**: `/api/admin/applyWallOwner`

**请求方式**: POST

**请求头**: 需携带token

**权限要求**: 普通用户权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| schoolName | String | 是 | 学校名称 |
| wallName | String | 是 | 想要创建的校园墙名称 |
| description | String | 是 | 校园墙描述/用途 |
| contactPhone | String | 是 | 联系电话 |
| contactWechat | String | 否 | 微信号 |
| applyReason | String | 是 | 申请理由 |
| studentProofUrl | String | 是 | 学生证明材料URL(如学生证照片) |

**请求示例**:

```json
{
  "schoolName": "清华大学",
  "wallName": "清华校园信息墙",
  "description": "分享校园生活、失物招领、二手交易等信息",
  "contactPhone": "13800138000",
  "contactWechat": "campus_123",
  "applyReason": "希望为同学们提供一个信息交流的平台，促进校园信息共享",
  "studentProofUrl": "https://xxx.com/uploads/proofs/student_123.jpg"
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "申请提交成功，请等待审核",
  "data": {
    "applyId": "apply_12345",
    "applyTime": "2023-05-01 10:30:00",
    "status": 0
  }
}
```

### 5.9 审核墙主申请

**接口地址**: `/api/admin/reviewWallOwnerApply`

**请求方式**: POST

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| applyId | String | 是 | 申请ID |
| status | Number | 是 | 审核状态：1通过，-1拒绝 |
| comment | String | 否 | 审核意见 |
| wallOwnerId | String | 否 | 如果通过，可以指定墙主ID，不传则自动生成 |

**请求示例**:

```json
{
  "applyId": "apply_12345",
  "status": 1,
  "comment": "资料审核通过，欢迎加入校园墙",
  "wallOwnerId": "wall_tsinghua"
}
```

**响应示例**:

```json
{
  "code": 200,
  "message": "审核成功",
  "data": {
    "success": true,
    "applyId": "apply_12345",
    "userId": "user_12345",
    "wallOwnerId": "wall_tsinghua",
    "reviewTime": "2023-05-02 14:20:00"
  }
}
```

### 5.10 获取墙主申请列表

**接口地址**: `/api/admin/wallOwnerApplyList`

**请求方式**: GET

**请求头**: 需携带token

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | Number | 否 | 页码，默认1 |
| size | Number | 否 | 每页数量，默认10 |
| status | Number | 否 | 状态筛选：0待审核，1已通过，-1未通过，不传则查询全部 |

**响应示例**:

```json
{
  "code": 200,
  "message": "查询成功",
  "data": {
    "total": 25,
    "page": 1,
    "size": 10,
    "list": [
      {
        "applyId": "apply_12345",
        "userId": "user_12345",
        "nickname": "张三",
        "avatar": "https://xxx.com/avatar.png",
        "schoolName": "清华大学",
        "wallName": "清华校园信息墙",
        "description": "分享校园生活、失物招领、二手交易等信息",
        "contactPhone": "138****8000",
        "contactWechat": "campus_123",
        "applyReason": "希望为同学们提供一个信息交流的平台，促进校园信息共享",
        "studentProofUrl": "https://xxx.com/uploads/proofs/student_123.jpg",
        "applyTime": "2023-05-01 10:30:00",
        "status": 1,
        "reviewTime": "2023-05-02 14:20:00",
        "reviewComment": "资料审核通过，欢迎加入校园墙"
      }
      // ... 更多申请记录
    ]
  }
}
```

## 6. 管理后台接口

### 6.1 管理员登录

**接口地址**: `/api/admin/login`

**请求方式**: POST

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| username | String | 是 | 管理员用户名 |
| password | String | 是 | 管理员密码 |

**响应示例**:

```json
{
  "code": 200, 
  "message": "登录成功", 
  "data": {
    "token": "md5生成的token字符串",
    "userInfo": {
      "id": 1,
      "username": "admin",
      "nickname": "管理员",
      "last_login_time": "2023-01-01 12:00:00"
    }
  }
}
```

### 6.2 获取仪表盘统计数据

**接口地址**: `/api/admin/dashboardStats`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**: 无

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "userCount": 1000,
    "postCount": 500,
    "pendingApplyCount": 5
  }
}
```

### 6.3 获取近期活动记录

**接口地址**: `/api/admin/recentActivities`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| limit | Number | 否 | 获取条数，默认10条 |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "activities": [
      {
        "time": "2023-05-01 10:30:00",
        "user": "管理员",
        "action": "墙主申请审核",
        "details": "通过了用户ID:12345的墙主申请，申请ID:67890"
      },
      {
        "time": "2023-05-01 09:15:00",
        "user": "管理员",
        "action": "登录系统",
        "details": "管理员登录"
      }
      // ... 更多活动记录
    ]
  }
}
```

### 6.4 获取墙主申请列表

**接口地址**: `/api/admin/wallMasterApplyList`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | Number | 否 | 页码，默认1 |
| size | Number | 否 | 每页数量，默认10 |
| status | Number/String | 否 | 状态筛选：0待审核，1已通过，-1未通过，"all"查询全部 |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "total": 25,
    "list": [
      {
        "applyId": 12345,
        "userId": 67890,
        "nickname": "张三",
        "avatar": "https://xxx.com/avatar.png",
        "schoolName": "清华大学",
        "wallName": "清华校园信息墙",
        "status": 0,
        "applyTime": "2023-05-01 10:30:00"
      }
      // ... 更多申请记录
    ],
    "page": 1,
    "pageSize": 10
  }
}
```

### 6.5 获取墙主申请详情

**接口地址**: `/api/admin/wallMasterApplyDetail`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| applyId | Number | 是 | 申请ID |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "applyDetail": {
      "applyId": 12345,
      "userId": 67890,
      "nickname": "张三",
      "avatar": "https://xxx.com/avatar.png",
      "schoolName": "清华大学",
      "wallName": "清华校园信息墙",
      "description": "分享校园生活、失物招领、二手交易等信息",
      "contactPhone": "13800138000",
      "contactWechat": "campus_123",
      "applyReason": "希望为同学们提供一个信息交流的平台，促进校园信息共享",
      "studentProofUrl": "https://xxx.com/uploads/proofs/student_123.jpg",
      "status": 0,
      "applyTime": "2023-05-01 10:30:00",
      "reviewTime": "",
      "reviewComment": ""
    }
  }
}
```

### 6.6 审核墙主申请

**接口地址**: `/api/admin/reviewWallMasterApply`

**请求方式**: POST

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| applyId | Number | 是 | 申请ID |
| status | Number | 是 | 审核状态：1通过，-1拒绝 |
| comment | String | 否 | 审核意见 |

**响应示例**:

```json
{
  "code": 200,
  "message": "审核成功"
}
```

### 6.7 获取墙列表

**接口地址**: `/api/admin/wallList`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**: 无

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "list": [
      {
        "wallId": 1,
        "wallName": "清华校园信息墙",
        "schoolName": "清华大学",
        "creatorName": "张三",
        "createTime": "2023-05-02 14:20:00"
      }
      // ... 更多墙记录
    ]
  }
}
```

### 6.8 获取投稿列表

**接口地址**: `/api/admin/postList`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| page | Number | 否 | 页码，默认1 |
| size | Number | 否 | 每页数量，默认10 |
| status | Number/String | 否 | 状态筛选：0待审核，1已通过，-1未通过，"all"查询全部 |
| wallId | Number/String | 否 | 墙ID，"all"查询所有墙 |
| keyword | String | 否 | 关键词搜索，搜索标题和内容 |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "total": 500,
    "list": [
      {
        "postId": 12345,
        "title": "寻物启事",
        "wallId": 1,
        "wallName": "清华校园信息墙",
        "userId": 67890,
        "nickname": "张三",
        "status": 0,
        "postTime": "2023-05-03 10:00:00"
      }
      // ... 更多投稿记录
    ],
    "page": 1,
    "pageSize": 10
  }
}
```

### 6.9 获取投稿详情

**接口地址**: `/api/admin/postDetail`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| postId | Number | 是 | 投稿ID |

**响应示例**:

```json
{
  "code": 200,
  "message": "获取成功",
  "data": {
    "postDetail": {
      "postId": 12345,
      "title": "寻物启事",
      "content": "今天在教学楼丢失了一把钥匙，上面有一个小熊挂件，如有拾到请联系我，感谢！",
      "imageUrls": ["https://xxx.com/image1.jpg", "https://xxx.com/image2.jpg"],
      "wallId": 1,
      "wallName": "清华校园信息墙",
      "userId": 67890,
      "nickname": "张三",
      "status": 0,
      "postTime": "2023-05-03 10:00:00",
      "reviewTime": "",
      "reviewComment": "",
      "publishTime": ""
    }
  }
}
```

### 6.10 审核投稿

**接口地址**: `/api/admin/reviewPost`

**请求方式**: POST

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| postId | Number | 是 | 投稿ID |
| status | Number | 是 | 审核状态：1通过，-1拒绝 |
| comment | String | 否 | 审核意见 |

**响应示例**:

```json
{
  "code": 200,
  "message": "审核成功"
}
```

### 6.11 发布投稿

**接口地址**: `/api/admin/publishPost`

**请求方式**: POST

**权限要求**: 管理员权限

**请求参数**:

| 参数名 | 类型 | 必填 | 描述 |
| ----- | ---- | ---- | ---- |
| postId | Number | 是 | 投稿ID |

**响应示例**:

```json
{
  "code": 200,
  "message": "发布成功"
}
```

### 6.12 检查认证状态

**接口地址**: `/api/admin/checkAuth`

**请求方式**: GET

**权限要求**: 管理员权限

**请求参数**: 无

**响应示例**:

```json
{
  "code": 200,
  "message": "认证成功",
  "data": {
    "adminId": 1,
    "time": "2023-05-01 10:30:00",
    "info": "如果您能看到此消息，说明权限验证已通过"
  }
}
```

## 错误码说明

| 错误码 | 说明 |
| ----- | ---- |
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权或token已过期 |
| 403 | 权限不足 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

## 附录

### 投稿状态码

| 状态码 | 说明 |
| ----- | ---- |
| 0 | 待审核 |
| 1 | 已通过 |
| -1 | 未通过 |

### 用户角色

| 角色值 | 说明 |
| ----- | ---- |
| user | 普通用户 |
| admin | 管理员/墙主 | 

## 7. 小程序与服务器通信指南

### 7.1 基础通信配置

以下是小程序中封装的通用请求方法，用于处理与服务器的通信：

```javascript
// utils/request.js
const BASE_URL = 'https://api.campus-wall.com';

// 封装请求方法
const request = (url, method, data) => {
  return new Promise((resolve, reject) => {
    const openid = wx.getStorageSync('openid');
    // 自动添加openid到请求参数中
    if (openid && data) {
      data.openid = openid;
    } else if (openid && method === 'GET') {
      url = url.includes('?') ? `${url}&openid=${openid}` : `${url}?openid=${openid}`;
    }
    
    wx.request({
      url: `${BASE_URL}${url}`,
      method: method,
      data: data,
      header: {
        'content-type': 'application/json'
      },
      success: (res) => {
        if (res.data.code === 200) {
          resolve(res.data.data);
        } else {
          handleErrorCode(res.data.code, res.data.message);
          reject(res.data);
        }
      },
      fail: (err) => {
        wx.showToast({
          title: '网络异常',
          icon: 'none'
        });
        reject(err);
      }
    });
  });
};

// 处理错误码
const handleErrorCode = (code, message) => {
  let title = message || '请求失败';
  
  switch (code) {
    case 400:
      title = message || '请求参数错误';
      break;
    case 401:
      title = '登录已过期，请重新登录';
      // 清除登录信息并跳转登录页
      wx.removeStorageSync('openid');
      wx.removeStorageSync('userInfo');
      wx.navigateTo({ url: '/pages/login/login' });
      break;
    case 403:
      title = '您没有权限进行此操作';
      break;
    case 404:
      title = '请求的资源不存在';
      break;
    case 500:
      title = '服务器内部错误';
      break;
  }
  
  wx.showToast({
    title: title,
    icon: 'none'
  });
};

module.exports = {
  get: (url, data) => request(url, 'GET', data),
  post: (url, data) => request(url, 'POST', data)
};
```

### 7.2 用户认证通信

```javascript
// services/user.js
const request = require('../utils/request');

// 微信登录
const login = () => {
  return new Promise((resolve, reject) => {
    wx.login({
      success: (res) => {
        if (res.code) {
          // 发送code到服务器换取openid
          request.post('/api/user/login', {
            code: res.code
          }).then(data => {
            // 保存用户信息和openid
            wx.setStorageSync('openid', data.userInfo.openId);
            wx.setStorageSync('userInfo', data.userInfo);
            resolve(data);
          }).catch(err => {
            reject(err);
          });
        } else {
          reject(res);
        }
      },
      fail: (err) => {
        reject(err);
      }
    });
  });
};

// 更新用户信息
const updateUserInfo = (userInfo) => {
  return request.post('/api/user/update', userInfo);
};

// 获取用户信息
const getUserInfo = () => {
  return request.get('/api/user/info');
};

module.exports = {
  login,
  updateUserInfo,
  getUserInfo
};
```

### 7.3 投稿功能通信

```javascript
// services/post.js
const request = require('../utils/request');

// 创建投稿
const createPost = (postData) => {
  return request.post('/api/post/create', postData);
};

// 获取投稿列表
const getPostList = (params = {}) => {
  return request.get('/api/post/list', params);
};

// 获取投稿详情
const getPostDetail = (postId) => {
  return request.get('/api/post/detail', { postId });
};

// 审核投稿(墙主)
const reviewPost = (postId, status, comment) => {
  return request.post('/api/post/review', { postId, status, comment });
};

// 批量审核投稿(墙主)
const batchReviewPost = (postIds, status, comment) => {
  return request.post('/api/post/batchReview', { postIds, status, comment });
};

// 删除投稿
const deletePost = (postId) => {
  return request.post('/api/post/delete', { postId });
};

module.exports = {
  createPost,
  getPostList,
  getPostDetail,
  reviewPost,
  batchReviewPost,
  deletePost
};
```

### 7.4 文件上传通信

```javascript
// services/upload.js
const request = require('../utils/request');
const BASE_URL = 'https://api.campus-wall.com';

// 上传图片
const uploadImage = (filePath) => {
  return new Promise((resolve, reject) => {
    const openid = wx.getStorageSync('openid');
    
    wx.uploadFile({
      url: `${BASE_URL}/api/upload/image`,
      filePath: filePath,
      name: 'file',
      formData: {
        openid: openid
      },
      success(res) {
        const data = JSON.parse(res.data);
        if (data.code === 200) {
          resolve(data.data.url);
        } else {
          wx.showToast({
            title: data.message || '上传失败',
            icon: 'none'
          });
          reject(data);
        }
      },
      fail(err) {
        wx.showToast({
          title: '上传失败',
          icon: 'none'
        });
        reject(err);
      }
    });
  });
};

// 获取上传签名
const getUploadSign = (fileType, fileName) => {
  return request.get('/api/upload/sign', { fileType, fileName });
};

module.exports = {
  uploadImage,
  getUploadSign
};
```

### 7.5 实际应用场景示例

#### 用户登录页面示例

```javascript
// pages/login/login.js
const userService = require('../../services/user');

Page({
  data: {
    userInfo: null
  },
  
  onLoad() {
    // 自动登录
    this.handleLogin();
  },
  
  handleLogin() {
    wx.showLoading({ title: '登录中...' });
    userService.login()
      .then(res => {
        wx.hideLoading();
        this.setData({ userInfo: res.userInfo });
        wx.navigateBack();
      })
      .catch(err => {
        wx.hideLoading();
        console.error('登录失败', err);
      });
  }
});
```

#### 投稿列表页面示例

```javascript
// pages/post-list/index.js
const postService = require('../../services/post');

Page({
  data: {
    postList: [],
    page: 1,
    size: 10,
    hasMore: true,
    loading: false
  },
  
  onLoad(options) {
    // 获取墙主ID（如果有的话）
    this.wallOwnerId = options.wallOwnerId || '';
    this.loadData();
  },
  
  // 加载投稿列表
  loadData(refresh = false) {
    if (this.data.loading) return;
    
    const page = refresh ? 1 : this.data.page;
    this.setData({ loading: true });
    
    const params = {
      page: page,
      size: this.data.size
    };
    
    // 如果有墙主ID，添加到查询参数
    if (this.wallOwnerId) {
      params.wallOwnerId = this.wallOwnerId;
    }
    
    postService.getPostList(params)
      .then(res => {
        const list = refresh ? res.list : [...this.data.postList, ...res.list];
        const hasMore = list.length < res.total;
        
        this.setData({
          postList: list,
          page: page + 1,
          hasMore: hasMore,
          loading: false
        });
      })
      .catch(err => {
        this.setData({ loading: false });
        console.error('获取投稿列表失败', err);
      });
  },
  
  // 下拉刷新
  onPullDownRefresh() {
    this.loadData(true);
    wx.stopPullDownRefresh();
  },
  
  // 上拉加载更多
  onReachBottom() {
    if (this.data.hasMore) {
      this.loadData();
    }
  }
});
```

#### 发布投稿页面示例

```javascript
// pages/post-create/index.js
const postService = require('../../services/post');
const uploadService = require('../../services/upload');

Page({
  data: {
    title: '',
    content: '',
    images: [],
    tags: [],
    selectedTags: [],
    bgColor: '#ffffff',
    submitting: false
  },
  
  onLoad(options) {
    // 获取墙主ID
    this.wallOwnerId = options.wallOwnerId || '';
    // 加载标签列表
    this.loadTags();
  },
  
  // 加载标签
  loadTags() {
    wx.request({
      url: 'https://api.campus-wall.com/api/tag/list',
      success: (res) => {
        if (res.data.code === 200) {
          this.setData({
            tags: res.data.data.tags
          });
        }
      }
    });
  },
  
  // 选择图片
  chooseImage() {
    wx.chooseImage({
      count: 9 - this.data.images.length,
      success: (res) => {
        this.uploadImages(res.tempFilePaths);
      }
    });
  },
  
  // 上传图片
  uploadImages(tempFilePaths) {
    const uploads = tempFilePaths.map(path => uploadService.uploadImage(path));
    
    wx.showLoading({ title: '上传中...' });
    Promise.all(uploads)
      .then(urls => {
        wx.hideLoading();
        this.setData({
          images: [...this.data.images, ...urls]
        });
      })
      .catch(err => {
        wx.hideLoading();
        console.error('上传图片失败', err);
      });
  },
  
  // 表单提交
  submitForm() {
    const { title, content, images, selectedTags, bgColor } = this.data;
    
    // 表单验证
    if (!title) {
      return wx.showToast({ title: '请输入标题', icon: 'none' });
    }
    if (!content) {
      return wx.showToast({ title: '请输入内容', icon: 'none' });
    }
    if (!this.wallOwnerId) {
      return wx.showToast({ title: '缺少墙主ID', icon: 'none' });
    }
    
    this.setData({ submitting: true });
    
    const postData = {
      wallOwnerId: this.wallOwnerId,
      title,
      content,
      images,
      tags: selectedTags,
      bgColor
    };
    
    postService.createPost(postData)
      .then(res => {
        this.setData({ submitting: false });
        wx.showToast({ title: '投稿成功', icon: 'success' });
        setTimeout(() => {
          wx.navigateBack();
        }, 1500);
      })
      .catch(err => {
        this.setData({ submitting: false });
        console.error('投稿失败', err);
      });
  }
});
```

### 7.6 通信最佳实践

1. **统一请求封装**：将所有API请求统一封装，方便管理和调试。

2. **错误处理统一化**：对API返回的错误码进行统一处理，提高用户体验。

3. **接口服务分层**：按功能模块划分服务层，保持代码结构清晰。

4. **状态码处理**：根据API文档中的错误码说明，针对不同错误给出用户友好提示。

5. **Token管理**：妥善保存和使用openid，确保用户身份验证的安全性。

6. **数据缓存策略**：合理利用本地缓存，减少不必要的网络请求。

7. **页面状态管理**：妥善处理页面的加载状态、错误状态和空数据状态。

8. **防抖与节流**：对频繁触发的事件（如搜索、下拉刷新）进行防抖和节流处理。 
# 校园墙应用数据库设计文档

## 概述

本文档详细描述了校园墙投稿应用的数据库设计，包括表结构、字段说明、索引设计和关联关系等内容。数据库采用MongoDB设计，适合文档型数据存储和查询。

## 数据库表设计

### 1. 用户表（users）

**表说明**：存储用户基本信息和授权信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| openid | String | 是 | - | 微信openid，唯一 |
| unionid | String | 否 | null | 微信unionid |
| nickname | String | 否 | "微信用户" | 用户昵称 |
| avatar | String | 否 | 默认头像URL | 头像URL |
| gender | Number | 否 | 0 | 性别：0未知，1男，2女 |
| role | String | 是 | "user" | 用户角色：user普通用户，admin管理员 |
| phone | String | 否 | null | 手机号 |
| create_time | Date | 是 | Date.now | 创建时间 |
| update_time | Date | 是 | Date.now | 更新时间 |
| last_login_time | Date | 是 | Date.now | 最后登录时间 |
| status | Number | 是 | 1 | 状态：1正常，0禁用 |

**索引设计**：
- openid：唯一索引
- unionid：普通索引
- phone：普通索引
- role + status：组合索引（用于权限查询）

### 2. 投稿表（posts）

**表说明**：存储用户投稿内容

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| user_id | ObjectId | 是 | - | 用户ID，关联users表 |
| wall_owner_id | ObjectId | 是 | - | 墙主ID，关联users表 |
| title | String | 是 | - | 投稿标题，限50字 |
| content | String | 是 | - | 投稿内容，限500字 |
| images | Array | 否 | [] | 图片URL数组，最多9张 |
| tags | Array | 否 | [] | 标签ID数组，最多3个 |
| bg_color | String | 否 | "#ffffff" | 背景颜色 |
| status | Number | 是 | 0 | 状态：0待审核，1已通过，-1未通过 |
| view_count | Number | 是 | 0 | 浏览次数 |
| like_count | Number | 是 | 0 | 点赞次数 |
| create_time | Date | 是 | Date.now | 创建时间 |
| update_time | Date | 是 | Date.now | 更新时间 |
| review_time | Date | 否 | null | 审核时间 |
| review_comment | String | 否 | null | 审核意见 |
| reviewer_id | ObjectId | 否 | null | 审核人ID |
| is_top | Boolean | 是 | false | 是否置顶 |
| is_deleted | Boolean | 是 | false | 是否删除 |

**索引设计**：
- user_id + status：组合索引（用于查询用户的投稿）
- wall_owner_id + status：组合索引（用于查询墙主的投稿）
- status + create_time：组合索引（用于查询待审核、已通过的投稿）
- tags：多键索引（用于按标签查询）
- create_time：普通索引（用于时间排序）

### 3. 标签表（tags）

**表说明**：存储投稿标签信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| name | String | 是 | - | 标签名称，唯一 |
| use_count | Number | 是 | 0 | 使用次数 |
| create_time | Date | 是 | Date.now | 创建时间 |
| update_time | Date | 是 | Date.now | 更新时间 |
| status | Number | 是 | 1 | 状态：1启用，0禁用 |
| sort | Number | 是 | 0 | 排序权重 |

**索引设计**：
- name：唯一索引
- use_count：普通索引（用于热门标签排序）
- sort：普通索引（用于自定义排序）

### 4. 点赞表（likes）

**表说明**：存储用户点赞记录

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| user_id | ObjectId | 是 | - | 用户ID，关联users表 |
| post_id | ObjectId | 是 | - | 投稿ID，关联posts表 |
| create_time | Date | 是 | Date.now | 创建时间 |

**索引设计**：
- user_id + post_id：唯一组合索引（防止重复点赞）
- post_id：普通索引（用于查询投稿的点赞记录）

### 5. 收藏表（collections）

**表说明**：存储用户收藏记录

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| user_id | ObjectId | 是 | - | 用户ID，关联users表 |
| post_id | ObjectId | 是 | - | 投稿ID，关联posts表 |
| create_time | Date | 是 | Date.now | 创建时间 |

**索引设计**：
- user_id + post_id：唯一组合索引（防止重复收藏）
- user_id + create_time：组合索引（用于查询用户的收藏列表）

### 6. 评论表（comments）

**表说明**：存储投稿评论信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| post_id | ObjectId | 是 | - | 投稿ID，关联posts表 |
| user_id | ObjectId | 是 | - | 评论用户ID |
| content | String | 是 | - | 评论内容 |
| reply_to | ObjectId | 否 | null | 回复的评论ID |
| status | Number | 是 | 0 | 状态：0待审核，1已通过，-1未通过 |
| create_time | Date | 是 | Date.now | 创建时间 |
| review_time | Date | 否 | null | 审核时间 |

**索引设计**：
- post_id + status：组合索引（用于查询投稿的已通过评论）
- user_id：普通索引（用于查询用户的评论）
- reply_to：普通索引（用于查询评论回复）

### 7. 通知表（notifications）

**表说明**：存储用户通知信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| user_id | ObjectId | 是 | - | 接收用户ID |
| type | String | 是 | - | 通知类型：review审核结果，like点赞，comment评论，system系统通知 |
| title | String | 是 | - | 通知标题 |
| content | String | 是 | - | 通知内容 |
| related_id | ObjectId | 否 | null | 关联ID，如投稿ID |
| is_read | Boolean | 是 | false | 是否已读 |
| create_time | Date | 是 | Date.now | 创建时间 |

**索引设计**：
- user_id + is_read：组合索引（用于查询用户未读通知）
- user_id + create_time：组合索引（用于按时间查询用户通知）

### 8. 短链表（short_links）

**表说明**：存储短链接信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| short_code | String | 是 | - | 短链码，唯一 |
| original_url | String | 是 | - | 原始链接 |
| user_id | ObjectId | 是 | - | 创建用户ID |
| click_count | Number | 是 | 0 | 点击次数 |
| expire_time | Date | 否 | null | 过期时间，null表示永不过期 |
| create_time | Date | 是 | Date.now | 创建时间 |

**索引设计**：
- short_code：唯一索引
- user_id：普通索引（用于查询用户创建的短链）
- expire_time：普通索引（用于清理过期短链）

### 9. 操作日志表（operation_logs）

**表说明**：记录管理员操作日志

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| user_id | ObjectId | 是 | - | 操作用户ID |
| action | String | 是 | - | 操作类型 |
| target_type | String | 是 | - | 目标类型：post投稿，user用户，tag标签等 |
| target_id | ObjectId | 是 | - | 目标ID |
| detail | Object | 否 | {} | 操作详情 |
| ip | String | 否 | - | 操作IP |
| create_time | Date | 是 | Date.now | 创建时间 |

**索引设计**：
- user_id + create_time：组合索引（用于查询用户的操作日志）
- target_type + target_id：组合索引（用于查询特定对象的操作记录）
- create_time：普通索引（用于按时间查询）

### 10. 墙主设置表（wall_settings）

**表说明**：存储墙主的配置和设置信息

**字段设计**：

| 字段名 | 类型 | 必填 | 默认值 | 说明 |
| ----- | ---- | ---- | ----- | ---- |
| _id | ObjectId | 是 | 自动生成 | 主键ID |
| owner_id | ObjectId | 是 | - | 墙主ID，关联users表 |
| wall_name | String | 是 | "我的校园墙" | 校园墙名称 |
| description | String | 否 | "" | 校园墙描述 |
| logo_url | String | 否 | null | 校园墙logo图片URL |
| theme_color | String | 否 | "#2979ff" | 主题色 |
| banner_url | String | 否 | null | 顶部banner图片URL |
| qrcode_url | String | 否 | null | 校园墙专属二维码图片URL |
| auto_review | Boolean | 是 | false | 是否开启自动审核 |
| review_rules | Object | 否 | {} | 自动审核规则配置 |
| notification_config | Object | 是 | { "new_post": true, "review_result": true } | 通知设置 |
| post_categories | Array | 否 | [] | 自定义投稿分类 |
| custom_fields | Array | 否 | [] | 自定义投稿字段 |
| contact_info | String | 否 | "" | 联系方式 |
| create_time | Date | 是 | Date.now | 创建时间 |
| update_time | Date | 是 | Date.now | 更新时间 |
| status | Number | 是 | 1 | 状态：1启用，0禁用 |

**索引设计**：
- owner_id：唯一索引（一个墙主只有一条设置记录）
- status：普通索引（用于查询启用的墙主设置）
- wall_name：普通索引（用于墙主设置搜索）

## 数据关系图

```
+-------+       +---------+       +------+
| users |<----->| posts   |<----->| tags |
+-------+       +---------+       +------+
    ^               ^
    |               |
    v               v
+-------+       +---------+
| likes |       |comments |
+-------+       +---------+
    ^               ^
    |               |
    v               v
+--------------+    |
| collections  |<---+
+--------------+
    ^
    |
    v
+---------------+
| wall_settings |
+---------------+
```

## 数据库优化策略

1. **索引优化**：
   - 为常用查询字段创建索引
   - 使用复合索引优化多条件查询
   - 避免过多索引导致写入性能下降

2. **数据分片**：
   - 按用户ID对投稿表进行分片
   - 历史数据归档，减少活跃集合大小

3. **缓存策略**：
   - 热门投稿使用Redis缓存
   - 用户信息和标签信息缓存
   - 首页数据定时预热

4. **数据备份**：
   - 每日全量备份
   - 实时增量备份
   - 多地域备份策略

## 数据迁移和版本控制

1. **版本管理**：
   - 使用数据库版本号控制结构变更
   - 变更脚本版本化管理

2. **迁移策略**：
   - 使用增量迁移脚本
   - 大型迁移任务分批执行
   - 保留回滚机制

## 安全策略

1. **数据加密**：
   - 敏感用户信息加密存储
   - 传输数据TLS加密

2. **访问控制**：
   - 基于角色的数据库访问控制
   - 最小权限原则
   - 定期审计数据库访问日志

3. **防注入**：
   - 使用参数化查询
   - 输入数据验证和清洗 
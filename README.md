<div align="center">

# 校园墙 · 投稿小程序

**把高校里那面「贴满纸条的墙」，做成一条能审核、能分账、能算账的流水线。**

微信小程序 + 管理后台 + WebSocket 实时通知 + 微信支付佣金分账。从投稿到提现，全链路闭环。

![小程序](https://img.shields.io/badge/小程序-UniApp%20%2B%20uView-07C160?logo=wechat&logoColor=white)
![后端](https://img.shields.io/badge/后端-ThinkPHP%205.1%20%2B%20PHP%208-4B8BBE)
![后台](https://img.shields.io/badge/后台-Vue%203%20%2B%20Element%20Plus-42b883?logo=vue.js&logoColor=white)
![实时](https://img.shields.io/badge/实时-GatewayWorker%20WebSocket-8B5CF6)
![接口](https://img.shields.io/badge/API-80%2B%20个-0EA5E9)
![表](https://img.shields.io/badge/MySQL-19%20张表-4479A1?logo=mysql&logoColor=white)

`微信支付分账` · `DFA 敏感词 O(n)` · `Canvas 海报` · `审核状态机` · `离线消息不丢`

</div>

---

## 它是什么

校园墙是高校里信息密度最高的地方：二手、拼车、失物、表白、吐槽。但它一直停留在「群聊 + 公告栏」的形态——信息碎、没人审、发完就沉、运营没收益。

这套系统把它当成一个**内容产品**来做：

| 角色 | 他要什么 | 系统给什么 |
| --- | --- | --- |
| 学生 | 发得快、看得见、不暴露身份 | 文字 + 图片 + 标签 + 背景色，提交前可预览，匿名可选 |
| 墙主 | 审得动、管得住、有收益 | 审核队列、数据统计、二维码、群聊管理、佣金明细分账 |
| 管理员 | 全局可控、出事能查 | 仪表盘、内容审核、墙主申请、提现审核、通知推送、协议管理 |

闭环是这样的：

```
投稿 → 墙主审核（通过 / 驳回+理由） → 发布到墙 → 浏览 · 点赞 · 评论
                                              ↓
                                        墙主获得佣金
                                              ↓
                                     申请提现 → 管理员审核 → 到账
```

每一步都有实时反馈：审核结果、点赞、评论通过 WebSocket 推给用户，**同时落库**——用户离线时消息不丢，下次打开拉历史。

---

## 架构：三模块 + 双通道

```
┌───────────────────────────────────────────────────────┐
│                      客户端层                          │
│  ┌──────────────────┐   ┌───────────────────────────┐ │
│  │ UniApp 小程序端    │   │ Vue 3 + Element Plus 后台 │ │
│  │ Vue 2 + uView     │   │ Vite 5 + Pinia + WangEditor│ │
│  └────────┬─────────┘   └────────────┬──────────────┘ │
└───────────┼──────────────────────────┼────────────────┘
            │ HTTP/JSON                │ HTTP/JSON
┌───────────▼──────────────────────────▼────────────────┐
│                  ThinkPHP 5.1（PHP ≥ 8.0）             │
│  ┌──────────┬────────────┬───────────────────────┐    │
│  │ API 模块  │ Admin 模块  │ WSS 模块              │    │
│  │ 9 控制器  │ 13 控制器   │ GatewayWorker 三进程   │    │
│  │ 80+ 接口  │ RBAC       │ UID 绑定 + 心跳        │    │
│  └────┬─────┴─────┬──────┴──────────┬────────────┘    │
│  ┌────▼───────────▼─────────────────▼────────────┐    │
│  │ Service：微信支付 · 微信登录/消息 · DFA 敏感词   │    │
│  └────┬──────────────────────────────────────────┘    │
└───────┼───────────────────────────────────────────────┘
        ▼
   MySQL（19 张表 · utf8mb4 支持 emoji）
```

**为什么 WebSocket 要独立进程**：ThinkPHP 是请求-响应模型，长连接塞进去会互相拖死。GatewayWorker 单独跑，业务层通过 `GatewayClient` 投递，HTTP 与推送彻底解耦。

---

## 核心能力

| 能力 | 实现要点 |
| --- | --- |
| 投稿 | 文字 / 图片 / 标签 / 背景色，提交前预览；状态机：待审 → 通过 / 驳回（带理由） |
| 审核 | 墙主审本墙、管理员审全站；DFA 敏感词树 O(n) 命中，万级词库毫秒级 |
| 评论 | 帖子评论 + 回复 + 审核 + 管理，支持折叠与清理 |
| 实时通知 | 审核结果 / 点赞 / 评论走 WebSocket；同时写 `notification` 表保证离线可达 |
| 墙主体系 | 申请 → 审核 → 后台（数据统计、审核、二维码、群聊管理、频道管理） |
| 佣金提现 | 佣金明细 → 微信支付 → 提现申请 → 管理员审核 → 记录留痕 |
| 海报生成 | Canvas 动态生成投稿海报，自定义背景色 / 标签 / 多图排版，一键存相册 |
| 管理后台 | 仪表盘、用户、投稿、评论、墙主申请、提现、通知、教程、协议、频道、社区 |

---

## 几个关键难点

**WebSocket 与 ThinkPHP 的集成**
长连接交给 GatewayWorker，业务层只负责「往哪个 uid 发什么」。推送失败不影响主流程，消息已入库，用户上线拉回来。

**微信支付 JSSDK 签名**
签名时效性极强。后端统一管理 `prepay_id` 与签名生成，前端只负责调起——前端碰签名就是给自己埋雷。

**Canvas 海报跨端兼容**
小程序 / H5 / App 的 Canvas API 各有差异。封装 `poster-capture.js` 统一差异，输出统一为图片，调用方只关心「画什么」。

**敏感词要快**
DFA 建树，匹配从 O(n·m) 降到 O(n)。这是内容平台的入场券，不是加分项。

**审核要即时，消息不能丢**
状态变更即推 + 落库双写。牺牲一点存储换「用户永远不会问『我的投稿呢』」。

---

## 快速开始

**环境**：PHP ≥ 8.0 · MySQL 5.7+ · Redis · Node 18+ · Composer

```bash
git clone https://github.com/edrin09/tougao.git
cd tougao

# 1. 后端 API（ThinkPHP 5.1）
cd api
composer install
cp config/database.example.php config/database.php   # 填数据库账号密码
cp config/wechat.example.php   config/wechat.php     # 填小程序 AppID / 商户号 / 支付密钥
# 公众号模板消息还需要 official_account（app_id / secret / token），不填则该功能直接报配置缺失
# 把支付证书放到 config/cert/，导入数据库结构，Nginx 指向 api/public
php think worker:gateway start                       # WebSocket 服务

# 2. 小程序
cd ../uniapp
npm install
npm run dev:mp-weixin           # 用微信开发者工具打开 dist 目录
# 首次构建前把 manifest.json 的 mp-weixin.appid 填成你自己的小程序 AppID

# 3. 管理后台
cd ../admin
npm install && npm run dev
```

> 微信支付、订阅消息、小程序码均需在公众平台配置对应权限；本地调试可先关掉支付相关路由。

---

## 技术栈

| 层级 | 选型 |
| --- | --- |
| 小程序 | UniApp + Vue 2 + uView UI（编译至微信小程序 / H5 / App） |
| 管理后台 | Vue 3 + Element Plus + Vite 5 + Pinia + Vue Router 4 + WangEditor |
| 后端 | ThinkPHP 5.1（PHP ≥ 8.0），API / Admin / WSS 三模块 |
| 实时 | Workerman GatewayWorker（三进程） |
| 微信 | EasyWeChat 5.x（登录 / 支付 / 订阅消息 / 小程序码） |
| 存储 | MySQL 8（utf8mb4）+ Redis 缓存 |
| 内容安全 | DFA 敏感词树 |
| 部署 | Docker + Nginx（admin-dist 静态分离） |

---

## 目录结构

```
api/          ThinkPHP 后端（application/api · admin · wss · service）
admin/        Vue 3 管理后台
uniapp/       小程序端（含 Canvas 海报、审核、墙主工作台）
原型.html      交互原型
项目介绍.md    完整产品与技术说明
```

---

## 适用与不适用

- ✅ 高校 / 园区 / 社区想做「有审核、能分账」的内容墙
- ✅ 想参考一套「小程序 + TP5 + GatewayWorker + 微信支付」的完整业务实现
- ❌ 想要开箱即用的 SaaS——这是一套需要自己部署与配置商户号的源码
- ❌ 想做匿名社交广场——审核与实名链路是这个项目的骨架，拆不掉

> 运营此类平台请遵守《网络安全法》《互联网信息服务管理办法》等法规，落实内容审核与留存义务。

---

## License

MIT。完整产品背景与接口清单见 `项目介绍.md`。

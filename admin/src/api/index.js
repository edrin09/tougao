import axios from 'axios';
import { ElMessage } from 'element-plus';
import { useAuthStore } from '../store/auth';

// 开发模式标识
const isDev = import.meta.env.DEV;

// 根据环境选择API基础地址
const baseURL = isDev
    ? 'http://localhost:3000/api'  // 开发环境使用代理
    // : 'https://tougao.njlinks.cn/api';  // 测试环境直接访问
    : 'https://tougao.lin-xin.com/api';  // 生产环境直接访问

const api = axios.create({
    baseURL,
    timeout: 10000,
});

// 请求拦截器 - 注入token
api.interceptors.request.use((config) => {
    const authStore = useAuthStore();
    if (authStore.token) {
        config.headers.Authorization = `Bearer ${authStore.token}`;
        // 兼容可能的其他token传递方式
        config.headers.token = authStore.token;
    }

    // 开发模式下打印请求信息
    if (isDev) {
        console.log(`🔄 API请求: ${config.method?.toUpperCase()} ${config.url}`, config.params || config.data);
    }

    return config;
});

// 响应拦截器 - 统一错误处理
api.interceptors.response.use(
    (response) => {
        const res = response.data;

        // 开发模式下打印响应信息
        if (isDev) {
            console.log(`✅ API响应: ${response.config.url}`, res);
        }

        // 兼容不同的响应格式
        if (res.code === 401 || res.code === 403) {
            const authStore = useAuthStore();
            authStore.logout();
            window.location.hash = '#/login';
            ElMessage.error('登录已过期，请重新登录');
            return Promise.reject(new Error('Unauthorized'));
        }
        return res;
    },
    (error) => {
        if (isDev) {
            console.error('❌ API错误:', error.config?.url, error);
        }

        if (error.response?.status === 401) {
            const authStore = useAuthStore();
            authStore.logout();
            window.location.hash = '#/login';
            ElMessage.error('登录已过期，请重新登录');
        } else if (error.code === 'ERR_NETWORK' || error.message === 'Network Error') {
            // 网络错误，不显示错误提示，让组件处理fallback
            console.warn('网络错误，将使用模拟数据');
        } else {
            ElMessage.error(error.response?.data?.message || error.message || '请求失败');
        }
        return Promise.reject(error);
    }
);

// 模拟数据工厂
const mockDataFactory = {
    // 仪表盘统计数据
    dashboardStats: () => ({
        code: 200,
        data: {
            userCount: 1256,
            postCount: 847,
            pendingApply: 12,
            commentCount: 2341
        },
        message: 'success'
    }),

    // 墙主申请列表
    wallMasterApplyList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    username: '张同学',
                    realName: '张三',
                    phone: '13800138001',
                    email: 'zhangsan@example.com',
                    reason: '我热爱校园文化建设，希望能为同学们提供更好的交流平台。有丰富的组织活动经验，曾担任学生会干部。',
                    status: 'pending',
                    applyTime: '2024-01-15 14:30:00',
                    avatar: ''
                },
                {
                    id: 2,
                    username: '李同学',
                    realName: '李四',
                    phone: '13800138002',
                    email: 'lisi@example.com',
                    reason: '作为计算机专业学生，具备良好的技术基础，能够协助维护和优化校园墙系统。',
                    status: 'approved',
                    applyTime: '2024-01-14 16:20:00',
                    avatar: ''
                }
            ],
            total: 2
        },
        message: 'success'
    }),

    // 投稿列表
    postList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    title: '寻找丢失的学生卡',
                    author: '张同学',
                    authorAvatar: '',
                    type: 'lost_found',
                    content: '昨天在图书馆三楼丢失了学生卡，卡号20210001，如有捡到请联系我，定有重谢！',
                    images: [],
                    status: 'pending',
                    createTime: '2024-01-15 14:30:00'
                },
                {
                    id: 2,
                    title: '出售二手教材 - 高等数学',
                    author: '李同学',
                    authorAvatar: '',
                    type: 'second_hand',
                    content: '大学一年级高等数学教材，同济版，9成新，原价80元现价40元，有需要的同学联系我。',
                    images: ['textbook1.jpg', 'textbook2.jpg'],
                    status: 'approved',
                    createTime: '2024-01-15 13:20:00'
                },
                {
                    id: 3,
                    title: '组队参加编程竞赛',
                    author: '王同学',
                    authorAvatar: '',
                    type: 'study',
                    content: '下个月有ACM编程竞赛，寻找队友一起参加，要求有一定的算法基础。',
                    images: [],
                    status: 'published',
                    createTime: '2024-01-15 12:10:00'
                }
            ],
            total: 3
        },
        message: 'success'
    }),

    // 评论列表
    commentList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    content: '这个信息很有用，谢谢分享！我也在找学生卡。',
                    author: '王同学',
                    authorAvatar: '',
                    postId: 1,
                    postTitle: '寻找丢失的学生卡',
                    replyTo: null,
                    status: 'pending',
                    createTime: '2024-01-15 15:30:00'
                },
                {
                    id: 2,
                    content: '价格还能再优惠点吗？可以当面交易。',
                    author: '赵同学',
                    authorAvatar: '',
                    postId: 2,
                    postTitle: '出售二手教材 - 高等数学',
                    replyTo: '李同学',
                    status: 'approved',
                    createTime: '2024-01-15 14:20:00',
                    parentComment: {
                        author: '李同学',
                        content: '大学一年级高等数学教材，同济版，9成新'
                    }
                }
            ],
            total: 2
        },
        message: 'success'
    }),

    // 通知列表 - 按原组件字段
    notificationList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    type: 1,
                    type_text: '系统通知',
                    content: '校园墙系统将于本周六晚上进行维护升级，预计耗时2小时，期间可能无法正常使用，请大家提前做好安排。',
                    created_at: '2024-01-15 10:00:00',
                    updated_at: '2024-01-15 10:00:00'
                },
                {
                    id: 2,
                    type: 2,
                    type_text: '活动通知',
                    content: '春季运动会报名正式开始啦！欢迎同学们积极参与，展现青春风采，报名截止到本月底。',
                    created_at: '2024-01-15 09:30:00',
                    updated_at: '2024-01-15 09:30:00'
                },
                {
                    id: 3,
                    type: 1,
                    type_text: '系统通知',
                    content: '为了提高校园墙的内容质量，我们新增了投稿审核机制，请大家理解配合。',
                    created_at: '2024-01-14 16:00:00',
                    updated_at: '2024-01-14 16:00:00'
                }
            ],
            total: 3
        },
        message: 'success'
    }),

    // 提现列表
    withdrawList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    applyId: 'WD20240115001',
                    nickname: '张同学',
                    avatar: '',
                    amount: '100.00',
                    type: 'wallet',
                    status: 0,
                    WithdrawTime: '2024-01-15 14:30:00',
                    remitTime: null,
                    remitImage: '',
                    isVip: 1,
                    isDefaultWallMaster: 1
                },
                {
                    id: 2,
                    applyId: 'WD20240115002',
                    nickname: '李同学',
                    avatar: '',
                    amount: '200.00',
                    type: 'transfer',
                    status: 2,
                    WithdrawTime: '2024-01-15 13:20:00',
                    remitTime: null,
                    remitImage: '',
                    isVip: 0,
                    isDefaultWallMaster: 0
                },
                {
                    id: 3,
                    applyId: 'WD20240115003',
                    nickname: '王同学',
                    avatar: '',
                    amount: '150.00',
                    type: 'transfer',
                    status: 3,
                    WithdrawTime: '2024-01-15 12:10:00',
                    remitTime: '2024-01-15 15:30:00',
                    remitImage: 'https://via.placeholder.com/200x200/409EFF/FFFFFF?text=Receipt',
                    isVip: 1,
                    isDefaultWallMaster: 0
                }
            ],
            total: 3
        },
        message: 'success'
    }),

    // 提现历史
    withdrawHistoryList: (params) => ({
        code: 200,
        data: {
            list: [
                {
                    id: 1,
                    amount: '100.00',
                    status: 3,
                    WithdrawTime: '2024-01-15 14:30:00',
                    remitTime: '2024-01-15 16:30:00',
                    remitImage: 'https://via.placeholder.com/200x200/67C23A/FFFFFF?text=Receipt1'
                },
                {
                    id: 2,
                    amount: '50.00',
                    status: 2,
                    WithdrawTime: '2024-01-14 10:20:00',
                    remitTime: null,
                    remitImage: ''
                }
            ],
            total: 2
        },
        message: 'success'
    })
};

// API包装函数，支持fallback
const apiWrapper = (apiCall, mockData) => {
    return async (...args) => {
        try {
            const result = await apiCall(...args);
            return result;
        } catch (error) {
            if (isDev && (error.code === 'ERR_NETWORK' || error.message === 'Network Error')) {
                console.warn('🔄 使用模拟数据:', mockData);
                return typeof mockData === 'function' ? mockData(...args) : mockData;
            }
            throw error;
        }
    };
};

// 图片上传工具函数
export const uploadImage = async (file, uploadUrl) => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('image', file); // 兼容不同的字段名

    try {
        const response = await api.post(uploadUrl, formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            },
            timeout: 30000 // 上传文件超时时间设置为30秒
        });
        return response;
    } catch (error) {
        console.error('图片上传失败:', error);
        throw error;
    }
};

// API接口封装
export const API = {
    baseApi: baseURL,

    // 仪表盘
    dashboard: {
        getStats: apiWrapper(
            () => api.get('/admin/dashboardStats'),
            mockDataFactory.dashboardStats
        ),
    },

    // 管理员认证
    admin: {
        login: (data) => api.post('/admin/login', data),
        logout: () => api.post('/admin/logout'),
        getProfile: () => api.get('/admin/profile'),
        config: () => api.get('/admin/config'),
        updatePassword: (data) => api.post('/admin/updatePassword', data),
    },

    // 墙主申请管理
    wallMasterApply: {
        getList: apiWrapper(
            (params) => api.get('/admin/wallMasterApplyList', { params }),
            mockDataFactory.wallMasterApplyList
        ),
        review: (data) => api.post('/admin/reviewWallMasterApply', data),
        getDetail: (id) => api.get('/admin/wallMasterApplyDetail', { params: { applyId: id } }),
        updateVipStatus: (data) => api.post('/admin/updateApplyVipStatus', data),
        setShareRatio: (data) => api.post('/admin/setShareRatio', data),
        setDefaultSharing: (data) => api.post('/admin/setShareRatio', data),//设置分成比例
        delete: (id) => api.post('/admin/deleteWallMasterApply', { applyId: id }),
        saveWallInfo: (data) => api.post('/admin/saveWallInfo', data),//设置墙社区
    },

    // 墙主管理
    wallMaster: {
        delete: (id) => api.post('/admin/deleteWallMasterApply', { applyId: id }),
    },

    // 投稿管理
    post: {
        getList: apiWrapper(
            (params) => api.get('/admin/post/list', { params }),
            mockDataFactory.postList
        ),
        review: (postId, data) => api.post(`/admin/post/review/${postId}`, data),
        batchReview: (data) => api.post('/admin/post/batch-review', data),
        delete: (postId) => api.post(`/admin/post/delete/${postId}`),
        getDetail: (postId) => api.get(`/admin/post/${postId}`),
        sensitiveWordFiltering: (data) => api.post(`/admin/post/sensitiveWordFiltering/`, data),
    },

    // 评论管理
    comment: {
        getList: apiWrapper(
            (params) => api.get('/admin/comment/list', { params }),
            mockDataFactory.commentList
        ),
        review: (commentId, data) => api.post(`/admin/comment/review/${commentId}`, data),
        approve: (commentId) => api.post(`/admin/comment/review/${commentId}`, { action: 'approve' }),
        reject: (commentId) => api.post(`/admin/comment/review/${commentId}`, { action: 'reject' }),
        delete: (commentId) => api.post(`/admin/comment/delete/${commentId}`),
        restore: (commentId) => api.post(`/admin/comment/restore/${commentId}`),
    },

    // 通知管理 - 按原组件API结构
    notificationManager: {
        getList: apiWrapper(
            (params) => api.get('/admin/notificationManager/list', { params }),
            (params) => ({
                code: 200,
                data: mockDataFactory.notificationList(params)
            })
        ),
        getStats: apiWrapper(
            () => api.get('/admin/notificationManager/stats'),
            () => ({
                code: 200,
                data: {
                    total: 156,
                    today_count: 12,
                    week_count: 48,
                    month_count: 156
                }
            })
        ),
        getTypeList: apiWrapper(
            () => api.get('/admin/notificationManager/typeList'),
            () => ({
                code: 200,
                data: [
                    { value: 1, label: '系统通知' },
                    { value: 2, label: '活动通知' }
                ]
            })
        ),
        getDetail: apiWrapper(
            (id) => api.get(`/admin/notificationManager/detail/${id}`),
            (id) => {
                const mockNotification = {
                    id: parseInt(id),
                    type: 1,
                    type_text: '系统通知',
                    content: '这是通知详情内容...',
                    created_at: '2024-01-15 10:00:00',
                    updated_at: '2024-01-15 10:00:00'
                };
                return { code: 200, data: mockNotification };
            }
        ),
        add: apiWrapper(
            (data) => api.post('/admin/notificationManager/add', data),
            (data) => ({ code: 200, msg: '添加成功' })
        ),
        update: apiWrapper(
            (id, data) => api.post(`/admin/notificationManager/update/${id}`, data),
            (id, data) => ({ code: 200, msg: '更新成功' })
        ),
        delete: apiWrapper(
            (id) => api.post(`/admin/notificationManager/delete/${id}`),
            (id) => ({ code: 200, msg: '删除成功' })
        ),
        batchDelete: apiWrapper(
            (data) => api.post('/admin/notificationManager/batchDelete', data),
            (data) => ({ code: 200, msg: `成功删除${data.ids.length}条通知` })
        ),
    },

    // 用户管理
    user: {
        getCommunityList: (params) => api.get('/admin/getCommunityList', { params }),
        getList: (params) => api.get('/admin/user/list', { params }),
        ban: (userId) => api.post(`/admin/user/${userId}/ban`),
        unban: (userId) => api.post(`/admin/user/${userId}/unban`),
        delete: (userId) => api.post('/admin/user/delete', { userId }),
    },

    // 使用教程
    tutorial: {
        get: () => api.get('/admin/tutorial/get'),
        save: (data) => api.post('/admin/tutorial/save', data)
    },

    // 协议管理
    agreement: {
        get: (params) => api.get('/admin/agreement/get', { params }),
        save: (data) => api.post('/admin/agreement/save', data),
    },

    // 提现管理
    Withdraw: {
        // 获取提现列表
        getList: apiWrapper(
            (params) => api.get('/admin/withdraw/list', { params }),
            mockDataFactory.withdrawList
        ),

        // 获取提现历史
        historyList: apiWrapper(
            (data) => api.post('/admin/withdraw/history', data),
            mockDataFactory.withdrawHistoryList
        ),

        // 审核提现申请
        review: apiWrapper(
            (data) => api.post('/admin/withdraw/review', data),
            (data) => ({ code: 200, message: '审核成功' })
        ),

        // 上传打款凭证
        uploadVoucher: apiWrapper(
            (data) => api.post('/admin/withdraw/uploadVoucher', data),
            (data) => ({
                code: 200,
                message: '打款凭证上传成功',
                data: {
                    id: data.id,
                    remitImage: data.remitImage,
                    remitTime: data.remitTime
                }
            })
        ),

        // 更新提现状态
        updateStatus: apiWrapper(
            (data) => api.post('/admin/withdraw/updateStatus', data),
            (data) => ({ code: 200, message: '状态更新成功' })
        )
    },

    // 墙列表
    level: {
        getList: (params) => api.get('/admin/wallList', { params }),
    },

    // 文件上传相关
    upload: {
        // 上传敏感词 的单独接口
        sensitive: (data) => api.post('/admin/sensitive', { data }),

        // 上传打款凭证专用方法
        voucherImage: (file) => {
            const uploadUrl = `${import.meta.env.VITE_BASE_API || '/qiang/public/index.php/api'}/upload/image`;
            return uploadImage(file, uploadUrl);
        },

        //通用图片上传
        image: (file) => {
            const uploadUrl = `${import.meta.env.VITE_BASE_API || '/qiang/public/index.php/api'}/upload/image`;
            return uploadImage(file, uploadUrl);
        }
    },
    // 社区管理
    communityManager: {
        getList: apiWrapper(
            (params) => api.get('/admin/community/list', { params }),
            (params) => ({
                code: 200,
                data: mockDataFactory.communityList(params)
            })
        ),
        updateStatus: apiWrapper(
            (data) => api.post('/admin/community/update_status', data),
            (data) => ({ code: 200, msg: '状态更新成功' })
        ),
        add: apiWrapper(
            (data) => api.post('/admin/community/add', data),
            (data) => ({ code: 200, msg: '添加成功' })
        ),
        update: apiWrapper(
            (id, data) => api.post(`/admin/community/update/community_id/${id}`, data),
            (id, data) => ({ code: 200, msg: '更新成功' })
        ),
    },

    // 渠道管理
    channelsManager: {
        getList: apiWrapper(
            (params) => api.get('/admin/channels/list', { params }),
            (params) => ({
                code: 200,
                data: mockDataFactory.channelsList(params)
            })
        ),
        updateStatus: apiWrapper(
            (data) => api.post('/admin/channels/update_status', data),
            (data) => ({ code: 200, msg: '状态更新成功' })
        ),
        add: apiWrapper(
            (data) => api.post('/admin/channels/add', data),
            (data) => ({ code: 200, msg: '添加成功' })
        ),
        update: apiWrapper(
            (id, data) => api.post(`/admin/channels/update/channels_id/${id}`, data),
            (id, data) => ({ code: 200, msg: '更新成功' })
        ),
    },
};

export default api;
<?php

namespace app\admin\controller;

use app\common\helper\DateHelper;
use app\common\model\Admin;
use app\common\model\AdminLog;
use app\common\model\Comment;
use app\common\model\Post;
use app\common\model\User;
use app\common\model\Wall;
use app\common\model\WallMasterApply;
use app\common\model\WallSettings;
use app\common\model\WallToUser;
use app\common\service\WechatService;
use Exception;
use think\Controller;
use think\Db;
use think\Request;
use think\response\Json;

/**
 * 管理系统接口控制器
 * Class Manager
 * @package app\admin\controller
 */
class Manager extends Controller
{
    protected $middleware = [
        'AdminAuth' => [
            'except' => ['login', 'resetPassword']
        ]
    ];

    /**
     * 管理员登录
     * @param Request $request
     * @return Json
     */
    public function login(Request $request)
    {
        $username = $request->post('username');
        $password = $request->post('password');


        if (empty($username) || empty($password)) {
            return json(['code' => 400, 'message' => '用户名和密码不能为空']);
        }

        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            return json(['code' => 401, 'message' => '用户名或密码错误']);
        }

        if (!$admin->checkPassword($password)) {
            return json(['code' => 401, 'message' => '用户名或密码错误']);
        }

        // 生成token
        $token = $this->generateToken($admin['id']);

        // 更新登录时间
        $admin->save([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip' => $request->ip()
        ]);

        // 记录登录日志
        AdminLog::record('登录系统', '管理员登录', $admin['id']);

        // 返回用户信息（去除敏感字段）
        $adminData = $admin->toArray();
        unset($adminData['password']);

        // 设置session
        session('admin', [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'role' => $admin['role']
        ]);

        return json([
            'code' => 200,
            'message' => '登录成功',
            'data' => [
                'token' => $token,
                'admin' => $adminData
            ]
        ]);
    }

    /**
     * 生成token
     * @param int $adminId 管理员ID
     * @return string
     */
    private function generateToken($adminId)
    {
        $time = time();
        $expireTime = $time + 86400; // 24小时过期

        $tokenData = [
            'admin_id' => $adminId,
            'create_time' => $time,
            'expire_time' => $expireTime
        ];

        // 使用JWT或其他方式生成token
        // 这里简单使用加密方式
        $token = md5(json_encode($tokenData) . config('app.auth_key', 'campus_wall_admin'));

        // 先清除旧token
        cache('admin_token_' . $adminId, null);

        // 保存token到缓存
        cache('admin_token_' . $adminId, $token, 86400);

        // 打印调试信息
        trace('生成管理员Token: adminId=' . $adminId . ', token=' . $token, 'info');

        return $token;
    }

    /**
     * 获取仪表盘统计数据
     * @param Request $request
     * @return Json
     */
    public function dashboardStats(Request $request)
    {
        // 用户总数（不计已删除）
        $userCount = User::count();

        // 已通过投稿总数（status = 1）
        $postCount = Post::where('status', 1)->count();

        // 待审核墙主申请（status = 0）
        $pendingApply = WallMasterApply::where('status', 0)->count();

        // 已通过评论总数（status = 1）
        $commentCount = Comment::where('status', 1)->count();

        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'userCount' => $userCount,
                'postCount' => $postCount,
                'pendingApply' => $pendingApply,
                'commentCount' => $commentCount
            ]
        ]);
    }

    /**
     * 获取近期活动记录
     * @param Request $request
     * @return Json
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->param('limit', 10);

        try {
            // 使用模型关联查询
            $logs = AdminLog::with(['admin'])
                ->field('*, admin.username, admin.nickname')
                ->order('id desc')
                ->limit($limit)
                ->select();

            $activities = [];
            foreach ($logs as $log) {
                $activities[] = [
                    'time' => $log['create_time'],
                    'user' => $log->admin ? ($log->admin->nickname ?: $log->admin->username) : '未知用户',
                    'action' => $log['action'],
                    'details' => $log['details']
                ];
            }

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'activities' => $activities
                ]
            ]);
        } catch (Exception $e) {
            // 记录错误
            trace('[Manager] 获取活动记录失败: ' . $e->getMessage(), 'error');

            return json([
                'code' => 500,
                'message' => '获取活动记录失败',
                'data' => [
                    'activities' => []
                ]
            ]);
        }
    }

    /**
     * 获取墙主申请列表
     * @param Request $request
     * @return Json
     */
    public function wallMasterApplyList(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 10);
        $status = $request->param('status');

        $query = WallMasterApply::with(['user' => function ($query) {
            $query->field('user_id,nickname,avatar');
        }]);

        // 如果传入了状态，则按状态筛选
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        $total = $query->count();
        $list = $query->order('id desc')
            ->page($page, $size)
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = [
                'applyId' => $item['id'],
                'userId' => $item['user_id'],
                'openid' => $item['openid'],
                'nickname' => isset($item['user']) ? $item['user']['nickname'] : '',
                'avatar' => isset($item['user']) ? $item['user']['avatar'] : '',
                'status' => $item['status'],
                'isVip' => $item['is_vip'],
                'isDefaultWallMaster' => isset($item['is_default_wall_master']) ? $item['is_default_wall_master'] : 0,
                'applyTime' => $item['apply_time'],
                'createTime' => $item['create_time'],
                'reviewTime' => $item['review_time'],
                'updateTime' => $item['update_time']
            ];
        }

        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'total' => $total,
                'list' => $result,
                'page' => $page,
                'pageSize' => $size
            ]
        ]);
    }

    /**
     * 获取墙主申请详情
     * @param Request $request
     * @return Json
     */
    public function wallMasterApplyDetail(Request $request)
    {
        $applyId = $request->param('applyId');

        if (empty($applyId)) {
            return json(['code' => 400, 'message' => '申请ID不能为空']);
        }

        $apply = WallMasterApply::with(['user' => function ($query) {
            $query->field('user_id,nickname,avatar,is_vip');
        }])->where('id', $applyId)->find();

        if (!$apply) {
            return json(['code' => 404, 'message' => '申请不存在']);
        }

        $wallSettings = new WallSettings();
        $wallOwner = $wallSettings->getWallSettings($apply->user_id);
        if (!$wallOwner) {
            return json(['code' => 404, 'message' => '墙主不存在']);
        }

        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'applyDetail' => [
                    'applyId' => $apply['id'],
                    'userId' => $apply['user_id'],
                    'openid' => $apply['openid'],
                    'nickname' => isset($apply['user']) ? $apply['user']['nickname'] : '',
                    'avatar' => isset($apply['user']) ? $apply['user']['avatar'] : '',
                    'isVip' => $apply['is_vip'] ?? (isset($apply['user']) ? $apply['user']['is_vip'] : 0) ?? 0,
                    'status' => $apply['status'],
                    'applyTime' => isset($apply['apply_time']) ? date('Y-m-d H:i:s', strtotime($apply['apply_time'])) : date('Y-m-d H:i:s', strtotime($apply['create_time'])),
                    'reviewTime' => $apply['review_time'] ? date('Y-m-d H:i:s', strtotime($apply['review_time'])) : '',
                    'reviewComment' => $apply['review_comment'],
                    'isDefaultWallMaster' => isset($apply['is_default_wall_master']) ? $apply['is_default_wall_master'] : 0,
                    'shareRatio' => $wallOwner['share_ratio'],
                    'community_id' => $wallOwner['community_id']
                ]
            ]
        ]);
    }

    /**
     * 审核墙主申请
     * @param Request $request
     * @return Json
     */
    public function reviewWallMasterApply(Request $request)
    {
        $applyId = $request->post('applyId');
        $status = $request->post('status');
        $comment = $request->post('comment');

        if (empty($applyId)) {
            return json(['code' => 400, 'message' => '申请ID不能为空']);
        }

        if (!in_array($status, [1, -1])) {
            return json(['code' => 400, 'message' => '状态参数错误']);
        }

        $apply = WallMasterApply::get($applyId);
        if (!$apply) {
            return json(['code' => 404, 'message' => '申请不存在']);
        }

        if ($apply->status != 0) {
            return json(['code' => 400, 'message' => '该申请已审核，无法重复审核']);
        }

        // 获取当前管理员ID
        $adminId = session('admin.id');
        if (!$adminId) {
            return json(['code' => 403, 'message' => '请先登录']);
        }

        Db::startTrans();
        try {
            // 更新申请状态
            $apply->status = $status;
            $apply->review_time = date('Y-m-d H:i:s');
            $apply->review_comment = $comment;
            $apply->review_admin_id = $adminId;
            $apply->save();

            // 如果通过，创建墙设置
            if ($status == 1) {
                // 获取用户信息
                $userModel = new User();
                $user = $userModel->where('user_id', $apply->user_id)->find();
                $wallName = $user['nickname'];
                // 墙主头像默认为用户头像
                $wallAvatar = $user['avatar'];

                // 创建墙设置记录
                $wallSettingsModel = new WallSettings();
                $wall_id = $wallSettingsModel->generateDefaultSettings($apply->user_id, $wallName, $apply->is_vip, $wallAvatar);

                // 更新用户为墙主和VIP状态
                $user->is_wall_master = 1;
                $user->is_vip = $apply->is_vip;
                $user->update_time = date('Y-m-d H:i:s');
                $user->wall_id = $wall_id;
                $user->save();

                WallToUser::where('user_id', $apply->user_id)->delete();
                WallToUser::create([
                    'wall_id' => $wall_id,
                    'user_id' => $apply->user_id,
                    'is_wall_master' => 1,
                ]);
            }

            // 记录操作日志
            $actionText = $status == 1 ? '通过' : '拒绝';
            AdminLog::record(
                '墙主申请审核',
                $actionText . '了用户ID:' . $apply->user_id . '的墙主申请，申请ID:' . $applyId,
                $adminId
            );

            Db::commit();
            return json(['code' => 200, 'message' => '审核成功']);

        } catch (Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '审核失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 获取墙列表
     * @param Request $request
     * @return Json
     */
    public function wallList(Request $request)
    {
        try {
            // 从wall_settings表获取墙数据
            $wallSettings = Db::name('cw_wall_settings')
                ->alias('ws')
                ->join('user u', 'ws.wall_owner_id = u.user_id', 'LEFT')
                ->field('ws.id, ws.wall_name, ws.wall_owner_id, u.nickname, ws.create_time')
                ->order('ws.id desc')
                ->select();

            $result = [];
            foreach ($wallSettings as $wall) {
                $result[] = [
                    'wallId' => $wall['id'],
                    'wallName' => $wall['wall_name'],
                    'schoolName' => '', // 从wall_settings中没有学校名称
                    'creatorName' => $wall['nickname'],
                    'creatorId' => $wall['wall_owner_id'], // 添加墙主用户ID
                    'createTime' => $wall['create_time'],
                    'status' => 1 // 默认状态为正常
                ];
            }

            return json([
                'code' => 200,
                'message' => '获取成功',
                'data' => [
                    'list' => $result
                ]
            ]);
        } catch (Exception $e) {
            trace('[Manager] 获取墙列表失败: ' . $e->getMessage(), 'error');
            return json([
                'code' => 500,
                'message' => '获取墙列表失败: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * 获取投稿列表
     * @param Request $request
     * @return Json
     */
    public function postList(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 10);
        $status = $request->param('status');
        $wallOwnerId = $request->param('wallOwnerId');
        $keyword = $request->param('keyword');

        $query = Post::with([
            'user' => function ($query) {
                $query->field('user_id,nickname');
            },
            'wall' => function ($query) {
                $query->field('wall_owner_id,wall_name');
            }
        ]);

        // 筛选条件
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where('status', $status);
        }

        if ($wallOwnerId && $wallOwnerId !== 'all') {
            $query->where('wall_owner_id', $wallOwnerId);
        }

        if ($keyword) {
            $query->where('title|content', 'like', "%{$keyword}%");
        }

        $total = $query->count();
        $list = $query->order('create_time desc')
            ->page($page, $size)
            ->select()
            ->toArray();

        $result = [];
        foreach ($list as $post) {
            $result[] = [
                'postId' => $post['post_id'],
                'title' => $post['title'],
                'wallOwnerId' => $post['wall_owner_id'],
                'wallName' => $post['wall']['wall_name'],
                'userId' => $post['user_id'],
                'nickname' => $post['user']['nickname'],
                'status' => $post['status'],
                'postTime' => $post['create_time'],
                'viewCount' => $post['view_count'],
                'likeCount' => $post['like_count'],
                'commentCount' => $post['comment_count']
            ];
        }

        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'total' => $total,
                'list' => $result,
                'page' => $page,
                'pageSize' => $size
            ]
        ]);
    }

    /**
     * 获取投稿详情
     * @param Request $request
     * @return Json
     */
    public function postDetail(Request $request)
    {
        $postId = $request->param('postId');

        if (empty($postId)) {
            return json(['code' => 400, 'message' => '投稿ID不能为空']);
        }

        $post = Post::with([
            'user' => function ($query) {
                $query->field('user_id,nickname');
            },
            'wall' => function ($query) {
                $query->field('id,name');
            }
        ])->where('id', $postId)->find();

        if (!$post) {
            return json(['code' => 404, 'message' => '投稿不存在']);
        }

        return json([
            'code' => 200,
            'message' => '获取成功',
            'data' => [
                'postDetail' => [
                    'postId' => $post['id'],
                    'title' => $post['title'],
                    'content' => $post['content'],
                    'imageUrls' => $post['image_urls'],
                    'wallId' => $post['wall_id'],
                    'wallName' => $post['wall']['name'],
                    'userId' => $post['user_id'],
                    'nickname' => $post['user']['nickname'],
                    'status' => $post['status'],
                    'postTime' => date('Y-m-d H:i:s', strtotime($post['create_time'])),
                    'reviewTime' => $post['review_time'] ? date('Y-m-d H:i:s', strtotime($post['review_time'])) : '',
                    'reviewComment' => $post['review_comment'],
                    'publishTime' => $post['publish_time'] ? date('Y-m-d H:i:s', strtotime($post['publish_time'])) : ''
                ]
            ]
        ]);
    }

    /**
     * 审核投稿
     * @param Request $request
     * @return Json
     */
    public function reviewPost(Request $request)
    {
        $postId = $request->post('postId');
        $status = $request->post('status');
        $comment = $request->post('comment');

        if (empty($postId)) {
            return json(['code' => 400, 'message' => '投稿ID不能为空']);
        }

        if (!in_array($status, [1, -1])) {
            return json(['code' => 400, 'message' => '状态参数错误']);
        }

        $post = Post::get($postId);
        if (!$post) {
            return json(['code' => 404, 'message' => '投稿不存在']);
        }

        if ($post->status != 0) {
            return json(['code' => 400, 'message' => '该投稿已审核，无法重复审核']);
        }

        // 更新投稿状态
        $post->status = $status;
        $post->review_time = DateHelper::now();
        $post->review_comment = $comment;
        $post->review_admin_id = $request->adminId;

        if ($post->save()) {
            // 记录操作日志
            $actionText = $status == 1 ? '通过' : '拒绝';
            AdminLog::record(
                '投稿审核',
                $actionText . '了标题为"' . $post->title . '"的投稿，投稿ID:' . $postId,
                $request->adminId
            );

            return json(['code' => 200, 'message' => '审核成功']);
        } else {
            return json(['code' => 500, 'message' => '审核失败']);
        }
    }

    /**
     * 发布投稿
     * @param Request $request
     * @return Json
     */
    public function publishPost(Request $request)
    {
        $postId = $request->post('postId');

        if (empty($postId)) {
            return json(['code' => 400, 'message' => '投稿ID不能为空']);
        }

        $post = Post::get($postId);
        if (!$post) {
            return json(['code' => 404, 'message' => '投稿不存在']);
        }

        if ($post->status != 1) {
            return json(['code' => 400, 'message' => '只有已通过审核的投稿才能发布']);
        }

        if ($post->status == 2) {
            return json(['code' => 400, 'message' => '该投稿已发布']);
        }

        // 更新投稿状态
        $post->status = 2;
        $post->publish_time = DateHelper::now();
        $post->publisher_id = $request->adminId;

        if ($post->save()) {
            // 记录操作日志
            AdminLog::record(
                '投稿发布',
                '发布了标题为"' . $post->title . '"的投稿，投稿ID:' . $postId,
                $request->adminId
            );

            return json(['code' => 200, 'message' => '发布成功']);
        } else {
            return json(['code' => 500, 'message' => '发布失败']);
        }
    }

    /**
     * 测试接口 - 检查认证状态
     * @param Request $request
     * @return Json
     */
    public function checkAuth(Request $request)
    {
        return json([
            'code' => 200,
            'message' => '认证成功',
            'data' => [
                'adminId' => $request->adminId,
                'time' => date('Y-m-d H:i:s'),
                'info' => '如果您能看到此消息，说明权限验证已通过'
            ]
        ]);
    }

    /**
     * 更新墙主状态
     * @param Request $request
     * @return Json
     */
    public function updateWallMasterStatus(Request $request)
    {
        $userId = $request->post('userId');
        $status = $request->post('status');
        $reason = $request->post('reason', '');

        if (empty($userId)) {
            return json(['code' => 400, 'message' => '用户ID不能为空']);
        }

        if ($status != '0' && $status != '1') {
            return json(['code' => 400, 'message' => '状态值无效']);
        }

        // 如果是取消墙主资格，需要提供原因
        if ($status == '0' && empty($reason)) {
            return json(['code' => 400, 'message' => '取消墙主资格需要提供原因']);
        }

        Db::startTrans();
        try {
            // 获取用户信息
            $user = Db::name('user')->where('user_id', $userId)->find();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            // 更新用户的墙主状态
            Db::name('user')->where('user_id', $userId)->update([
                'is_wall_master' => intval($status),
                'update_time' => date('Y-m-d H:i:s')
            ]);

            // 获取该用户的墙设置
            $wallSettings = Db::name('cw_wall_settings')
                ->where('wall_owner_id', $userId)
                ->select();

            // 更新所有墙的状态
            foreach ($wallSettings as $wall) {
                Db::name('cw_wall_settings')
                    ->where('id', $wall['id'])
                    ->update([
                        'status' => intval($status),
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }

            // 记录操作日志
            $action = $status == '1' ? '恢复墙主资格' : '取消墙主资格';
            $logDetail = $status == '1' ?
                sprintf('恢复了用户ID:%d 的墙主资格', $userId) :
                sprintf('取消了用户ID:%d 的墙主资格，原因：%s', $userId, $reason);

            AdminLog::record($action, $logDetail, $request->adminId);

            Db::commit();
            return json([
                'code' => 200,
                'message' => '更新成功',
                'data' => [
                    'affectedWalls' => count($wallSettings)
                ]
            ]);

        } catch (Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '操作失败：' . $e->getMessage()]);
        }
    }

    public function cancelWallMaster(Request $request)
    {
        $userId = $request->post('userId');
        $reason = $request->post('reason');

        if (empty($userId)) {
            return json(['code' => 400, 'message' => '用户ID不能为空']);
        }

        Db::startTrans();
        try {
            // 获取用户信息
            $user = Db::name('user')->where('user_id', $userId)->find();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            if (!$user['is_wall_master']) {
                throw new Exception('该用户不是墙主');
            }

            // 更新用户的墙主状态
            Db::name('user')->where('user_id', $userId)->update([
                'is_wall_master' => 0,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            // 获取该用户的墙设置
            $wallSettings = Db::name('cw_wall_settings')
                ->where('wall_owner_id', $userId)
                ->where('status', 1)
                ->select();

            // 更新所有墙的状态为已停用
            foreach ($wallSettings as $wall) {
                Db::name('cw_wall_settings')
                    ->where('id', $wall['id'])
                    ->update([
                        'status' => 0,
                        'update_time' => date('Y-m-d H:i:s')
                    ]);
            }

            // 记录操作日志
            AdminLog::record(
                '取消墙主资格',
                sprintf('取消了用户ID:%d 的墙主资格，原因：%s', $userId, $reason),
                $request->adminId
            );

            Db::commit();
            return json([
                'code' => 200,
                'message' => '已取消墙主资格',
                'data' => [
                    'affectedWalls' => count($wallSettings)
                ]
            ]);

        } catch (Exception $e) {
            Db::rollback();
            return json([
                'code' => 500,
                'message' => '操作失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 重置密码
     * @param Request $request
     * @return Json
     */
    public function resetPassword(Request $request)
    {
        $username = $request->post('username');
        $newPassword = $request->post('newPassword');
        $confirmPassword = $request->post('confirmPassword');

        // 参数验证
        if (empty($username) || empty($newPassword) || empty($confirmPassword)) {
            return json(['code' => 400, 'message' => '请填写所有必填项']);
        }

        if ($newPassword !== $confirmPassword) {
            return json(['code' => 400, 'message' => '两次输入的密码不一致']);
        }

        // 查找管理员
        $admin = Admin::where('username', $username)->find();
        if (!$admin) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        try {
            // 更新密码
            $admin->password = md5($newPassword);
            $admin->save();

            // 记录操作日志
            AdminLog::create([
                'admin_id' => $admin->id,
                'username' => $admin->username,
                'action' => '重置密码',
                'details' => '管理员重置了密码',
                'ip' => $request->ip(),
                'user_agent' => $request->header('user-agent'),
                'create_time' => date('Y-m-d H:i:s')
            ]);

            return json(['code' => 200, 'message' => '密码重置成功']);
        } catch (Exception $e) {
            return json(['code' => 500, 'message' => '密码重置失败：' . $e->getMessage()]);
        }
    }

    /**
     * 取消墙主资格
     * @param Request $request
     * @return Json
     */

    /**
     * 设置默认墙主（管理后台接口）
     * @param Request $request
     * @return Json
     */
//    public function setDefaultWallMaster(Request $request)
//    {
//        $applyId = $request->param('apply_id');
//        $userId = $request->param('user_id'); // 兼容旧接口
//
//        if (empty($applyId) && empty($userId)) {
//            return json(['code' => 400, 'message' => '申请ID或用户ID不能为空']);
//        }
//
//        try {
//            $applyModel = new WallMasterApply();
//
//            // 如果传入的是用户ID，需要找到对应的申请ID
//            if (!empty($userId) && empty($applyId)) {
//                $apply = $applyModel->where('user_id', $userId)
//                    ->where('status', 1)
//                    ->find();
//                if (!$apply) {
//                    return json(['code' => 404, 'message' => '该用户没有通过审核的墙主申请']);
//                }
//                $applyId = $apply['id'];
//            }
//
//            // 检查申请是否存在且已通过审核
//            $apply = $applyModel->where('id', $applyId)
//                ->where('status', 1)
//                ->with('user')
//                ->find();
//
//            if (!$apply) {
//                return json(['code' => 404, 'message' => '申请不存在或未通过审核']);
//            }
//
//            // 设置为默认墙主
//            $result = $applyModel->setDefaultWallMasterApply($applyId);
//
//            if ($result) {
//                // 记录操作日志
//                $adminId = session('admin.id');
//                AdminLog::record(
//                    '设置默认墙主',
//                    '将申请ID:' . $applyId . '(用户:' . (isset($apply['user']) ? $apply['user']['nickname'] : '未知用户') . ')设置为默认墙主',
//                    $adminId
//                );
//
//                return json([
//                    'code' => 200,
//                    'message' => '设置默认墙主成功',
//                    'data' => [
//                        'apply_id' => $apply['id'],
//                        'user_id' => $apply['user_id'],
//                        'nickname' => isset($apply['user']) ? $apply['user']['nickname'] : '',
//                        'is_default_wall_master' => 1
//                    ]
//                ]);
//            } else {
//                return json(['code' => 500, 'message' => '设置默认墙主失败']);
//            }
//
//        } catch (Exception $e) {
//            return json(['code' => 500, 'message' => '设置默认墙主失败: ' . $e->getMessage()]);
//        }
//    }

    /**
     * 获取默认墙主信息（管理后台接口）
     * @param Request $request
     * @return Json
     */
//    public function getDefaultWallMaster(Request $request)
//    {
//        try {
//            $applyModel = new WallMasterApply();
//            $defaultApply = $applyModel->getDefaultWallMasterApply();
//
//            if (!$defaultApply) {
//                return json(['code' => 404, 'message' => '暂无默认墙主']);
//            }
//
//            $wallMasterInfo = [
//                'apply_id' => $defaultApply['id'],
//                'user_id' => $defaultApply['user_id'],
//                'nickname' => isset($defaultApply['user']) ? $defaultApply['user']['nickname'] : '默认墙主',
//                'avatar' => isset($defaultApply['user']) ? $defaultApply['user']['avatar'] : '',
//                'is_wall_master' => 1,
//                'is_default_wall_master' => isset($defaultApply['is_default_wall_master']) ? $defaultApply['is_default_wall_master'] : 1,
//                'is_vip' => isset($defaultApply['is_vip']) ? $defaultApply['is_vip'] : 0,
//                'status' => $defaultApply['status'],
//                'create_time' => $defaultApply['create_time']
//            ];
//
//            return json([
//                'code' => 200,
//                'message' => '获取默认墙主成功',
//                'data' => $wallMasterInfo
//            ]);
//
//        } catch (Exception $e) {
//            return json(['code' => 500, 'message' => '获取默认墙主失败: ' . $e->getMessage()]);
//        }
//    }

    /**
     * 删除墙主申请
     * @param Request $request
     * @return Json
     */
    public function deleteWallMasterApply(Request $request)
    {
        $applyId = $request->post('applyId');
        if (empty($applyId)) {
            return json(['code' => 400, 'message' => '申请ID不能为空']);
        }
        $apply = WallMasterApply::get($applyId);
        if (!$apply) {
            return json(['code' => 404, 'message' => '申请不存在']);
        }
        $userId = $apply->user_id;

        $user = User::get($userId);
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }
        $isApproved = $apply->status == 1; // 是否已审核通过

        Db::startTrans();
        try {
            $apply->delete();

            // 如果是已审核通过的墙主申请，重置用户状态
            if ($isApproved) {
                $user->is_wall_master = 0;
                $user->is_vip = 0;
                $user->wall_id = 0;
                $user->save();

                $wall = WallSettings::where('wall_owner_id', $userId)->find();
                if ($wall) {
                    $wall->delete();

                    WallToUser::where('wall_id', $wall->id)->delete();
                }
            }

            // 记录操作日志
            $adminId = session('admin.id');
            $logDetails = '删除了ID为' . $applyId . '的墙主申请';
            if ($isApproved) {
                $logDetails .= '，同时取消了用户ID:' . $userId . '的墙主资格';
            }
            AdminLog::record('删除墙主申请', $logDetails, $adminId);

            Db::commit();
            return json(['code' => 200, 'message' => '删除成功']);

        } catch (Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '删除失败: ' . $e->getMessage()]);
        }
    }

    public function setShareRatio(Request $request)
    {
        $applyId = $request->param('apply_id', 0);
        $share_ratio = $request->param('share_ratio', 0); // 分成比例
        if ($share_ratio < 0 || $share_ratio > 100) {
            return json(['code' => 400, 'message' => '分成比例必须在0-100之间']);
        }
        if (empty($applyId)) {
            return json(['code' => 400, 'message' => '申请ID或用户ID不能为空']);
        }

        try {
            // 检查申请是否存在且已通过审核
            $apply = WallMasterApply::where('id', $applyId)
                ->where('status', 1)
                ->find();
            if (!$apply) {
                return json(['code' => 404, 'message' => '申请不存在或未通过审核']);
            }

            $wallSettings = new WallSettings();
            $wallOwner = $wallSettings->getWallSettings($apply->user_id);
            if (!$wallOwner) {
                return json(['code' => 404, 'message' => '墙主不存在']);
            }

            // 更新申请记录
            $wallOwner->share_ratio = $share_ratio;
            $wallOwner->save();

            // 记录操作日志
            $adminId = session('admin.id');
            AdminLog::record(
                '设置墙主分成比例',
                '将申请ID:' . $applyId . '(用户:' . (isset($apply['user']) ? $apply['user']['nickname'] : '未知用户') . ')设置分成比例',
                $adminId
            );

            return json([
                'code' => 200,
                'message' => '设置分成比例成功',
                'data' => [
                    'apply_id' => $apply['id'],
                    'user_id' => $apply['user_id'],
                ]
            ]);

        } catch (Exception $e) {
            return json(['code' => 500, 'message' => '设置分成比例失败: ' . $e->getMessage()]);
        }
    }

    /**
     * 生成二维码图片
     * @param string $url 要编码的URL
     * @param int $wallOwnerId 墙主用户ID
     * @return string 图片相对路径
     */
    private function generateQrCode($url, $wallOwnerId)
    {
        try {
            // 使用微信服务生成小程序二维码
            $wechatService = new WechatService();

            // 构建小程序页面路径和参数
            $page = config('wechat.publish_page') ?: 'pages/index/index'; // 使用配置文件中的publish_page
            $scene = 'wallOwnerId=' . $wallOwnerId; // 传递墙主用户ID参数

            // 尝试生成微信小程序二维码
            $qrCodeUrl = $wechatService->generateQRCode($page, $scene);

            if ($qrCodeUrl) {
                return $qrCodeUrl;
            }

            // 如果微信小程序二维码生成失败，使用普通URL二维码作为备选
            $fallbackQrCode = $wechatService->generateQRCodeByUrl($url);
            if ($fallbackQrCode) {
                return $fallbackQrCode;
            }

            // 最后备选：返回默认路径
            return '/static/images/default-qrcode.png';

        } catch (Exception $e) {
            trace('[Manager] 生成二维码失败: ' . $e->getMessage(), 'error');
            return '/static/images/default-qrcode.png';
        }
    }

    /**
     * 生成小程序短链，优先微信官方短链，失败则回退本地短码
     * @param int $wallOwnerId 墙主用户ID
     * @return string
     */
    private function generateMiniProgramShortLink($wallOwnerId)
    {
        $wechatService = new WechatService();
        $pageUrl = (config('wechat.publish_page') ?: 'pages/index/index') . '?wallOwnerId=' . $wallOwnerId;
        $title = '表白墙';
        $shortLink = $wechatService->generateWxShortLink($pageUrl, $title, true);
        if ($shortLink) {
            return $shortLink;
        }
        // 降级为本地短码
        $shortCode = $this->generateShortCode();
        // 这里可选：将本地短码存入数据库，或返回短码URL
        $domain = config('app.domain') ?: '';
        return $domain . '/s/' . $shortCode;
    }

    /**
     * 生成短链接代码
     * @return string
     */
    private function generateShortCode()
    {
        // 生成6位随机字符串
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < 6; $i++) {
            $code .= $chars[mt_rand(0, strlen($chars) - 1)];
        }

        // 检查是否已存在
        $exists = Db::name('cw_short_link')->where('short_code', $code)->find();
        if ($exists) {
            // 如果已存在则递归重新生成
            return $this->generateShortCode();
        }

        return $code;
    }
}
<?php

namespace app\admin\controller;

use app\common\model\AdminLog;
use app\common\model\User;
use app\common\model\WallChannels;
use Exception;
use think\Controller;
use think\Db;
use think\Request;
use think\response\Json;

/**
 * 用户管理控制器
 * Class UserManager
 * @package app\admin\controller
 */
class UserManager extends Controller
{
    protected $middleware = [
        'AdminAuth' => [
            'except' => []
        ]
    ];

    /**
     * 获取用户列表
     * @param Request $request
     * @return Json
     */
    public function getUserList(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 10);
        $keyword = $request->param('keyword', '');
        $isWallMaster = $request->param('is_wall_master', '');
        $isVip = $request->param('is_vip', '');

        $query = Db::name('cw_user');

        // 添加搜索条件
        if (!empty($keyword)) {
            $query->where('nickname|openid|user_id', 'like', "%{$keyword}%");
        }

        if ($isWallMaster !== '') {
            $query->where('is_wall_master', intval($isWallMaster));
        }

        if ($isVip !== '') {
            $query->where('is_vip', intval($isVip));
        }

        $total = $query->count();
        $list = $query->order('user_id desc')
            ->page($page, $size)
            ->select();

        $result = [];
        foreach ($list as $user) {
            $result[] = [
                'userId' => $user['user_id'],
                'openid' => $user['openid'],
                'nickname' => $user['nickname'],
                'avatar' => $user['avatar'],
                'isWallMaster' => $user['is_wall_master'],
                'isVip' => $user['is_vip'],
                'isDefaultWallMaster' => $user['is_default_wall_master'] ?? 0,
                'createTime' => $user['create_time'],
                'updateTime' => $user['update_time'],
                'lastLoginTime' => $user['last_login_time']
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
     * 删除用户
     * @param Request $request
     * @return Json
     */
    public function deleteUser(Request $request)
    {
        $userId = $request->post('userId');

        if (empty($userId)) {
            return json(['code' => 400, 'message' => '用户ID不能为空']);
        }

        Db::startTrans();
        try {
            // 获取用户信息
            $user = Db::name('cw_user')->where('user_id', $userId)->find();
            if (!$user) {
                return json(['code' => 404, 'message' => '用户不存在']);
            }

            // 删除用户关联的墙主申请
            Db::name('cw_wall_master_apply')->where('user_id', $userId)->delete();

            // 如果是墙主，处理相关数据
            if ($user['is_wall_master'] == 1) {
                // 获取该用户的墙设置并删除
                Db::name('cw_wall_settings')->where('wall_owner_id', $userId)->delete();

                // 可能还需要处理其他与墙主相关的数据
                // 例如删除墙主的帖子、评论等
            }

            // 删除用户
            Db::name('cw_user')->where('user_id', $userId)->delete();

            // 记录操作日志
            $adminId = session('admin.id');
            AdminLog::record(
                '删除用户',
                '删除了用户ID:' . $userId . '，昵称:' . $user['nickname'],
                $adminId
            );

            Db::commit();
            return json(['code' => 200, 'message' => '删除成功']);
        } catch (Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '删除失败: ' . $e->getMessage()]);
        }
    }

    public function getChannelsInfo(Request $request)
    {
        $user_id = $request->post('user_id', 0);
        $user = User::where('user_id', $user_id)->where('is_wall_master', 1)->find();
        if (!$user) {
            return json(['code' => 404, 'message' => '用户不存在']);
        }

        $list = WallChannels::where('wall_owner_id', $user_id)
            ->with(['channels' => function ($query) {
                $query->field('channels_id,channels_name');
            }])
            ->select();

        return json(['code' => 200, 'message' => '获取成功', 'data' => $list]);
    }
}

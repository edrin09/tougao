<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Community;
use app\common\model\WallSettings;
use app\common\model\WallToUser;
use Exception;
use think\Request;
use think\response\Json;

/**
 * 墙管理控制器
 */
class Wall extends AdminBase
{
    /**
     * 获取墙列表
     * @param Request $request
     * @return Json
     */
    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $status = $request->param('status', '');
        $is_vip = $request->param('is_vip', '');

        try {
            // 构建查询条件
            $where = [['user.is_wall_master', '=', 1]];

            // 状态筛选
            if ($status !== null && $status !== '') {
                $where[] = ['p.status', '=', $status];
            }
            if ($is_vip !== null && $is_vip !== '') {
                $where[] = ['p.is_vip', '=', $is_vip];
            }

            // 关键词搜索
            if (!empty($keyword)) {
                $where[] = ['p.wall_name', 'like', '%' . $keyword . '%'];
            }
            // 墙主ID筛选
            if (!empty($wallOwnerId)) {
                $where[] = ['p.wall_owner_id', 'like', '%' . $wallOwnerId . '%'];
            }
//dd($where);
            $WallSettings = new WallSettings();
            $WallToUser = new WallToUser();
            //墙列表
            $list = $WallSettings->alias('p')
                ->leftJoin('cw_user user', 'p.wall_owner_id = user.user_id')
                ->leftJoin('cw_community community', 'p.community_id = community.community_id')
                ->field('id as wall_id,wall_owner_id,wall_name,description,logo_url,auto_review,nickname,avatar,p.status,p.is_vip,p.create_time,p.community_id,community_name')
                ->where($where)
                ->order('p.create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
//dd($list);
            foreach ($list as $v) {
//                dd($v);
                $v->user_list = $WallToUser->alias('to')
                    ->join('cw_user user', 'to.user_id = user.user_id')
                    ->where('to.wall_id', $v->wall_id)
                    ->field('user.user_id,nickname,avatar,phone,email,wechat_id,school,college,is_official_subscribe,to.is_wall_master')
                    ->order('to.is_wall_master', 'desc')
                    ->select();
            }

            // 分页信息
            $pagination = [
                'current_page' => $list->getCurrentPage(),
                'total_pages' => $list->lastPage(),
                'total' => $list->total(),
                'per_page' => $limit,
                'has_prev' => $list->getCurrentPage() > 1,
                'has_next' => $list->getCurrentPage() < $list->lastPage()
            ];

            return $this->success('获取成功', null, [
                'list' => $list->items(),
                'pagination' => $pagination,
                'total' => $list->total()
            ]);

        } catch (Exception $e) {
            return $this->error('获取墙列表失败: ' . $e->getMessage());
        }
    }

    public function getCommunityList(Request $request)
    {
        $keyword = $request->param('keyword', '');

        $where[] = ['status', '=', 1];
        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['community_name', 'like', '%' . $keyword . '%'];
        }

        $community_list = Community::where($where)
            ->field('community_id,community_name')
            ->select();

        return $this->success('获取成功', null, [
            'list' => $community_list,
        ]);
    }

    public function saveWallInfo(Request $request)
    {
        $rule['user_id'] = 'require';
        $rule['community_id'] = 'require|integer';

        $this->validate($request->post(), $rule);

        $wall_owner_id = $request->post('user_id');
        $community_id = $request->post('community_id');

        $WallSettings = new WallSettings();
        $info = $WallSettings->where('wall_owner_id', $wall_owner_id)->find();
        if (!$info) {
            return $this->error('墙不存在');
        }

        $community_info = Community::where('community_id', $community_id)->find();
        if (!$community_info) {
            return $this->error('社区不存在');
        }

        $info->community_id = $community_id;
        $info->save();

        return $this->success('更新成功');
    }
}

<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Community as CommunityModel;
use think\Request;

/**
 * 社区管理控制器
 */
class Community extends AdminBase
{
    protected $model;

    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $status = $request->param('status', '');
        $keyword = $request->param('keyword', '');

        // 构建查询条件
        $where = [];

        // 状态筛选（审核状态）
        if (!empty($status)) {
            $where[] = ['community_status', '=', $status];
        }

        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['community_name|alias', 'like', "%{$keyword}%"];
        }

        // 获取社区列表
        $list = $this->model
            ->where($where)
//            ->order('create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);

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
    }

    //添加
    public function add(Request $request)
    {
        $data = $request->param();
        $this->model->save($data);
        return $this->success('添加成功');
    }

    //更新

    public function updateStatus(Request $request)
    {
        $community_id = $request->param('community_id', 0);
        $status = $request->param('status', '0');

        $community = $this->model->where('community_id', $community_id)->find();
        if (!$community) {
            return $this->error('社区不存在');
        }
        $community->status = $status;
        $community->save();
        return $this->success('更新状态成功');
    }

    //更新状态

    public function update(Request $request)
    {
        $community_id = $request->param('community_id', 0);
        $community_name = $request->param('community_name', '');
        $alias = $request->param('alias', '');
        $community = $this->model->where('community_id', $community_id)->find();
        if (!$community) {
            return $this->error('社区不存在');
        }

        $community->community_name = $community_name;
        $community->alias = $alias;
        $community->save();

        return $this->success('更新成功');
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->model = new CommunityModel();
    }
}
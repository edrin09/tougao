<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Channels as ChannelsModel;
use think\Request;

/**
 * 渠道管理控制器
 */
class Channels extends AdminBase
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
        if ($status !== '') {
            $where[] = ['status', '=', $status];
        }

        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['channels_name', 'like', "%{$keyword}%"];
        }

        // 获取渠道列表
        $list = $this->model
            ->where($where)
            ->order('create_time', 'desc')
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
        $channels_id = $request->param('channels_id');
        $status = $request->param('status', '0');

        $channel = $this->model->where('channels_id', $channels_id)->find();
        if (!$channel) {
            return $this->error('渠道不存在');
        }

        $channel->status = $status;
        $channel->save();
        return $this->success('更新状态成功');
    }

    //更新状态

    public function update(Request $request)
    {
        $channels_id = $request->param('channels_id', 0);
        $channels_name = $request->param('channels_name', '');
        $channel = $this->model->where('channels_id', $channels_id)->find();
        if (!$channel) {
            return $this->error('渠道不存在');
        }

        $channel->channels_name = $channels_name;
        $channel->save();

        return $this->success('更新成功');
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->model = new ChannelsModel();
    }
}
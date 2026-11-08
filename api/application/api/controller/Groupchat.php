<?php
namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\GroupChat as GroupChatModel;
use think\facade\Log;
use think\Request;

class GroupChat extends Api
{
    protected $noNeedLogin = [];

    /**
     * 获取群聊列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function getList(Request $request)
    {
        try {
            $wallOwnerId = $request->param('wallOwnerId');

            if (empty($wallOwnerId)) {
                return $this->error('墙主ID不能为空');
            }

            $groupModel = new GroupChatModel();
            $list = $groupModel->where('wall_owner_id', $wallOwnerId)
                             ->where('status', 1)
                             ->order('weight desc, create_time desc')
                             ->select();

            return $this->success('获取成功', '', $list);

        } catch (\Exception $e) {
            Log::error('获取群聊列表失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建群聊
     * @param Request $request
     * @return \think\response\Json
     */
    public function create(Request $request)
    {
        try {
            $data = $request->param();

            // 验证必填字段
            if (empty($data['group_name'])) {
                return $this->error('群聊名称不能为空');
            }

            if (empty($data['wallOwnerId'])) {
                return $this->error('墙主ID不能为空');
            }

            // 检查群聊名称是否重复
            $groupModel = new GroupChatModel();
            $exists = $groupModel->where('wall_owner_id', $data['wallOwnerId'])
                                ->where('group_name', $data['group_name'])
                                ->where('status', 1)
                                ->find();

            if ($exists) {
                return $this->error('群聊名称已存在');
            }

            // 准备数据
            $insertData = [
                'wall_owner_id' => $data['wallOwnerId'],
                'group_name' => trim($data['group_name']),
                'group_description' => isset($data['group_description']) ? trim($data['group_description']) : '',
                'group_avatar' => isset($data['group_avatar']) ? $data['group_avatar'] : '',
                'group_qrcode' => isset($data['group_qrcode']) ? $data['group_qrcode'] : '',
                'weight' => isset($data['weight']) ? intval($data['weight']) : 0,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];

            $result = $groupModel->insert($insertData);

            if ($result) {
                return $this->success('创建成功', '', ['id' => $groupModel->getLastInsID()]);
            } else {
                return $this->error('创建失败');
            }

        } catch (\Exception $e) {
            Log::error('创建群聊失败', [
                'data' => $data ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('创建失败: ' . $e->getMessage());
        }
    }

    /**
     * 更新群聊
     * @param Request $request
     * @return \think\response\Json
     */
    public function update(Request $request)
    {
        try {
            $data = $request->param();

            if (empty($data['id'])) {
                return $this->error('群聊ID不能为空');
            }

            if (empty($data['wallOwnerId'])) {
                return $this->error('墙主ID不能为空');
            }

            $groupModel = new GroupChatModel();
            $group = $groupModel->where('id', $data['id'])
                              ->where('wall_owner_id', $data['wallOwnerId'])
                              ->find();

            if (!$group) {
                return $this->error('群聊不存在或无权限操作');
            }

            // 检查群聊名称是否重复（排除自己）
            if (!empty($data['group_name'])) {
                $exists = $groupModel->where('wall_owner_id', $data['wallOwnerId'])
                                    ->where('group_name', $data['group_name'])
                                    ->where('id', '<>', $data['id'])
                                    ->where('status', 1)
                                    ->find();

                if ($exists) {
                    return $this->error('群聊名称已存在');
                }
            }

            // 准备更新数据
            $updateData = [
                'update_time' => date('Y-m-d H:i:s')
            ];

            if (!empty($data['group_name'])) {
                $updateData['group_name'] = trim($data['group_name']);
            }

            if (isset($data['group_description'])) {
                $updateData['group_description'] = trim($data['group_description']);
            }

            if (isset($data['group_avatar'])) {
                $updateData['group_avatar'] = $data['group_avatar'];
            }

            if (isset($data['group_qrcode'])) {
                $updateData['group_qrcode'] = $data['group_qrcode'];
            }

            if (isset($data['weight'])) {
                $updateData['weight'] = intval($data['weight']);
            }

            $result = $groupModel->where('id', $data['id'])->update($updateData);

            if ($result !== false) {
                return $this->success('更新成功');
            } else {
                return $this->error('更新失败');
            }

        } catch (\Exception $e) {
            Log::error('更新群聊失败', [
                'data' => $data ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除群聊
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->param('id');
            $wallOwnerId = $request->param('wallOwnerId');

            if (empty($id)) {
                return $this->error('群聊ID不能为空');
            }

            if (empty($wallOwnerId)) {
                return $this->error('墙主ID不能为空');
            }

            $groupModel = new GroupChatModel();
            $group = $groupModel->where('id', $id)
                              ->where('wall_owner_id', $wallOwnerId)
                              ->find();

            if (!$group) {
                return $this->error('群聊不存在或无权限操作');
            }

            // 软删除
            $result = $groupModel->where('id', $id)->update([
                'status' => 0,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            if ($result !== false) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }

        } catch (\Exception $e) {
            Log::error('删除群聊失败', [
                'id' => $id ?? '',
                'wallOwnerId' => $wallOwnerId ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取群聊详情
     * @param Request $request
     * @return \think\response\Json
     */
    public function detail(Request $request)
    {
        try {
            $id = $request->param('id');

            if (empty($id)) {
                return $this->error('群聊ID不能为空');
            }

            $groupModel = new GroupChatModel();
            $group = $groupModel->where('id', $id)
                              ->where('status', 1)
                              ->find();

            if (!$group) {
                return $this->error('群聊不存在');
            }

            return $this->success('获取成功', '', $group);

        } catch (\Exception $e) {
            Log::error('获取群聊详情失败', [
                'id' => $id ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量更新权重
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchUpdateWeight(Request $request)
    {
        try {
            $weightData = $request->param('weightData');
            $wallOwnerId = $request->param('wallOwnerId');

            if (empty($weightData) || !is_array($weightData)) {
                return $this->error('权重数据不能为空');
            }

            if (empty($wallOwnerId)) {
                return $this->error('墙主ID不能为空');
            }

            $groupModel = new GroupChatModel();

            // 验证所有群聊都属于当前墙主
            $ids = array_column($weightData, 'id');
            $count = $groupModel->where('wall_owner_id', $wallOwnerId)
                               ->where('id', 'in', $ids)
                               ->where('status', 1)
                               ->count();

            if ($count != count($ids)) {
                return $this->error('存在无权限操作的群聊');
            }

            // 批量更新权重
            $result = $groupModel->batchUpdateWeight($weightData);

            if ($result) {
                return $this->success('更新成功');
            } else {
                return $this->error('更新失败');
            }

        } catch (\Exception $e) {
            Log::error('批量更新权重失败', [
                'weightData' => $weightData ?? [],
                'wallOwnerId' => $wallOwnerId ?? '',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('更新失败: ' . $e->getMessage());
        }
    }
}

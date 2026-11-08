<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Notification as NotificationModel;
use think\Request;
use think\response\Json;
use think\facade\Log;

class NotificationManager extends AdminBase
{
    /**
     * 获取通知列表
     * @param Request $request
     * @return Json
     */
    public function getList(Request $request): Json
    {
        try {
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 10);
            $keyword = $request->param('keyword', '');
            $type = $request->param('type', 0);

            // 验证参数
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 10;
            if ($type < 0) $type = 0;

            $result = NotificationModel::getNotificationList($page, $limit, $keyword, $type);

            return $this->success('获取成功', '', $result);

        } catch (\Exception $e) {
            Log::error('获取通知列表失败：' . $e->getMessage());
            return $this->error('获取通知列表失败');
        }
    }

    /**
     * 获取通知详情
     * @param Request $request
     * @return Json
     */
    public function getDetail(Request $request): Json
    {
        try {
            $id = $request->param('id');

            if (empty($id)) {
                return $this->error('通知ID不能为空');
            }

            $notification = NotificationModel::getNotificationById($id);

            if (!$notification) {
                return $this->error('通知不存在');
            }

            return $this->success('获取成功', '', $notification);

        } catch (\Exception $e) {
            Log::error('获取通知详情失败：' . $e->getMessage());
            return $this->error('获取通知详情失败');
        }
    }

    /**
     * 添加通知
     * @param Request $request
     * @return \think\response\Json
     */
    public function add(Request $request)
    {
        try {
            $content = $request->param('content');
            $type = $request->param('type', NotificationModel::TYPE_PERSONAL);

            if (empty($content)) {
                return $this->error('通知内容不能为空');
            }

            // 内容长度验证
            if (mb_strlen($content) > 1000) {
                return $this->error('通知内容不能超过1000个字符');
            }

            // 验证类型
            if (!in_array($type, [NotificationModel::TYPE_PERSONAL, NotificationModel::TYPE_WALL_MASTER])) {
                return $this->error('通知类型无效');
            }

            $result = NotificationModel::addNotification($content, $type);

            if ($result) {
                Log::info('添加通知成功，ID：' . $result . '，类型：' . $type);
                return $this->success('添加成功', '', ['id' => $result]);
            } else {
                return $this->error('添加失败');
            }

        } catch (\Exception $e) {
            Log::error('添加通知失败：' . $e->getMessage());
            return $this->error('添加通知失败');
        }
    }

    /**
     * 更新通知
     * @param Request $request
     * @return \think\response\Json
     */
    public function update(Request $request)
    {
        try {
            $id = $request->param('id');
            $content = $request->param('content');
            $type = $request->param('type');

            if (empty($id)) {
                return $this->error('通知ID不能为空');
            }

            if (empty($content)) {
                return $this->error('通知内容不能为空');
            }

            // 内容长度验证
            if (mb_strlen($content) > 1000) {
                return $this->error('通知内容不能超过1000个字符');
            }

            // 验证类型（如果提供了类型参数）
            if ($type !== null && !in_array($type, [NotificationModel::TYPE_PERSONAL, NotificationModel::TYPE_WALL_MASTER])) {
                return $this->error('通知类型无效');
            }

            // 检查通知是否存在
            $notification = NotificationModel::getNotificationById($id);
            if (!$notification) {
                return $this->error('通知不存在');
            }

            $result = NotificationModel::updateNotification($id, $content, $type);

            if ($result !== false) {
                Log::info('更新通知成功，ID：' . $id);
                return $this->success('更新成功');
            } else {
                return $this->error('更新失败');
            }

        } catch (\Exception $e) {
            Log::error('更新通知失败：' . $e->getMessage());
            return $this->error('更新通知失败');
        }
    }

    /**
     * 删除通知
     * @param Request $request
     * @return \think\response\Json
     */
    public function delete(Request $request)
    {
        try {
            $id = $request->param('id');

            if (empty($id)) {
                return $this->error('通知ID不能为空');
            }

            // 检查通知是否存在
            $notification = NotificationModel::getNotificationById($id);
            if (!$notification) {
                return $this->error('通知不存在');
            }

            $result = NotificationModel::deleteNotification($id);

            if ($result) {
                Log::info('删除通知成功，ID：' . $id);
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }

        } catch (\Exception $e) {
            Log::error('删除通知失败：' . $e->getMessage());
            return $this->error('删除通知失败');
        }
    }

    /**
     * 批量删除通知
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchDelete(Request $request)
    {
        try {
            $ids = $request->param('ids');

            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的通知');
            }

            $successCount = 0;
            $failCount = 0;

            foreach ($ids as $id) {
                try {
                    $result = NotificationModel::deleteNotification($id);
                    if ($result) {
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                    Log::error('批量删除通知失败，ID：' . $id . '，错误：' . $e->getMessage());
                }
            }

            Log::info("批量删除通知完成，成功：{$successCount}，失败：{$failCount}");

            return $this->success("批量删除完成，成功：{$successCount}，失败：{$failCount}", '', [
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);

        } catch (\Exception $e) {
            Log::error('批量删除通知失败：' . $e->getMessage());
            return $this->error('批量删除失败');
        }
    }

    /**
     * 获取通知统计信息
     * @return \think\response\Json
     */
    public function getStats()
    {
        try {
            $stats = NotificationModel::getNotificationStats();

            return $this->success('获取成功', '', $stats);

        } catch (\Exception $e) {
            Log::error('获取通知统计失败：' . $e->getMessage());
            return $this->error('获取统计信息失败');
        }
    }

    /**
     * 获取通知类型列表
     * @return \think\response\Json
     */
    public function getTypeList()
    {
        try {
            $typeList = NotificationModel::getTypeList();

            // 转换为前端需要的格式
            $options = [];
            foreach ($typeList as $value => $label) {
                $options[] = [
                    'value' => $value,
                    'label' => $label
                ];
            }

            return $this->success('获取成功', '', $options);

        } catch (\Exception $e) {
            Log::error('获取通知类型列表失败：' . $e->getMessage());
            return $this->error('获取通知类型列表失败');
        }
    }
}

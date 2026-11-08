<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Notification as NotificationModel;
use think\Request;
use think\response\Json;

class Notification extends AdminBase
{
    /**
     * 获取通知列表
     * @param Request $request
     * @return \think\response\Json
     */
    public function getList(Request $request)
    {
        try {
            $page = $request->param('page', 1);
            $limit = $request->param('limit', 10);

            // 验证参数
            if ($page < 1) $page = 1;
            if ($limit < 1 || $limit > 100) $limit = 10;

            $result = NotificationModel::getNotifications($page, $limit);

            return $this->success('获取成功', '', $result);

        } catch (\Exception $e) {
            return $this->error('获取通知列表失败');
        }
    }

    /**
     * 获取通知详情
     * @param Request $request
     * @return \think\response\Json
     */
    public function getDetail(Request $request)
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

            if (empty($content)) {
                return $this->error('通知内容不能为空');
            }

            $result = NotificationModel::addNotification($content);

            if ($result) {
                return $this->success('添加成功', '', ['id' => $result]);
            } else {
                return $this->error('添加失败');
            }

        } catch (\Exception $e) {
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

            if (empty($id)) {
                return $this->error('通知ID不能为空');
            }

            if (empty($content)) {
                return $this->error('通知内容不能为空');
            }

            // 检查通知是否存在
            $notification = NotificationModel::getNotificationById($id);
            if (!$notification) {
                return $this->error('通知不存在');
            }

            $result = NotificationModel::updateNotification($id, $content);

            if ($result !== false) {
                return $this->success('更新成功');
            } else {
                return $this->error('更新失败');
            }

        } catch (\Exception $e) {
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
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }

        } catch (\Exception $e) {
            return $this->error('删除通知失败');
        }
    }

    /**
     * 获取最新通知
     * @param Request $request
     * @return \think\response\Json
     */
    public function getLatest(Request $request)
    {
        try {
            $limit = $request->param('limit', 5);

            if ($limit < 1 || $limit > 20) $limit = 5;

            $notifications = NotificationModel::getLatestNotifications($limit);

            return $this->success('获取成功', '', $notifications);

        } catch (\Exception $e) {
            return $this->error('获取最新通知失败');
        }
    }
}

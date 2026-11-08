<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Notification as NotificationModel;
use think\Request;
use think\response\Json;

class Notification extends Api {
    /**
    * 获取通知列表
    * @param Request $request
    * @return \think\response\Json
    */

    public function getList( Request $request ) {
        try {
            $page = $request->param( 'page', 1 );
            $limit = $request->param( 'limit', 10 );

            // 验证参数
            if ( $page < 1 ) $page = 1;
            if ( $limit < 1 || $limit > 50 ) $limit = 10;
            // API接口限制更严格

            $result = NotificationModel::getNotifications( $page, $limit );

            return $this->success( '获取成功', '', $result );

        } catch ( \Exception $e ) {
            return $this->error( '获取通知列表失败' );
        }
    }

    /**
    * 获取通知详情
    * @param Request $request
    * @return \think\response\Json
    */

    public function getDetail( Request $request ) {
        try {
            $id = $request->param( 'id' );

            if ( empty( $id ) ) {
                return $this->error( '通知ID不能为空' );
            }

            $notification = NotificationModel::getNotificationById( $id );

            if ( !$notification ) {
                return $this->error( '通知不存在' );
            }

            return $this->success( '获取成功', '', $notification );

        } catch ( \Exception $e ) {
            return $this->error( '获取通知详情失败' );
        }
    }

    /**
    * 获取最新通知
    * @param Request $request
    * @return \think\response\Json
    */

    public function getLatest(Request $request) {
        try {
            $type = $request->param('type', 1);

            // 验证类型参数
            if (!in_array($type, [1, 2])) {
                return $this->error('无效的通知类型');
            }

            // 获取时间最近的一条通知
            $notification = NotificationModel::where('type', $type)
                ->order('created_at', 'desc')
                ->find();

            // 如果没有找到通知，返回空数据而不是null
            if (!$notification) {
                return $this->success('暂无通知', '', [
                    'id' => null,
                    'type' => $type,
                    'content' => '',
                    'created_at' => null,
                    'updated_at' => null
                ]);
            }

            // 添加类型文本描述
            $notificationData = $notification->toArray();
            $notificationData['type_text'] = NotificationModel::$typeMap[$notification->type] ?? '未知类型';

            return $this->success('获取成功', '', $notificationData);

        } catch (\Exception $e) {
            Log::error('获取最新通知失败：' . $e->getMessage());
            return $this->error('获取最新通知失败');
        }
    }

    /**
    * 获取通知总数
    * @return \think\response\Json
    */

    public function getCount() {
        try {
            $count = NotificationModel::count();

            return $this->success( '获取成功', '', [
                'count' => $count
            ] );

        } catch ( \Exception $e ) {
            return $this->error( '获取通知总数失败' );
        }
    }
}

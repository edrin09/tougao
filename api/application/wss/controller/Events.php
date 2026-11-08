<?php

/**
 * 用于检测业务代码死循环或者长时间阻塞等问题
 * 如果发现业务卡死，可以将下面declare打开（去掉//注释），并执行php start.php reload
 * 然后观察一段时间workerman.log看是否有process_timeout异常
 */

//declare(ticks=1);

namespace app\wss\controller;

use app\wss\service\Post as PostService;
use GatewayClient\Gateway;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class Events extends WsConfig
{
    public function __construct()
    {
        parent::_initialize();
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        $send_msg['status'] = 200;
        $send_msg['message'] = "login succeeded";

        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, json_encode($send_msg));
    }

    /**
     * 当客户端发来消息时触发
     * @param int $client_id 连接id
     * @param mixed $message 具体消息
     */
    public static function onMessage($client_id, $message)
    {
        $msg_data = json_decode($message, true);

        if (isset($msg_data['type'])) {
            $isSendWsMessage = 1;


            switch ($msg_data['type']) {
                case 'bind':
                    if (empty($msg_data['bind_user'])) {
                        $msg_arr = self::returErrorArray('bind_user不能为空');
                        break;
                    }

                    Gateway::bindUid($client_id, $msg_data['bind_user']);//绑定

                    $send_user = $msg_data['bind_user'];
                    $msg_arr = self::returnArray('bind_user绑定成功');

                    break;

                case 'heartbeat':
                    if (empty($msg_data['bind_user'])) {
                        $msg_arr = self::returErrorArray('bind_user不能为空');
                        break;
                    }

                    $send_user = $msg_data['bind_user'];
                    $msg_arr = self::returnArray('succeeded');
                    break;

                case 'status_moment':
                    if (empty($msg_data['user_id'])) {
                        $msg_arr = self::returErrorArray('user_id不能为空');
                        break;
                    }

                    if (empty($msg_data['data'])) {
                        $msg_arr = self::returErrorArray('data不能为空');
                        break;
                    }

                    if (!in_array($msg_data['data']['status'], [0, 1])) {
                        $msg_arr = self::returErrorArray('status值错误');
                        break;
                    }

                    if (empty($msg_data['data']['no'])) {
                        $msg_arr = self::returErrorArray('no不能为空');
                        break;
                    }

                    $send_user = $msg_data['user_id'];

                    $PostService = new PostService();
                    $msg_arr = $PostService->saveStatusMoment(
                        $msg_data['data']['wall_id'],
                        $msg_data['data']['status'],
                        $msg_data['data']['no'],
                        $msg_data['data']['list']
                    );

                    break;


                default:
                    $msg_arr = self::returErrorArray('未知的消息类型');
                    break;
            }

            if ($isSendWsMessage) {
                self::sendWsMessage($msg_arr, $send_user);
            }
        }
    }

    /**
     * 当用户断开连接时触发
     * @param int $client_id 连接id
     */
    public static function onClose($client_id)
    {
        // 向所有人发送
//        GateWay::sendToAll("$client_id logout\r\n");
    }
}
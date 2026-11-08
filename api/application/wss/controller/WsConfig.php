<?php

namespace app\wss\controller;

use GatewayClient\Gateway;

class WsConfig
{
    protected static $register_address;
    protected $data = [];

    public static function getRegisterAddress()
    {
        return self::$register_address;
    }

    public static function sendWsMessage($send_msg, $to_user = '')
    {
        $send_msg['register_address'] = self::$register_address;

        if ($to_user) {
            Gateway::sendToUid($to_user, json_encode($send_msg));
            return true;
        }

        Gateway::sendToAll(json_encode($send_msg));
        return true;
    }

    protected static function returErrorArray($message, $code = 400)
    {
        return ['code' => $code, 'message' => $message];
    }

    protected static function returnArray($message = 'success')
    {
        return ['code' => 200, 'message' => $message];
    }

    public function _initialize()
    {
        self::$register_address = '127.0.0.1:1236';
        Gateway::$registerAddress = self::$register_address;
    }
}
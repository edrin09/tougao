<?php

namespace app\wss\controller;

use GatewayClient\Gateway;

class Client extends WsConfig
{
    public function __construct()
    {
        parent::_initialize();
    }


    public function getAllUidList()
    {
        $res['register_address'] = self::$register_address;
        $res['list'] = Gateway::getAllUidList();
        return json($res);
    }
}
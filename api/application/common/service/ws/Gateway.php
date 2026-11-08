<?php

namespace app\common\service\ws;

use GatewayWorker\Gateway;
use Workerman\Worker;

require_once $_SERVER['ROOT_DIR'] . 'vendor/autoload.php';

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    $_SERVER['ROOT_DIR'] = str_replace('\\', '/', dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/';
}

//wss连接 证书最好是申请的证书
//    $context = array(
//        'ssl' => array(
//            // 请使用绝对路径
//            'local_cert' => Env::get('ws.certificat_path', '') . '/fullchain.pem', // 也可以是crt文件
//            'local_pk' => Env::get('ws.certificat_path', '') . '/privkey.key',
//            'verify_peer' => false,
//            'allow_self_signed' => true, //如果是自签名证书需要开启此选项
//        )
//    );
//    // gateway 进程，这里使用Text协议，可以用telnet测试
//    // websocket协议(端口任意，只要没有被其它程序占用就行)
//    $gateway = new Gateway('websocket://0.0.0.0:' . Env::get('ws.websocket_port', '8282'), $context);
//
//    // 开启SSL，websocket+SSL 即wss
//    $gateway->transport = 'ssl';

//ws连接
// gateway 进程，这里使用Text协议，可以用telnet测试
// websocket协议(端口任意，只要没有被其它程序占用就行)
$gateway = new Gateway('websocket://0.0.0.0:8282');

// gateway名称，status方便查看
$gateway->name = 'tougao';
// gateway进程数
// 内部通讯起始端口，假如$gateway->count=4，起始端口为4000
$gateway->count = 4;

// 本机ip，单机填127.0.0.1 分布式填写自己的内网ip
$gateway->lanIp = '127.0.0.1';

// 则一般会使用4000 4001 4002 4003 4个端口作为内部通讯端口
$gateway->startPort = 4000;

// 服务注册地址  单机填127.0.0.1   分布式部署时 主服务是127.0.0.1 。从服务填写主服务的内网ip
$gateway->registerAddress = '127.0.0.1:1236';
// 心跳间隔  单位秒
$gateway->pingInterval = 55;
// 心跳数据
$gateway->pingData = '{"type":"ping"}';

// 当客户端连接上来时，设置连接的onWebSocketConnect，即在websocket握手时的回调
//$gateway->onConnect = function ($connection) {
//    $connection->onWebSocketConnect = function ($connection, $http_header) {
//        // 可以在这里判断连接来源是否合法，不合法就关掉连接
//        // $_SERVER['HTTP_ORIGIN']标识来自哪个站点的页面发起的websocket链接
//        if ($_SERVER['HTTP_ORIGIN'] != 'http://kedou.workerman.net') {
//            $connection->close();
//        }
//        // onWebSocketConnect 里面$_GET $_SERVER是可用的
//        // var_dump($_GET, $_SERVER);
//    };
//};

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    Worker::runAll();
}

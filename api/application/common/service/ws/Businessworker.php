<?php

namespace app\common\service\ws;

use GatewayWorker\BusinessWorker;
use Workerman\Worker;

require_once $_SERVER['ROOT_DIR'] . 'vendor/autoload.php';

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    $_SERVER['ROOT_DIR'] = str_replace('\\', '/', dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/';
}

// bussinessWorker 进程
$worker = new BusinessWorker();

// worker名称
$worker->name = 'tougao';

// bussinessWorker进程数量
$worker->count = 4;

// 服务注册地址  //单机填127.0.0.1 分布式时填写填主服务的内网地址
$worker->registerAddress = '127.0.0.1:1236';
//事件处理类，默认是 Event 类
$worker->eventHandler = '\app\wss\controller\Events';

Worker::$logFile = $_SERVER['ROOT_DIR'] . 'runtime/workerman.log';

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    Worker::runAll();
}
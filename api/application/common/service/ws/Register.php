<?php

namespace app\common\service\ws;

use GatewayWorker\Register;
use Workerman\Worker;

require_once $_SERVER['ROOT_DIR'] . 'vendor/autoload.php';

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    $_SERVER['ROOT_DIR'] = str_replace('\\', '/', dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/';
}
// register 必须是text协议
new Register('text://0.0.0.0:1236');

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    Worker::runAll();
}
<?php
//php startWs.php start

define("ROOT_DIR", __DIR__ . '/');

ini_set('display_errors', 'on');

use Workerman\Worker;

if (strpos(strtolower(PHP_OS), 'win') === 0) {
    exit("start.php not support windows, please use start_for_win.bat\n");
}

// 检查扩展
if (!extension_loaded('pcntl')) {
    exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

if (!extension_loaded('posix')) {
    exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
}

$_SERVER['ROOT_DIR'] = ROOT_DIR;  //根目录

require_once ROOT_DIR . 'application/common/service/ws/Businessworker.php';
require_once ROOT_DIR . 'application/common/service/ws/Gateway.php';
require_once ROOT_DIR . 'application/common/service/ws/Register.php';

// 运行所有服务
Worker::runAll();
<?php

namespace app\common\service;

use app\common\Env;
use think\Db;

class DatabasesService
{
    public static function getDatabaseConfig()
    {
        Env::loadFile(ROOT_DIR . '.env');

        $config = [
            // 默认数据连接标识
            'default' => Env::get('database.driver'),

            // 数据库连接信息
            'connections' => [
                'mysql' => [
                    // 数据库类型
                    'type' => Env::get('database.driver'),
                    // 服务器地址
                    'hostname' => Env::get('database.hostname'),
                    // 数据库名
                    'database' => Env::get('database.database'),
                    // 用户名
                    'username' => Env::get('database.username'),
                    // 密码
                    'password' => Env::get('database.password'),
                    // 端口
                    'hostport' => Env::get('database.hostport'),
                    // 数据库连接参数
                    'params' => [],
                    // 数据库编码默认采用utf8
                    'charset' => Env::get('database.charset'),
                    // 数据库表前缀
                    'prefix' => Env::get('database.prefix'),
                    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
                    'deploy' => 0,
                    // 数据库读写是否分离 主从式有效
                    'rw_separate' => false,
                    // 读写分离后 主服务器数量
                    'master_num' => 1,
                    // 指定从服务器序号
                    'slave_no' => '',
                    // 是否严格检查字段是否存在
                    'fields_strict' => true,
                    // 是否需要断线重连
                    'break_reconnect' => false,
                    // 监听SQL
                    'trigger_sql' => true,
                    // 开启字段缓存
                    'fields_cache' => false,
                ],
            ],
        ];
//        print_r($config);
        Db::setConfig($config);
    }
}
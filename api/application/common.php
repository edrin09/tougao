<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


if (!function_exists('pr')) {
    function pr($data, $title = '', $is_dump = false)
    {
        echo "<pre>------------------" . PHP_EOL;
        if ($title) {
            echo "title:" . $title;
            echo PHP_EOL;
        }
        if ($is_dump) {
            var_dump($data);
        } else {
            print_r($data);
        }
        echo PHP_EOL . "------------------</pre>" . PHP_EOL . PHP_EOL;
    }
}

if (!function_exists('dd')) {
    function dd($data, $title = '', $is_dump = false)
    {
        echo "<pre>------------------" . PHP_EOL;
        if ($title) {
            echo "title:" . $title;
            echo PHP_EOL;
        }

        if ($is_dump) {
            var_dump($data);
        } else {
            print_r($data);
        }
        echo PHP_EOL . "------------------</pre>" . PHP_EOL . PHP_EOL;
        exit();
    }
}


/**
 * 输出错误信息
 * @param string $message 报错信息
 * @param int|string|null $code 状态码,默认为配置文件status.error
 * @param array $data 附加数据
 */
if (!function_exists('throwError')) {
    function throwError(string $message, $code = null, array $data = [])
    {
        is_null($code) && $code = 400;
        throw new Exception($message, $code);
    }
}

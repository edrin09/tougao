<?php
// +----------------------------------------------------------------------
// | 小程序配置
// +----------------------------------------------------------------------

return [
    // 小程序域名
    'domain'        => 'https://wall.example.com',
    
    // 小程序 APPID
    'appid'         => '',
    
    // 小程序 AppSecret
    'appsecret'     => '',
    
    // 小程序接口域名
    'api_domain'    => 'https://api.weixin.qq.com',
    
    // 短链接配置
    'short_link'    => [
        'length'    => 6,        // 短链代码长度
        'prefix'    => 's',      // 短链URL前缀
        'expires'   => 0,        // 过期时间（秒），0表示永不过期
    ],
    
    // 二维码配置
    'qrcode'        => [
        'width'     => 430,      // 二维码宽度
        'path'      => 'uploads/qrcode/', // 保存路径
        'color'     => [         // 主色调
            'r' => 0,
            'g' => 0,
            'b' => 0,
        ],
    ],
]; 
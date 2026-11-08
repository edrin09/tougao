<?php
// +----------------------------------------------------------------------
// | 微信配置
// +----------------------------------------------------------------------

return [
    // 小程序配置
    'appid' => 'wx0000000000000000',  // 小程序 AppID（换成你自己的）
    'appsecret' => 'YOUR_APP_SECRET', // 小程序AppSecret，开发时替换为实际的AppSecret

    // 生成的投稿小程序路径
    'publish_page' => 'pages/index/index',

    // 公共评论区小程序路径
    'comment_page' => 'pages/comment/comment',

    // 小程序消息模板ID
    'templates' => [
        'review_result' => 'QENzCIeyKW-ec4b39p8199fZPIXhBO8jgUodUXrj6rc', // 审核结果通知模板ID
        'new_post' => 'k5SHRDxW7BpYGK5G327sqiuphwtr7ebDHu6Qv_9jaV4', // 新投稿提醒模板ID
        'reply' => 'O46wVFKwr7Qf4jCQKZd2Yf-CYxseEpJFgfCdf5WpQ_I', // 新回复提醒模板ID
    ],

    // 小程序消息模板跳转路径
    'template_pages' => [
        'review_result' => 'pages/my-posts/my-posts',
        'new_post' => 'pages/submission-list/submission-list',
        'reply' => 'pages/comment/comment?wallOwnerId='
    ],

    // 公众号（服务号）配置 —— 用于审核结果 / 提现结果的模板消息推送
    // 留空则 WechatOfficialService 会抛出明确的配置缺失异常
    'official_account' => [
        'app_id' => 'YOUR_OFFICIAL_APPID',
        'secret' => 'YOUR_OFFICIAL_APPSECRET',
        'token' => 'YOUR_OFFICIAL_TOKEN',
    ],

    // 微信支付配置
    //小山豪
    'mch_id' => 'YOUR_MCH_ID',  // 微信支付商户号
    'pay_key' => 'YOUR_PAY_KEY', // 微信支付API密钥
//    怡彬网络
//    'mch_id'     => 'YOUR_MCH_ID',  // 微信支付商户号
//    'pay_key'    => 'YOUR_PAY_KEY', // 微信支付API密钥
    'cert_path' => '/www/wwwroot/tougao.lin-xin.com/config/cert/apiclient_cert.pem', // 证书路径
    'key_path' => __DIR__ . '/cert/apiclient_key.pem',  // 证书路径

];

<?php
// +----------------------------------------------------------------------
// | 上传配置
// +----------------------------------------------------------------------

return [
//    'domain'      => 'https://tougao.njlinks.cn',  // 实际开发替换为真实域名
    // 路径前缀
    'path_prefix' => '/public',
    
    // 上传地址（云存储）
    'upload_url'  => 'http://upload.example.com',
    
    // 云存储密钥
    'access_key'  => 'your_access_key',
    'secret'      => 'your_secret_key',
    
    // 本地上传保存路径
    'save_path'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',

    // 海报上传保存路径
    'poster_path'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'poster',

    // 无联系海报上传保存路径
    'no_contact_poster_path'   => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'no_contact_poster',

    // 图片上传设置
    'image_size'  => 2,  // 图片大小限制，单位MB
    'image_exts'  => 'jpg,jpeg,png,gif,webp',  // 允许的图片类型
    
    // 视频上传设置
    'video_size'  => 20, // 视频大小限制，单位MB
    'video_exts'  => 'mp4,flv,avi,wmv',  // 允许的视频类型
]; 
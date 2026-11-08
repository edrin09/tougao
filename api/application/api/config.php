<?php
// +----------------------------------------------------------------------
// | API模块配置
// +----------------------------------------------------------------------

return [
    // 默认输出类型
    'default_return_type'    => 'json',
    
    // 允许的域名
    'cors_allow_origin'      => '*',
    
    // 允许的请求方法
    'cors_allow_methods'     => 'GET,POST,OPTIONS',
    
    // 允许的头信息
    'cors_allow_headers'     => 'Content-Type,Authorization,X-Requested-With',
    
    // API接口版本
    'api_version'            => 'v1.0',
    
    // 接口域名
    'api_domain'             => 'https://api.campus-wall.com',
    
    // Token密钥
    'token_secret'           => 'campus_wall_2023@secret',
    
    // 短链域名
    'short_domain'           => 'https://s.campus-wall.com',
    
    // 域名
    'domain'                 => 'https://campus-wall.com',
]; 
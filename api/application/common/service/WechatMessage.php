<?php

namespace app\common\service;

use think\Exception;
use think\facade\Config;
use think\facade\Log;

class WechatMessage
{
    protected $appId;
    protected $appSecret;
    protected $accessToken;
    protected $templates;

    public function __construct()
    {
        $config = config('wechat.');
        $this->appId = $config['appid'];
        $this->appSecret = $config['appsecret'];
        $this->templates = $config['templates'];
        $this->getAccessToken();
    }

    /**
     * 获取小程序全局接口调用凭据
     */
    protected function getAccessToken()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid={$this->appId}&secret={$this->appSecret}";
        $result = json_decode(file_get_contents($url), true);

        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            return $result['access_token'];
        } else {
            throw new Exception('获取access_token失败：' . json_encode($result));
        }
    }

    /**
     * 发送订阅消息
     * @param string $openid 接收者openid
     * @param string $template_type 模板类型
     * @param array $data 模板数据
     * @param string $page 跳转页面（可选，如果不传则根据template_pages配置自动匹配）
     * @param array $pageParams 页面参数（可选，用于动态拼接页面参数）
     */

    public function sendSubscribeMessage($openid, $template_type, $data, $page = null, $pageParams = [])
    {
        if (!isset($this->templates[$template_type])) {
            throw new Exception('未找到对应的模板ID');
        }

        // 如果没有指定页面，根据template_pages配置自动匹配
        if ($page === null) {
            $page = $this->getPageByTemplateType($template_type, $pageParams);
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=' . $this->accessToken;

        $post_data = [
            'touser' => $openid,
            'template_id' => $this->templates[$template_type],
            'page' => $page,
            'data' => $data
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($result['errcode'] != 0) {
            Log::error('发送订阅消息失败：' . json_encode($result));
            throw new Exception('发送订阅消息失败：' . $result['errmsg']);
        }
        Log::info('发送订阅消息成功：' . json_encode($result));

        return true;
    }

    /**
     * 根据模板类型获取页面路径
     * @param string $template_type 模板类型
     * @param array $pageParams 页面参数
     * @return string 页面路径
     */

    public function getPageByTemplateType($template_type, $pageParams = [])
    {
        // 获取template_pages配置
        $templatePages = Config::get('wechat.template_pages');

        if (!isset($templatePages[$template_type])) {
            // 如果没有配置对应的页面，返回默认首页
            Log::warning("未找到模板类型 {$template_type} 对应的页面配置，使用默认首页");
            return 'pages/index/index';
        }

        $basePage = $templatePages[$template_type];

        // 处理特殊情况：reply模板需要动态拼接wallOwnerId和publishId参数
        if ($template_type === 'reply' && isset($pageParams['wallOwnerId'])) {
            // reply模板的配置是 'pages/comment/comment?wallOwnerId='
            // 需要拼接实际的wallOwnerId值
            if (strpos($basePage, '?wallOwnerId=') !== false) {
                $basePage = $basePage . $pageParams['wallOwnerId'];

                // 如果还有publishId参数，继续拼接
                if (isset($pageParams['publishId'])) {
                    $basePage = $basePage . '&publishId=' . $pageParams['publishId'];
                }
            }
        }

        // 处理其他可能的参数拼接
        if (!empty($pageParams) && $template_type !== 'reply') {
            $queryString = http_build_query($pageParams);
            $separator = strpos($basePage, '?') !== false ? '&' : '?';
            $basePage = $basePage . $separator . $queryString;
        }

        Log::info("模板类型 {$template_type} 匹配到页面路径: {$basePage}");

        return $basePage;
    }
}
<?php

namespace app\common\service;

use app\common\helper\UrlHelper;
use Exception;
use think\facade\Config;
use think\facade\Log;

class WechatService
{
    // 小程序配置
    protected $appId;
    protected $appSecret;

    // 构造函数
    public function __construct()
    {
        // 获取配置
        $wxConfig = Config::get('wechat.');
        $this->appId = $wxConfig['appid'] ?? '';
        $this->appSecret = $wxConfig['appsecret'] ?? '';
    }

    /**
     * 通过code获取openid
     * @param string $code
     * @return array
     */
    public function getOpenidByCode($code)
    {
        $url = "https://api.weixin.qq.com/sns/jscode2session";
        $params = [
            'appid' => $this->appId,
            'secret' => $this->appSecret,
            'js_code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $result = $this->httpGet($url, $params);
        $data = json_decode($result, true);

        if (!$data || isset($data['errcode'])) {
            Log::error('获取openid失败：' . $result);
            return [];
        }

        return [
            'openid' => $data['openid'] ?? '',
            'unionid' => $data['unionid'] ?? '',
            'session_key' => $data['session_key'] ?? ''
        ];
    }

    /**
     * 发送GET请求
     * @param string $url
     * @param array $params
     * @return string
     */
    protected function httpGet($url, $params = [])
    {
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $result = curl_exec($curl);
        curl_close($curl);

        return $result;
    }

    /**
     * 获取小程序二维码（数量限制100,000）
     * @param string $path 扫码进入的小程序页面路径
     * @param int $width 二维码的宽度，单位 px，最小 280px，最大 1280px
     * @return string 返回二维码图片URL
     */
    public function getQRCode($path, $width = 280)
    {
        // 获取access_token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('获取access_token失败');
            return '';
        }

        Log::info('开始生成二维码，参数：', ['path' => $path, 'width' => $width]);

        $url = "https://api.weixin.qq.com/wxa/getwxacode?access_token={$accessToken}";
        $data = [
            'path' => $path,
            'width' => $width,
            'auto_color' => false,
            'line_color' => ['r' => 0, 'g' => 0, 'b' => 0],
            'is_hyaline' => false,
            'env_version' => 'release'
        ];

        $result = $this->httpPost($url, json_encode($data));

        // 判断是否为JSON返回（表示错误）
        $jsonData = json_decode($result, true);
        if (is_array($jsonData) && isset($jsonData['errcode'])) {
            Log::error('生成二维码失败：' . json_encode($jsonData));
            return '';
        }

        // 返回的是二进制图片数据，需要保存
        $savePath = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcode' . DIRECTORY_SEPARATOR;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $fileName = md5('qrcode_' . $path . $width) . '.jpg';
        file_put_contents($savePath . $fileName, $result);

        return UrlHelper::generateQrcodeUrl($fileName);
    }

    /**
     * 获取小程序access_token（使用稳定版接口）
     * @return string
     */
    protected function getAccessToken()
    {
        // 从缓存获取
        $cacheKey = 'wx_stable_access_token';
        $accessToken = cache($cacheKey);

        if ($accessToken) {
            Log::info('从缓存获取access_token成功');
            return $accessToken;
        }

        // 使用新的稳定版access_token接口
        $url = "https://api.weixin.qq.com/cgi-bin/stable_token";
        $data = [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret
        ];

        Log::info('请求稳定版access_token：', [
            'url' => $url,
            'appid' => $this->appId,
            'secret' => substr($this->appSecret, 0, 8) . '...'
        ]);

        $result = $this->httpPost($url, json_encode($data));
        $responseData = json_decode($result, true);

        Log::info('稳定版access_token响应：', [
            'response' => $result
        ]);

        if (!$responseData || isset($responseData['errcode'])) {
            Log::error('获取稳定版access_token失败：' . $result);

            // 如果稳定版失败，尝试传统方式
            Log::info('尝试使用传统方式获取access_token');
            return $this->getAccessTokenLegacy();
        }

        $accessToken = $responseData['access_token'];
        $expiresIn = $responseData['expires_in'] - 200; // 提前200秒过期

        // 缓存access_token
        cache($cacheKey, $accessToken, $expiresIn);

        Log::info('获取稳定版access_token成功：' . substr($accessToken, 0, 20) . '...');

        return $accessToken;
    }

    /**
     * 发送POST请求
     * @param string $url
     * @param string $data
     * @return string
     */
    protected function httpPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);

        // 设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ]);

        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        // 记录请求详情
        Log::info('HTTP POST请求详情：', [
            'url' => $url,
            'data' => $data,
            'http_code' => $httpCode,
            'response' => $result,
            'curl_error' => $error
        ]);

        return $result;
    }

    /**
     * 获取小程序access_token（传统方式，作为备用）
     * @return string
     */
    protected function getAccessTokenLegacy()
    {
        $url = "https://api.weixin.qq.com/cgi-bin/token";
        $params = [
            'grant_type' => 'client_credential',
            'appid' => $this->appId,
            'secret' => $this->appSecret
        ];

        $result = $this->httpGet($url, $params);
        $data = json_decode($result, true);

        if (!$data || isset($data['errcode'])) {
            Log::error('获取传统access_token失败：' . $result);
            return '';
        }

        $accessToken = $data['access_token'];
        $expiresIn = $data['expires_in'] - 200; // 提前200秒过期

        // 缓存access_token
        cache('wx_legacy_access_token', $accessToken, $expiresIn);

        Log::info('获取传统access_token成功：' . substr($accessToken, 0, 20) . '...');

        return $accessToken;
    }

    /**
     * 获取小程序码（无数量限制）
     * @param string $page 必须是已经发布的小程序存在的页面
     * @param string $scene 场景值，最大32个可见字符
     * @param int $width 二维码的宽度，单位 px，最小 280px，最大 1280px
     * @return string 返回二维码图片URL
     */
    public function generateQRCode($page, $scene, $width = 280)
    {
        try {
            // 获取access_token
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('获取access_token失败');
                return '';
            }

            // 确保页面路径格式正确
            $page = ltrim($page, '/');

            // 打印输入参数
            Log::info('generateQRCode输入参数：' . json_encode([
                    'page' => $page,
                    'scene' => $scene,
                    'width' => $width
                ], JSON_UNESCAPED_UNICODE));

            // 打印access_token
            Log::info('当前access_token：' . $accessToken);

            $url = "https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token={$accessToken}";
            $data = [
                'scene' => $scene,
                'page' => $page,
                'width' => $width,
                'auto_color' => false,
                'line_color' => ['r' => 0, 'g' => 0, 'b' => 0],
                'is_hyaline' => false,
                'env_version' => 'release'
            ];

            // 打印请求数据
            Log::info('请求微信接口数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));

            $result = $this->httpPost($url, json_encode($data));


            // 判断是否为JSON返回（表示错误）
            // $jsonData = json_decode($result, true);
            // if (is_array($jsonData) && isset($jsonData['errcode'])) {
            //     Log::error('生成小程序码失败：' . json_encode($jsonData, JSON_UNESCAPED_UNICODE));
            //     return '';
            // }
            if (strpos($result, '{') === 0) {
                $jsonData = json_decode($result, true);
                Log::error('生成小程序码失败：' . json_encode($jsonData, JSON_UNESCAPED_UNICODE));
                $resArr = json_decode($result, true);
                return '';
            }

            // 返回的是二进制图片数据，需要保存
            $savePath = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcode' . DIRECTORY_SEPARATOR;
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }

            $fileName = md5('wxacode_' . $page . $scene) . '.jpg';
            $saveResult = file_put_contents($savePath . $fileName, $result);

            // 打印保存结果
            Log::info('保存二维码结果：' . ($saveResult ? '成功' : '失败') . '，保存路径：' . $savePath . $fileName);

            return UrlHelper::generateQrcodeUrl($fileName);

        } catch (Exception $e) {
            Log::error('生成小程序码异常：' . $e->getMessage());
            return '';
        }
    }

    /**
     * 将URL生成二维码
     * @param string $url
     * @return string
     */
    public function generateQRCodeByUrl($url)
    {
        // 此处使用第三方服务生成二维码，实际开发中可以集成专门的二维码生成库
        $qrcodeApi = "https://api.qrserver.com/v1/create-qr-code/?size=280x280&data=" . urlencode($url);

        // 保存二维码
        $savePath = dirname(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'qrcode' . DIRECTORY_SEPARATOR;
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        $fileName = md5($url) . '.jpg';
        $result = $this->httpGet($qrcodeApi);
        file_put_contents($savePath . $fileName, $result);

        return UrlHelper::generateQrcodeUrl($fileName);
    }

    /**
     * 生成微信小程序官方短链
     * @param string $pageUrl 形如 pages/index/index?wallOwnerId=xxx
     * @param string $title 短链标题
     * @param bool $isPermanent 是否永久
     * @return string|false 成功返回短链，失败返回false
     */
    public function generateWxShortLink($pageUrl, $title = '', $isPermanent = true)
    {
        try {
            $accessToken = $this->getAccessToken();
            if (!$accessToken) {
                Log::error('获取access_token失败，无法生成小程序短链');
                return false;
            }

            // 打印输入参数
            Log::info('generateWxShortLink输入参数：' . json_encode([
                    'pageUrl' => $pageUrl,
                    'title' => $title,
                    'isPermanent' => $isPermanent
                ], JSON_UNESCAPED_UNICODE));

            $api = "https://api.weixin.qq.com/wxa/genwxashortlink?access_token={$accessToken}";
            $data = [
                'page_url' => $pageUrl,
                'page_title' => $title ?: '表白墙',
                'is_permanent' => $isPermanent
            ];

            // 打印请求数据
            Log::info('请求微信短链接口数据：' . json_encode($data, JSON_UNESCAPED_UNICODE));

            $result = $this->httpPost($api, json_encode($data, JSON_UNESCAPED_UNICODE));
            $resArr = json_decode($result, true);

            if (isset($resArr['errcode']) && $resArr['errcode'] == 0 && !empty($resArr['link'])) {
                Log::info('生成短链成功：' . $resArr['link']);
                return $resArr['link'];
            } else {
                Log::error('生成微信小程序短链失败: ' . json_encode($resArr, JSON_UNESCAPED_UNICODE));
                return false;
            }
        } catch (Exception $e) {
            Log::error('生成微信小程序短链异常：' . $e->getMessage());
            return false;
        }
    }

    /**
     * 生成小程序URL Link
     * @param array $params 请求参数
     * @return array
     */
    public function generateUrlLink($params)
    {
        // 获取access_token
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            Log::error('获取access_token失败');
            return ['errcode' => -1, 'errmsg' => '获取access_token失败'];
        }

        $url = "https://api.weixin.qq.com/wxa/generate_urllink?access_token={$accessToken}";

        // 记录请求参数
        Log::info('生成URL Link请求参数：', $params);

        $result = $this->httpPost($url, json_encode($params));
        $data = json_decode($result, true);

        if (!$data) {
            Log::error('生成URL Link失败，返回数据为空');
            return ['errcode' => -1, 'errmsg' => '返回数据为空'];
        }

        // 记录响应结果
        Log::info('生成URL Link响应结果：', $data);

        if (isset($data['errcode']) && $data['errcode'] != 0) {
            Log::error('生成URL Link失败：' . json_encode($data));
            return $data;
        }

        return $data;
    }
}
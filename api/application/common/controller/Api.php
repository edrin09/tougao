<?php

namespace app\common\controller;

use app\common\model\User;
use think\Controller;
use think\Exception;
use think\facade\Log;
use think\response\Json;

class Api extends Controller
{
    // 无需登录的方法
    protected $noNeedLogin = [];

    // 无需权限的方法
    protected $noNeedAuth = [];

    // 需要openid验证但不需要完整登录的方法
    protected $needOpenidOnly = [];

    // 当前登录用户ID
    protected $userId = null;

    // 当前用户是否为墙主
    protected $isWallMaster = false;

    // 当前用户openid
    protected $openid = null;

    // 当前用户信息
    protected $userInfo;

    /**
     * 返回成功消息
     * @param string $msg 提示信息
     * @param string|null $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param int $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return Json
     */
    public function success($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $result = [
            'code' => 200,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        // 添加中间件执行代码
        $path = $this->request->pathinfo();

        //先执行请求，获取响应对象
        Log::notice("**************************************");
        Log::notice('[请求路由]' . $path);
        Log::notice('[请求参数]' . json_encode($this->request->param()));
        Log::notice('[返回参数]' . json_encode($result, true));
        Log::notice("**************************************");

        return json($result)->header($header);
    }

    /**
     * 重写验证规则
     * @param array $data
     * @param array|string $validate
     * @param array $message
     * @param bool $batch
     * @param null $callback
     * @return array|bool|string|true
     */
    public function validate($data, $validate, $message = [], $batch = false, $callback = null)
    {
        try {
            parent::validateFailException();
            parent::validate($data, $validate, $message, $batch);
        } catch (Exception $e) {
            throwError($e->getMessage(), $e->getCode());
        }
        return true;
    }

    /**
     * 返回错误消息
     * @param string $msg 提示信息
     * @param string|null $url 跳转的URL地址
     * @param mixed $data 返回的数据
     * @param int $wait 跳转等待时间
     * @param array $header 发送的Header信息
     * @return Json
     */
    public function error($msg = '', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $code = isset($header['code']) ? $header['code'] : 400;
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];
        return json($result)->header($header);
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();

        // 跨域请求处理
        $this->handleCors();

        // 检查是否需要登录
        $this->checkLogin();
    }

    /**
     * 处理跨域请求
     */
    protected function handleCors()
    {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

        if ($this->request->isOptions()) {
            exit();
        }
    }

    /**
     * 检查登录状态
     */
    protected function checkLogin()
    {
        // 检查当前操作是否需要登录
        if (in_array($this->request->action(), $this->noNeedLogin)) {
            return true;
        }

        // 检查是否只需要openid验证
        if (in_array($this->request->action(), $this->needOpenidOnly)) {
            return $this->checkOpenidOnly();
        }

        // 获取token（先从header获取，没有则从参数获取）
        $token = $this->request->header('Authorization');
        if (empty($token)) {
            // 如果header中没有，尝试从参数中获取openid
            $token = $this->request->param('openid');
        }

        if (empty($token)) {
            throwError('请先登录');
        }

        // 验证token
        $userInfo = $this->validateToken($token);
        if (!$userInfo) {
            throwError('登录已过期，请重新登录');
        }

        // 设置用户信息
        $this->userId = $userInfo['userId'];
        $this->isWallMaster = $userInfo['isWallMaster'];
        $this->openid = $userInfo['openid'];
        $this->userInfo = $userInfo;

        // 检查权限
        $this->checkPermission();
    }

    /**
     * 检查openid验证
     */
    protected function checkOpenidOnly()
    {
        // 获取openid参数
        $openid = $this->request->param('openid');

        if (empty($openid)) {
            throwError('请先登录');
        }

        // 简单验证openid格式（微信openid通常是28位字符）
        if (strlen($openid) < 20) {
            throwError('无效的用户标识');
        }

        // 设置openid
        $this->openid = $openid;

        return true;
    }

    /**
     * 验证token
     * @param string $token
     * @return array|bool
     */
    protected function validateToken($token)
    {
        // 如果是Bearer token，去掉Bearer前缀
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }

        // 查询用户信息
        $userModel = new User();
        $user = $userModel->where('openid', $token)->find();

        if (!$user) {
            return false;
        }

        return [
            'userId' => $user['user_id'],
            'isWallMaster' => $user['is_wall_master'],
            'openid' => $user['openid'],
            'wall_id' => $user['wall_id'],
        ];
    }

    /**
     * 检查权限
     */
    protected function checkPermission()
    {
        // 检查当前接口是否需要管理员权限
        $needWallMaster = false;

        // 这里可以通过注解或配置实现更细粒度的权限控制
        $controllerName = strtolower($this->request->controller());
        $actionName = strtolower($this->request->action());

        // 示例：管理模块下的接口需要墙主权限
        if ($controllerName === 'admin' || $controllerName === 'tag' && $actionName === 'manage') {
            $needWallMaster = true;
        }

        if ($needWallMaster && !$this->isWallMaster) {
            throwError('权限不足');
        }
    }

    /**
     * 获取当前登录用户ID
     * @return string|null
     */
    protected function getUserId()
    {
        return $this->userId;
    }

    /**
     * 获取当前用户是否为墙主
     * @return bool
     */
    protected function isWallMaster()
    {
        return $this->isWallMaster;
    }

    /**
     * 获取当前用户的openid
     * @return string|null
     */
    protected function getOpenid()
    {
        return $this->openid;
    }
}
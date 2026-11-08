<?php

namespace app\common\controller;

use app\common\controller\Api;

/**
 * 后台管理基础控制器
 */
class AdminBase extends Api
{
    // 无需登录的方法
    protected $noNeedLogin = [];

    /**
     * 初始化
     */
    protected function initialize()
    {
        // 跨域请求处理
        $this->handleCors();

        // 这里可以添加管理员权限验证逻辑
        // $this->checkAdminAuth();
    }

    /**
     * 返回成功响应
     * @param string $msg 消息
     * @param mixed $url 跳转URL
     * @param mixed $data 数据
     * @param int $wait 等待时间
     * @param array $header 头信息
     * @return \think\response\Json
     */
    public function success($msg = '操作成功', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $result = [
            'code' => 200,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait
        ];

        return json($result)->header($header);
    }

    /**
     * 返回错误响应
     * @param string $msg 消息
     * @param mixed $url 跳转URL
     * @param mixed $data 数据
     * @param int $wait 等待时间
     * @param array $header 头信息
     * @return \think\response\Json
     */
    public function error($msg = '操作失败', $url = null, $data = '', $wait = 3, array $header = [])
    {
        $code = isset($header['code']) ? $header['code'] : 400;
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait
        ];

        return json($result)->header($header);
    }

    /**
     * 检查管理员权限
     * @return bool
     */
    protected function checkAdminAuth()
    {
        // 这里可以添加管理员权限验证逻辑
        // 例如检查session、token等
        return true;
    }
}

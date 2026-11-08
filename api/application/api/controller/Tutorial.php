<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Tutorial as TutorialModel;

/**
 * 小程序端使用教程接口
 */
class Tutorial extends Api
{
    // 无需登录、无需权限
    protected $noNeedLogin = ['get'];
    protected $noNeedAuth  = ['get'];

    /**
     * 获取使用教程内容
     * GET /api/tutorial/get
     * @return \think\response\Json
     */
    public function get()
    {
        $model   = new TutorialModel();
        $content = $model->getContent();
        return $this->success('获取成功', null, ['content' => $content]);
    }
} 
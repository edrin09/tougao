<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\service\WechatPayService;
use think\Request;

class Payment extends Api
{
    protected $noNeedLogin = ['login'];

    protected $noNeedRight = ['*'];

    // 获取支付配置

    public function getJssdkConfig(Request $request)
    {
        $rule['prepay_id'] = 'require';
        $this->validate($request->post(), $rule);
        $prepay_id = $request->post('prepay_id');

        $wechatPayService = new WechatPayService();
        $jssdk = $wechatPayService->getJssdkConfig($prepay_id);

        return $this->success('获取成功', null, ['jssdk' => $jssdk]);
    }

    // 统一下单
    public function saveUnify(Request $request)
    {
        $params = $request->post();
        $rule['amount'] = 'require|number';
        $rule['desc'] = 'require';
        $rule['out_trade_no'] = 'require';
        $this->validate($params, $rule);

        $openid = $this->getOpenid();
        $amount = $params['amount'];
        $desc = $params['desc'];
        $out_trade_no = $params['out_trade_no'];

        $wechatPayService = new WechatPayService();
        $res = $wechatPayService->saveUnify($openid, $amount, $desc, $out_trade_no);

        if ($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
            return $this->success('支付成功', null, ['res' => $res]);
        } else {
            return $this->error('支付失败');
        }
    }
}

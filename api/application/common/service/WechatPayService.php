<?php

namespace app\common\service;

use EasyWeChat\Factory;
use think\facade\Log;

class WechatPayService
{
    protected $app;

    public function __construct()
    {
        $wechat_config = config('wechat.');

        $config = [
            // 必要配置
            'app_id' => $wechat_config['appid'],
            'mch_id' => $wechat_config['mch_id'],
            'key' => $wechat_config['pay_key'],   // API v2 密钥 (注意: 是v2密钥 是v2密钥 是v2密钥)

            // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
            'cert_path' => $wechat_config['cert_path'], // XXX: 绝对路径！！！！
            'key_path' => $wechat_config['key_path'],      // XXX: 绝对路径！！！！
            'notify_url' => 'https://tougao.njlinks.cn/index/wechat_pay/receivePaidNotify',     // 你也可以在下单时单独设置来想覆盖它
        ];

//pr($config);
        $this->app = Factory::payment($config);

// 判断当前是否为沙箱模式：
//        $isSandbox = $this->app->inSandbox();
    }


    /*
     * 获取支付应用实例
     * @return \EasyWeChat\Payment\Application
     */
    public function getApps()
    {
        return $this->app;
    }


    /**
     * 获取JS-SDK支付配置
     * @param $prepayId
     * @return array
     */
    public function getJssdkConfig($prepayId)
    {
        $jssdk = $this->app->jssdk;
        $config = $jssdk->bridgeConfig($prepayId, false); // 返回数组
        return $config;
    }

    /**
     * 统一下单
     * @param $openid
     * @param $amount
     * @param $desc
     * @param $out_trade_no
     * @return array
     */
    public function saveUnify($openid, $amount, $desc, $out_trade_no)
    {
        $params['body'] = $desc;
        $params['out_trade_no'] = $out_trade_no;
        $params['total_fee'] = $amount;
//        $params['notify_url'] = 'https://pay.weixin.qq.com/wxpay/pay.action'; // 支付结果通知网址，如果不设置则会使用配置里的默认地址
        $params['trade_type'] = 'JSAPI'; // 请对应换成你的支付方式对应的值类型
        $params['openid'] = $openid;

//        dd($params);
        $result = $this->app->order->unify($params);
        return $result;
    }


    /*
     * 企业付款到零钱
     * @param $openid
     * @param $amount
     * @param $desc
     * @return array
     */
    public function paymentToChange($openid, $amount, $desc)
    {
        $partner_trade_no = time() . rand(1000, 9999);
        $res = $this->app->transfer->toBalance([
            'partner_trade_no' => $partner_trade_no, // 商户订单号，需保持唯一性(只能是字母或者数字，不能包含有符号)
            'openid' => $openid,
            'check_name' => 'NO_CHECK', // NO_CHECK：不校验真实姓名, FORCE_CHECK：强校验真实姓名
            're_user_name' => '', // 如果 check_name 设置为FORCE_CHECK，则必填用户真实姓名
            'amount' => $amount, // 企业付款金额，单位为分
            'desc' => $desc, // 企业付款操作说明信息。必填
        ]);

        Log::notice('企业付款到零钱结果：' . json_encode($res));
        return $res;
    }


    /**
     * 退款
     * @param $out_trade_no
     * @param $post_id
     * @param $total_fee
     * @param $refund_fee
     * @return array
     */
    public function refund($out_trade_no, $post_id, $total_fee, $refund_fee, $refund_desc)
    {
        Log::notice('微信退款提交参数：' . json_encode([
                'out_trade_no' => $out_trade_no,
                'post_id' => $post_id,
                'total_fee' => $total_fee,
                'refund_fee' => $refund_fee,
                'refund_desc' => $refund_desc,
            ]));

        // 参数分别为：商户订单号、商户退款单号、订单金额、退款金额、其他参数
        $result = $this->app->refund->byOutTradeNumber($out_trade_no, $post_id, $total_fee, $refund_fee, [
            'refund_desc' => $refund_desc,
        ]);

        Log::notice('微信退款结果：' . json_encode($result));

        if (!empty($result['result_code']) && $result['result_code'] != 'SUCCESS') {
            $message = '微信第三方支付单号：' . $out_trade_no . '，退款失败：' . $result['err_code'] . $result['err_code_des'] ?? $result['return_msg'];
            throwError($message);
        }

        return $result;
    }
}
<?php

namespace app\common\service;

use EasyWeChat\Factory;
use Exception;
use think\facade\Log;

class WechatOfficialService
{
    protected $app;

    public function __construct()
    {
        // 公众号凭据统一放在 config/wechat.php 的 official_account 里，不写死进代码
        $config = config('wechat.official_account') ?: [];

        if (empty($config['app_id']) || empty($config['secret'])) {
            throw new Exception(
                '公众号凭据未配置：请在 config/wechat.php 的 official_account 中填写 app_id / secret / token'
            );
        }

        $this->app = Factory::officialAccount([
            'app_id' => $config['app_id'],
            'secret' => $config['secret'],
            'token' => $config['token'] ?? '',
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
        ]);
    }


    /*
     * 获取应用实例
     * @return \EasyWeChat\OfficialAccount\Application
     */
    public function getApps()
    {
        return $this->app;
    }


    /**
     * 发送提现结果通知
     * @param $withdrawal
     * @return bool
     */
    public function sendWithdrawalResult($withdrawal)
    {
        $openid = $withdrawal->user->official_openid;
        if ($openid) {
            $template_id = 'bBEch6KjH87DSY0rPRdlXdmHYHOj79ja_FJHeETVLBg';

            $data['character_string1'] = $withdrawal->withdrawal_no;
            $data['thing2'] = $withdrawal->withdrawal_method == 'wechat' ? '微信零钱' : '线下打款';
            $data['amount3'] = $withdrawal->cash_amount . '元';
            $data['const5'] = $withdrawal->withdraw_status == 5 ? '成功' : '失败';

            $this->sendTemplateMessage($openid, $template_id, $data);
        }
        return true;
    }

    /**
     * 发送模板消息
     * @param $openid
     * @param $template_id
     * @param $data
     * @param array $miniprogram
     * @param string $url
     * @return bool
     */
    public function sendTemplateMessage($openid, $template_id, $data, $miniprogram = [], $url = '')
    {
        $result = $this->app->template_message->send([
            'touser' => $openid,
            'template_id' => $template_id,
            'miniprogram' => $miniprogram,
            'url' => $url,
            'data' => $data,
        ]);
//dd($result);

        if ($result['errcode'] != 0) {
            try {
                Log::error('发送模板消息失败，OpenID：' . $openid . '，模板ID：' . $template_id . '，错误：' . $result['errmsg']);
            } catch (Exception $e) {
                return true;
            }
        }
        return true;
    }


    /*
     * 发送审核结果通知
     * @param $post
     * @return bool
     */
    public function sendAuditResult($post)
    {
        $WechatMessage = new WechatMessage();

        $openid = $post->user->official_openid;
        if ($openid) {
            $template_id = 'kvanrli7tLAdFpGGya6dxOMya3lXmPIF-o_Ri2EkT0k';

            $config = config('wechat.');
            $miniprogram_appid = $config['appid'];
            $data['character_string3'] = $post->post_id;
            $data['thing1'] = $post->title;

            $miniprogram['appid'] = $miniprogram_appid;
            $miniprogram['pagepath'] = $WechatMessage->getPageByTemplateType('review_result');

            switch ($post->status) {
                case 1:
                    $data['const6'] = '通过';
                    break;
                case -1:
                    $data['const6'] = '驳回';
                    break;
                default:
                    $data['const6'] = '';
                    break;
            }

//            dd($data);
            $this->sendTemplateMessage($openid, $template_id, $data, $miniprogram);
        }
        return true;
    }


    /*
     * 发送待审核结果通知
     * @param $post
     * @return bool
     */
    public function sendPendingReviewResult($title, $user_name, $pending_posts, $openid)
    {
        $WechatMessage = new WechatMessage();

        if ($openid) {
            $template_id = 'Mdx5NMtImaP1mueF53LjiyT-eR3ss95Bs-QaIOThbqE';

            $config = config('wechat.');
            $miniprogram_appid = $config['appid'];


            $data['thing4'] = $title;
            $data['thing5'] = $user_name;
            $data['time6'] = date('Y-m-d H:i:s');
            $data['character_string7'] = $pending_posts;

            $miniprogram['appid'] = $miniprogram_appid;
            $miniprogram['pagepath'] = $WechatMessage->getPageByTemplateType('new_post');


//            dd($data);
            $this->sendTemplateMessage($openid, $template_id, $data, $miniprogram);
        }
        return true;
    }


    public function sendAppStatusResult($user, $no, $app_status)
    {
        $openid = $user['official_openid'];

        switch ($app_status) {
            case 1:
                $template_id = 'Au57XAxdwDA2iG9BXYKqHxZ6IsGwcR8BRNR-9wNpNhE';
                $status_text = '发送成功';
                break;
            case 0:
                $template_id = '1ewTFWue6WjItRGJY314IvoxF4Z9twhgv6OYu3_uugQ';
                $status_text = '发送失败';
                break;
            default:
                $template_id = '';
                $status_text = '';
                break;
        }

        if ($openid) {
            $data['character_string2'] = $no;
//            $data['character_string2'] = '墙墙智能投稿合集' . $no;
            $data['phrase3'] = $status_text;

//            print_r($data);die;
            $this->sendTemplateMessage($openid, $template_id, $data);
        }

        return true;
    }
}
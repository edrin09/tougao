<?php

namespace app\index\controller;

use app\common\model\User;
use app\common\service\WechatOfficialService;
use think\facade\Log;


class Official
{
    //Service configuration of Weixin Official Accounts
    public function ConfigurationService()
    {
        $WechatOfficialService = new WechatOfficialService();
        $app = $WechatOfficialService->getApps();

        $app->server->push(function ($message) use ($app) {
            if ($message['MsgType'] == 'event') {
                if ($message['Event'] == 'subscribe') {
                    $official_openid = $message['FromUserName'];
                    $official_user = $app->user->get($official_openid);
                    Log::info($official_user);

                    $user = User::where('unionid', $official_user['unionid'])->find();
                    if ($user) {
                        $user->official_openid = $official_user['openid'];
                        $user->is_official_subscribe = 1;
                        $user->save();
                    }

                    return '关注成功';
                }

                if ($message['Event'] == 'unsubscribe') {
//                    Log::info($message);
                    $official_openid = $message['FromUserName'];

                    $user = User::where('official_openid', $official_openid)->find();
                    if ($user) {
                        $user->official_openid = '';
                        $user->is_official_subscribe = 0;
                        $user->save();
                    }
                }
            }

//            if ($message['MsgType'] == 'text') {
//                $openid = $message['FromUserName'];
//                $user = $app->user->get($openid);
//
//                Log::info($user);
//                return '收到文字消息';
//            }
        });

        $response = $app->server->serve();
        $response->send();
    }
}

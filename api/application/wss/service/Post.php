<?php

namespace app\wss\service;


use app\common\service\WechatOfficialService;
use app\wss\service\comm\DatabasesService;
use app\wss\service\comm\QueryBuilder;

class Post extends DatabasesService
{
    public $query;

    public function __construct()
    {
        parent::__construct();
        $db = DatabasesService::getInstance();
        $this->query = new QueryBuilder($db);
    }

    public function saveStatusMoment($wall_id, $app_status, $no, $list)
    {
//        print_r($status);
//        print_r($list);
        $post_ids = array_column($list, 'post_id');

        $this->query->reset();
        $affected_rows = $this->query->table('cw_post')
            ->whereIn('post_id', $post_ids)
            ->update([
                'app_status' => $app_status,
                'update_time' => date('Y-m-d H:i:s')
            ]);

        $this->sendMessage($wall_id, $app_status, $no);
        return ['status' => 200, 'message' => '更新成功', 'data' => $affected_rows];
    }

    public function sendMessage($wall_id, $app_status, $no)
    {
        $this->query->reset();
        $user = $this->query->table('cw_user as u')
            ->fields(['u.user_id', 'u.official_openid', 'u.is_official_subscribe', 'w.wall_name'])
            ->join('cw_wall_settings as w', 'w.wall_owner_id = u.user_id')
            ->where('w.id', $wall_id)
            ->getOne();
        if ($user) {
            $WechatOfficialService = new WechatOfficialService();
            $WechatOfficialService->sendAppStatusResult($user, $no, $app_status);
        }

        return true;
    }
}
<?php

namespace app\index\controller;

use app\common\model\User;
use app\common\model\WallSettings;

class Index
{
    public function index()
    {
        return 'hello world';
    }

    public function hello()
    {
//        $list = User::where('is_wall_master', 1)->where('wall_id', 0)->select();
//
//        foreach ($list as $item) {
//            $info = WallSettings::where('wall_owner_id', $item->user_id)->find();
//
////            dd($info);
//            if ($info) {
//                $item->wall_id = $info->id;
//                $item->save();
//            }
//        }

        return 'success';
    }
}

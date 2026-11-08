<?php

namespace app\index\controller;

use app\common\model\UserPosterSettings;
use think\Controller;
use think\Exception;
use think\facade\Config;

class Poster extends Controller
{
    public function index()
    {
        $user_id = input('user_id', '');
        $is_contact = input('is_contact', 1);
        $info = UserPosterSettings::find($user_id);

        $time_font_size = '24px';
        $text_font_size = '24px';
        $tag_font_size = '30px';

        if (empty($is_contact)) {

            $time_font_size = '24px';
            $text_font_size = '40px';
            $tag_font_size = '50px';

            $info->contact = '';
            $info->images = '';


            // 获取上传配置
            $uploadConfig = Config::get('upload.');
            // 使用配置文件中的海报保存路径
            $posterDir = $uploadConfig['save_path'];
            //逗号转数组
            if ($info->images) {
                $images_list = [];
//                foreach (explode(',', $info->images) as $image) {
//
//                    //去除域名、指定要解码的图片路径
//                    $imagePath = $posterDir . str_replace(request()->domain() . '/uploads', '', $image);
//                    // 解码图片
//                    $result = $this->detectQRCode($imagePath);
////                    pr($image);
////                    pr($decodedData);
//                    // 处理解码结果
//                    if ($result['success']) {
//                        $images_list[] = $image;
//                    }
//                }

                //数组转字符串
                $info->images = $images_list ? implode(',', $images_list) : '';
            }
        }
        // 背景样式
        $bg_style = !empty($info->custom_bg_image) ?
            "background-image: url('" . $info->custom_bg_image . "'); background-size: cover; background-position: center;" :
            "background-color: " . $info->bg_color . ";";

        // 图片处理
        $images = isset($info->images) ? explode(',', $info->images) : [];
        // 去除空白和无效链接
        $images = array_values(array_filter($images, function ($img) {
            return trim($img) !== '';
        }));

//dd($bg_style);
        $this->assign('time_font_size', $time_font_size);
        $this->assign('text_font_size', $text_font_size);
        $this->assign('tag_font_size', $tag_font_size);
        $this->assign('bg_style', $bg_style);
        $this->assign('images', $images);
        $this->assign('time', date('Y-m-d H:i:s'));
        $this->assign('info', $info);

        return $this->fetch();
    }

    public function test()
    {
//        $image = 'https://tougao.njlinks.cn/uploads/images/20251104/de0478d09b5a19bee8ffce8e870fee15.jpg';

        $image = input('image', '');
        if ($image) {
            // 获取上传配置
            $uploadConfig = Config::get('upload.');
            // 使用配置文件中的海报保存路径
            $posterDir = $uploadConfig['save_path'];


            //去除域名、指定要解码的图片路径
            $imagePath = $posterDir . str_replace(request()->domain() . '/uploads', '', $image);

            $decoder = new PHPZxingDecoder();
            $decoder->setJavaPath('/www/server/java/jdk-21.0.2/bin/java');

            $decodedData = $decoder->decode($imagePath);

            pr($decodedData);
        }

        dd($image);
    }
}
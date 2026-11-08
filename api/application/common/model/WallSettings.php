<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;
use app\common\service\WechatService;
use Exception;
use think\facade\Config;
use think\facade\Log;
use think\model\relation\BelongsTo;

class WallSettings extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'cw_wall_settings';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // JSON类型字段
    protected $json = ['review_rules', 'notification_config', 'post_categories', 'custom_fields'];

    // JSON数据返回数组
    protected $jsonAssoc = true;

    // 字段列表
    protected $fields = [
        'id', 'wall_owner_id', 'wall_name', 'description', 'logo_url',
        'theme_color', 'banner_url', 'qrcode_url', 'auto_review',
        'review_rules', 'notification_config', 'post_categories',
        'custom_fields', 'contact_info', 'status', 'create_time',
        'update_time', 'miniapp_short_link', 'miniapp_qrcode',
        'miniapp_external_link', 'is_vip', 'submission_reply',
        'scrolling_announcement', 'post_template', 'require_phone_auth',
        'owner_name_image', 'custom_bg_image', 'owner_avatar',
        'new_post_notification', 'management_key', 'homepage_notice_image',
        'comment_area_short_link', 'publish_template'
    ];

    /**
     * 获取墙主设置
     * @param string $wallOwnerId
     * @return WallSettings
     */
    public function getWallSettings($wallOwnerId)
    {
        return $this->where('wall_owner_id', $wallOwnerId)->find();
    }


    /**
     * 获取默认墙主信息
     * @return WallMasterApply|WallSettings|array
     */
//    public function getWallInfo($wall_id)
//    {
//        $info = $this->where('cw_wall_settings.status', 1)
//            ->where('cw_wall_settings.id', $wall_id)
//            ->field('id,wall_owner_id,wall_name,description,logo_url')
//            ->withJoin(['wallOwner' => ['user_id', 'nickname', 'avatar']])
//            ->find();
//
//        $channels = WallChannels::where('wall_id', $wall_id)
//            ->field('wall_id,channels_id,channel_account,channel_fans_numbers,channel_fans_costs')
//            ->withJoin(['channels' => ['channels_id', 'channels_name']])
//            ->select();
//
//        $datas['info'] = $info;
//        $datas['channels'] = $channels;
//
//        return $datas;
////        return
//    }


    /**
     * 创建或更新墙主设置
     * @param string $wallOwnerId
     * @param array $data
     * @return bool
     */
    public function saveWallSettings($wallOwnerId, $data)
    {
        $settings = $this->where('wall_owner_id', $wallOwnerId)->find();

        if (!$settings) {
            // 创建新设置
            $data['wall_owner_id'] = $wallOwnerId;
            return $this->save($data);
        } else {
            // 更新设置
            return $settings->save($data);
        }
    }

    /**
     * 检查是否为墙主
     * @param string $userId
     * @return bool
     */
    public function isWallOwner($userId)
    {
        return $this->where('wall_owner_id', $userId)->count() > 0;
    }

    /**
     * 生成默认墙主设置
     * @param string $wallOwnerId
     * @param string $wallName
     * @param bool $isVip
     * @param string $wallAvatar
     * @return WallSettings|bool
     */
    public function generateDefaultSettings($wallOwnerId, $wallName = '', $isVip = false, $wallAvatar = '')
    {
        if (empty($wallName)) {
            // 查询用户昵称
            $userModel = new User();
            $user = $userModel->where('user_id', $wallOwnerId)->find();
            $wallName = $user ? $user['nickname'] . '的校园墙' : '新建校园墙';
        }

        // 生成短链
        $wechatConfig = Config::get('wechat.');
        $page = (config('wechat.publish_page') ?: 'pages/index/index') . '?wallOwnerId=' . $wallOwnerId;
        $shortLink = $this->generateShortLink($page, $wallOwnerId);

        // 生成评论区短链
//        $commentPage = $wechatConfig['comment_page'] . '?wallOwnerId=' . $wallOwnerId ?? 'pages/comment/comment';
//        $commentShortLink = $this->generateShortLink($commentPage, $wallOwnerId);

        // 生成小程序二维码
        $qrcode = $this->generateMiniappQrcode($wallOwnerId);

        // 生成评论区小程序短链
//        $commentAreaShortLink = $this->generateCommentAreaShortLink($wallOwnerId);

        $data = [
            'wall_owner_id' => $wallOwnerId,
            'wall_name' => $wallName,
            'description' => '欢迎来到' . $wallName,
            'logo_url' => '',
            'theme_color' => '#2979ff',
            'banner_url' => '',
            'qrcode_url' => $qrcode['qrcode_url'] ?? '',
//            'comment_area_short_link' => $commentAreaShortLink,
            'auto_review' => 0,
            'review_rules' => json_encode([
                'keywords' => [],
                'imageCheck' => true
            ]),
            'notification_config' => json_encode([
                'newPost' => true,
                'reviewResult' => true
            ]),
            'contact_info' => '',
            'miniapp_short_link' => $shortLink['short_code'] ?? '',
            'miniapp_qrcode' => $qrcode['qrcode_url'] ?? '',
            'miniapp_external_link' => $shortLink['external_link'] ?? '',
            'is_vip' => $isVip ? 1 : 0,
            'submission_reply' => '',
            'scrolling_announcement' => '',
            'post_template' => '',
            'require_phone_auth' => 0,
            'owner_name_image' => '',
            'custom_bg_image' => '',
            'owner_avatar' => $wallAvatar,
            'new_post_notification' => 0,
            'management_key' => md5(uniqid() . time()),
            'homepage_notice_image' => '',
            'status' => 1,
//            'comment_area_short_link' => $commentShortLink['short_code'] ?? ''
        ];

        $res = $this->create($data);
        return $res->id;
    }

    /**
     * 生成短链接
     * @param string $wallOwnerId
     * @return array
     */
    protected function generateShortLink($page, $wallOwnerId)
    {
        try {
            // 调用微信小程序接口生成短链
            $wechatService = new WechatService();

            // 页面路径 - 直接使用传入的page，因为已经包含了wallOwnerId参数
            $pageUrl = $page;
            $title = '';

            Log::info('开始生成短链：', [
                'page' => $page,
                'wallOwnerId' => $wallOwnerId,
                'pageUrl' => $pageUrl,
                'title' => $title
            ]);

            // 生成微信官方短链
            $wxShortLink = $wechatService->generateWxShortLink($pageUrl, $title, true);

            // 如果生成失败，生成一个临时的短链标识
            if (!$wxShortLink) {
                Log::error('生成短链失败：微信接口返回false，使用临时短链', [
                    'pageUrl' => $pageUrl,
                    'title' => $title
                ]);

                // 生成一个临时的短链标识
                $tempShortLink = 'temp_' . md5($pageUrl . time());

                return [
                    'short_code' => $tempShortLink,
                    'external_link' => 'https://mp.weixin.qq.com/s/' . $tempShortLink
                ];
            }

            Log::info('生成短链成功：', [
                'pageUrl' => $pageUrl,
                'shortLink' => $wxShortLink
            ]);

            // 返回微信官方短链
            return [
                'short_code' => $wxShortLink,
                'external_link' => $wxShortLink
            ];
        } catch (Exception $e) {
            Log::error('生成短链异常：' . $e->getMessage(), [
                'page' => $page,
                'wallOwnerId' => $wallOwnerId,
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'short_code' => '',
                'external_link' => ''
            ];
        }
    }

    /**
     * 生成小程序二维码
     * @param string $wallOwnerId
     * @return array
     */
    protected function generateMiniappQrcode($wallOwnerId)
    {
        // 调用微信小程序接口生成二维码
        $wechatService = new WechatService();
        $scene = 'id=' . $wallOwnerId;
        $page = config('wechat.publish_page') ?: 'pages/index/index';
        // 使用generateQRCode方法生成二维码
        $qrcodeUrl = $wechatService->generateQRCode($page, $scene);

        return [
            'qrcode_url' => $qrcodeUrl
        ];
    }

    /**
     * 生成评论区小程序短链
     * @param string $wallOwnerId
     * @return string
     */
    private function generateCommentAreaShortLink($wallOwnerId)
    {
        try {
            // 使用微信服务生成小程序短链
            $wxService = new WechatService();
            $pageUrl = 'pages/comment/comment?wallOwnerId=' . $wallOwnerId;
            $title = '公共评论区';
            $shortLink = $wxService->generateWxShortLink($pageUrl, $title, true);

            // 如果生成失败，返回临时短链
            if (!$shortLink) {
                Log::error('生成评论区短链失败，使用临时短链', [
                    'pageUrl' => $pageUrl,
                    'wallOwnerId' => $wallOwnerId
                ]);
                return 'temp_comment_' . md5($pageUrl . time());
            }

            return $shortLink;
        } catch (Exception $e) {
            Log::error('生成评论区短链异常: ' . $e->getMessage());
            return 'temp_comment_' . md5('comment_' . $wallOwnerId . time());
        }
    }

    /**
     * 获取VIP状态
     * @param string $wallOwnerId
     * @return bool
     */
    public function getVipStatus($wallOwnerId)
    {
        $settings = $this->where('wall_owner_id', $wallOwnerId)->find();
        return $settings ? (bool)$settings['is_vip'] : false;
    }

    /**
     * 更新VIP状态
     * @param string $wallOwnerId
     * @param bool $isVip
     * @return bool
     */
    public function updateVipStatus($wallOwnerId, $isVip)
    {
        return $this->where('wall_owner_id', $wallOwnerId)->update(['is_vip' => $isVip ? 1 : 0]);
    }

    /**
     * 更新小程序相关设置
     * @param string $wallOwnerId
     * @param array $data
     * @return bool
     */
    public function updateMiniAppSettings($wallOwnerId, $data)
    {
        $updateData = [];

        // 只提取小程序相关字段
        if (isset($data['miniapp_short_link'])) {
            $updateData['miniapp_short_link'] = $data['miniapp_short_link'];
        }

        if (isset($data['miniapp_qrcode'])) {
            $updateData['miniapp_qrcode'] = $data['miniapp_qrcode'];
        }

        if (isset($data['miniapp_external_link'])) {
            $updateData['miniapp_external_link'] = $data['miniapp_external_link'];
        }

        if (empty($updateData)) {
            return true;
        }

        return $this->where('wall_owner_id', $wallOwnerId)->update($updateData);
    }

    /**
     * 更新墙主定制设置
     * @param string $wallOwnerId
     * @param array $data
     * @return bool
     */
    public function updateCustomSettings($wallOwnerId, $data)
    {
        $updateData = [];

        // 提取新增的自定义字段
        $customFields = [
            'submission_reply', 'scrolling_announcement', 'post_template',
            'require_phone_auth', 'owner_name_image', 'custom_bg_image',
            'owner_avatar', 'new_post_notification', 'management_key',
            'home_announcement_image'
        ];

        foreach ($customFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return true;
        }

        return $this->where('wall_owner_id', $wallOwnerId)->update($updateData);
    }

    /**
     * 关联墙主用户
     * @return BelongsTo
     */
    public function wallOwner()
    {
        return $this->belongsTo('User', 'wall_owner_id', 'user_id');
    }
}
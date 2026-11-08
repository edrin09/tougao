<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Channels;
use app\common\model\Config as ConfigModel;
use app\common\model\GroupChat;
use app\common\model\Post as PostModel;
use app\common\model\ShortLink;
use app\common\model\User as UserModel;
use app\common\model\WallChannels;
use app\common\model\WallMasterApply;
use app\common\model\WallSettings;
use app\common\model\Withdraw as WithdrawModel;
use app\common\service\WechatService;
use Exception;
use think\Db;
use think\facade\Config;
use think\Request;
use think\response\Json;

class Wallowner extends Api
{
    protected $noNeedLogin = [];

    protected $wall_id;

    /**
     * 构造函数
     */

    public function __construct()
    {
        parent::__construct();

        // 检查是否为墙主
        if (!$this->isWallMaster()) {
            $this->error('无权限访问', null, 403);
        }

        $wall_info = WallSettings::where('wall_owner_id', $this->userId)
            ->field('id,wall_name,status')
            ->find();

        $this->wall_id = $wall_info ? $wall_info->id : 0;
    }

    /**
     * 生成分享二维码
     * @param Request $request
     * @return Json
     */

    public function generateQRCode(Request $request)
    {
        $page = $request->param('page');
        $scene = $request->param('scene');

        if (empty($page)) {
            return $this->error('跳转页面路径不能为空');
        }

        if (empty($scene)) {
            return $this->error('场景参数不能为空');
        }

        // 微信API调用
        $wxService = new WechatService();
        $result = $wxService->generateQRCode($page, $scene);

        if (empty($result)) {
            return $this->error('生成二维码失败');
        }

        return $this->success('生成成功', null, [
            'qrcodeUrl' => $result
        ]);
    }

    /**
     * 生成墙主专属短链
     * @param Request $request
     * @return Json
     */

    public function generateWallOwnerLink(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');
        $customCode = $request->param('customCode');
        $validDays = $request->param('validDays/d', 365);

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        // 检查墙主是否存在
        $userModel = new UserModel();
        $user = $userModel->where('user_id', $wallOwnerId)->find();

        if (!$user) {
            return $this->error('墙主用户不存在');
        }

        // 获取墙主信息
        $wallSettingsModel = new WallSettings();
        $wallSettings = $wallSettingsModel->getWallSettings($wallOwnerId);

        if (!$wallSettings) {
            // 创建默认设置
            $wallSettingsModel->generateDefaultSettings($wallOwnerId, $user['nickname'] . '的校园墙');
            $wallSettings = $wallSettingsModel->getWallSettings($wallOwnerId);
        }

        // 生成墙主页面的URL
        $pageUrl = url('index/wall/index', ['id' => $wallOwnerId], true, true);

        // 创建短链
        $shortLinkModel = new ShortLink();
        $result = $shortLinkModel->createShortLink($pageUrl, $customCode, $validDays, $wallOwnerId);

        if (!$result) {
            return $this->error('生成短链失败');
        }

        // 生成二维码
        $wxService = new WechatService();
        $qrCodeUrl = $wxService->generateQRCodeByUrl($result['shortUrl']);

        return $this->success('生成成功', null, [
            'shortUrl' => $result['shortUrl'],
            'qrCodeUrl' => $qrCodeUrl,
            'wallOwnerName' => $wallSettings['wall_name'],
            'expireTime' => $result['expireTime'],
            'visitCount' => 0
        ]);
    }

    /**
     * 获取墙主专属链接列表
     * @param Request $request
     * @return Json
     */

    public function wallOwnerLinks(Request $request)
    {
        $page = $request->param('page/d', 1);
        $size = $request->param('size/d', 10);
        $keyword = $request->param('keyword');

        $shortLinkModel = new ShortLink();

        // 查询条件：只查询与墙主关联的短链
        $query = $shortLinkModel->where('wall_owner_id', '<>', 'null');

        // 关键词搜索
        if (!empty($keyword)) {
            $keyword = '%' . $keyword . '%';
            $query->where('short_code|original_url', 'like', $keyword);
        }

        // 查询结果
        $list = $query->order('create_time', 'desc')
            ->paginate($size, false, [
                'page' => $page
            ]);

        // 关联墙主信息
        $wallOwnerIds = [];
        foreach ($list as $link) {
            if (!empty($link['wall_owner_id'])) {
                $wallOwnerIds[] = $link['wall_owner_id'];
            }
        }

        if (!empty($wallOwnerIds)) {
            // 获取墙主信息
            $wallSettingsModel = new WallSettings();
            $wallSettings = $wallSettingsModel->whereIn('wall_owner_id', $wallOwnerIds)->column('wall_name', 'wall_owner_id');

            // 填充墙主名称
            foreach ($list as &$link) {
                $link['wallOwnerName'] = isset($wallSettings[$link['wall_owner_id']]) ?
                    $wallSettings[$link['wall_owner_id']] : '未命名墙面';
            }
        }

        return $this->success('查询成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'size' => $size,
            'list' => $list->items()
        ]);
    }

    /**
     * 获取数据统计
     * @param Request $request
     * @return Json
     */

    public function statistics(Request $request)
    {
        $startDate = $request->param('startDate');
        $endDate = $request->param('endDate');
        $wallOwnerId = $request->param('wallOwnerId');

        // 默认统计最近7天
        if (empty($startDate)) {
            $startDate = date('Y-m-d', strtotime('-7 days'));
        }

        if (empty($endDate)) {
            $endDate = date('Y-m-d');
        }

        // 查询条件
        $where = [];
        if (!empty($wallOwnerId)) {
            $where[] = ['wall_owner_id', '=', $wallOwnerId];
        }

        // 用户总数
        $userModel = new UserModel();
        $userCount = $userModel->count();

        // 新增用户数
        $newUserCount = $userModel->where('create_time', '>=', $startDate . ' 00:00:00')
            ->where('create_time', '<=', $endDate . ' 23:59:59')
            ->count();

        // 投稿总数和待审核数
        $postModel = new PostModel();
        $postWhere = $where;
        $postCount = $postModel->where($postWhere)->count();

        $pendingWhere = $where;
        $pendingWhere[] = ['status', '=', 0];
        $pendingReviewCount = $postModel->where($pendingWhere)->count();

        // 每日统计数据
        $dailyStats = [];
        $currentDate = $startDate;

        while (strtotime($currentDate) <= strtotime($endDate)) {
            $nextDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));

            // 新增用户
            $newUsers = $userModel->where('create_time', '>=', $currentDate . ' 00:00:00')
                ->where('create_time', '<', $nextDate . ' 00:00:00')
                ->count();

            // 新增投稿
            $postWhere = $where;
            $posts = $postModel->where($postWhere)
                ->where('create_time', '>=', $currentDate . ' 00:00:00')
                ->where('create_time', '<', $nextDate . ' 00:00:00')
                ->count();

            // 审核数量
            $reviewWhere = $where;
            $reviewWhere[] = ['review_time', '>=', $currentDate . ' 00:00:00'];
            $reviewWhere[] = ['review_time', '<', $nextDate . ' 00:00:00'];
            $reviews = $postModel->where($reviewWhere)->count();

            $dailyStats[] = [
                'date' => $currentDate,
                'newUsers' => $newUsers,
                'posts' => $posts,
                'reviews' => $reviews
            ];

            $currentDate = $nextDate;
        }

        // 标签统计
        $tagStats = Db::name('cw_tag')
            ->field('tag_id as tagId, tag_name as tagName, use_count as count')
            ->order('use_count', 'desc')
            ->limit(10)
            ->select();

        return $this->success('查询成功', null, [
            'userCount' => $userCount,
            'newUserCount' => $newUserCount,
            'postCount' => $postCount,
            'pendingReviewCount' => $pendingReviewCount,
            'dailyStats' => $dailyStats,
            'tagStats' => $tagStats
        ]);
    }

    /**
     * 获取墙主设置
     * @param Request $request
     * @return Json
     */

    public function getSettings(Request $request)
    {
        $rule['wall_id'] = 'require';
        $this->validate($request->get(), $rule);

        $wall_id = $request->get('wall_id');

        $WallSettingsModel = new WallSettings();
        //是否含有user_前缀
        if (str_contains($wall_id, 'user_')) {
            $settings = $WallSettingsModel->getWallSettings($wall_id);
        } else {
            $settings = $WallSettingsModel->find($wall_id);
        }

        if (!$settings) {
            return $this->error('墙主设置不存在');
        }

        // 获取群聊列表
        $groupChatModel = new GroupChat();
        $groupList = $groupChatModel->where('wall_owner_id', $settings->wall_owner_id)
            ->where('status', 1)
            ->order('weight desc, create_time desc')
            ->select();

        // 将群聊列表转换为数组
        $groupListArray = [];
        if ($groupList) {
            foreach ($groupList as $group) {
                $groupListArray[] = $group->toArray();
            }
        }

        $channels = WallChannels::where('wall_id', $settings->id)
            ->field('wall_id,channels_id,channel_account,channel_fans_numbers,channel_fans_costs')
            ->withJoin(['channels' => ['channels_id', 'channels_name']])
            ->select();

        $total = PostModel::where('wall_owner_id', $settings->wall_owner_id)
            ->where('create_time', '>=', date('Y-m-d') . ' 00:00:00')
            ->where('create_time', '<', date('Y-m-d') . ' 23:59:59')
            ->count();

        // 组合返回数据
        $responseData = [
            'settings' => $settings,
            'groupList' => $groupListArray,
            'channels' => $channels,
            'no' => date('md') . $total
        ];

        return $this->success('获取成功', null, $responseData);

    }

    /**
     * 更新墙主设置
     * @param Request $request
     * @return Json
     */

    public function updateWallSettings(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');

        if (empty($wallOwnerId)) {
            // 如果不指定墙主，则使用当前用户作为墙主
            $wallOwnerId = $this->getUserId();
        }

        // 获取所有参数（除了wallOwnerId）
        $data = $request->param();
        unset($data['wallOwnerId']);
        // 移除wallOwnerId，避免被更新到数据库

        // 处理布尔值字段
        $booleanFields = ['auto_review', 'is_vip', 'require_phone_auth', 'new_post_notification', 'sync_to_space'];
        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $data[$field] ? 1 : 0;
            }
        }

        $wallSettingsModel = new WallSettings();
        $result = $wallSettingsModel->saveWallSettings($wallOwnerId, $data);

        if ($result === false) {
            return $this->error('更新失败');
        }

        // 获取最新设置
        $settings = $wallSettingsModel->getWallSettings($wallOwnerId);

        // 将模型对象转换为数组
        if ($settings) {
            $settings = $settings->toArray();
        }

        // 获取群聊列表
        $groupChatModel = new GroupChat();
        $groupList = $groupChatModel->where('wall_owner_id', $wallOwnerId)
            ->where('status', 1)
            ->order('weight desc, create_time desc')
            ->select();

        // 将群聊列表转换为数组
        $groupListArray = [];
        if ($groupList) {
            foreach ($groupList as $group) {
                $groupListArray[] = $group->toArray();
            }
        }

        return $this->success('更新成功', null, [
            'success' => true,
            'settings' => $settings,
            'groupList' => $groupListArray
        ]);
    }

    /**
     * 申请墙主
     */

    public function applyWallOwner(Request $request)
    {
        $openid = $request->param('openid');
        $isVip = $request->param('is_vip', 0);

        // 根据openid查询用户信息
        $userModel = new UserModel();
        $user = $userModel->where('openid', $openid)->find();
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 是否已经申请过
        $applyModel = new WallMasterApply();
        $apply = $applyModel->where('user_id', $user['user_id'])->find();
        if ($apply) {
            return $this->error('您已经申请过墙主，请等待管理员审核!');
        }

        // 创建申请记录
        $data = [
            'user_id' => $user['user_id'],
            'openid' => $openid,
            'is_vip' => $isVip,
            'status' => 0 // 待审核
        ];

        if ($applyModel->createApply($data)) {
            return $this->success('申请提交成功，请等待审核');
        } else {
            return $this->error('申请提交失败，请稍后重试');
        }
    }

    /**
     * 获取订阅消息模板ID
     * @param Request $request
     * @return Json
     */

    public function getSubscribeTemplates(Request $request)
    {
        try {
            // 从配置文件获取模板ID
            $templates = Config::get('wechat.templates');

            if (empty($templates) || !isset($templates['new_post'])) {
                return $this->error('未配置新投稿提醒模板');
            }

            return $this->success('获取成功', null, [
                'template_id' => $templates['new_post']
            ]);

        } catch (Exception $e) {
            return $this->error('获取订阅消息模板失败：' . $e->getMessage());
        }
    }

    /**
     * 获取审核通知消息模板ID
     */

    public function getReviewResultTemplate(Request $request)
    {
        try {
            // 从配置文件获取模板ID
            $templates = Config::get('wechat.templates');

            if (empty($templates) || !isset($templates['review_result'])) {
                return $this->error('未配置审核结果通知模板');
            }

            return $this->success('获取成功', null, [
                'template_id' => $templates['review_result']
            ]);
        } catch (Exception $e) {
            return $this->error('获取审核通知模板失败：' . $e->getMessage());
        }
    }

    // 获取回复提醒消息模板ID

    public function getReplyNoticeTemplate(Request $request)
    {
        try {
            // 从配置文件获取模板ID
            $templates = Config::get('wechat.templates');

            if (empty($templates) || !isset($templates['reply'])) {
                return $this->error('未配置回复提醒模板');
            }

            return $this->success('获取成功', null, [
                'template_id' => $templates['reply']
            ]);
        } catch (Exception $e) {
            return $this->error('获取回复提醒模板失败：' . $e->getMessage());
        }
    }

    /**
     * 生成评论区小程序短链
     * @param Request $request
     * @return Json
     */

    public function generateCommentAreaLink(Request $request)
    {
        $wallOwnerId = $this->getUserId();

        try {
            // 使用微信服务生成小程序短链
            $wxService = new WechatService();
            $pageUrl = 'pages/comment/comment?wallOwnerId=' . $wallOwnerId;
            $title = '公共评论区';
            $shortLink = $wxService->generateWxShortLink($pageUrl, $title, true);

            if ($shortLink) {
                // 保存到墙主设置
                $wallSettingsModel = new WallSettings();
                $wallSettingsModel->saveWallSettings($wallOwnerId, [
                    'comment_area_short_link' => $shortLink
                ]);

                return $this->success('生成成功', null, [
                    'shortLink' => $shortLink
                ]);
            } else {
                return $this->error('生成失败');
            }
        } catch (Exception $e) {
            return $this->error('生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成小程序URL Link外链
     * @param Request $request
     * @return Json
     */
    public function generateUrlLink(Request $request)
    {
        $path = $request->param('path', '');
        $query = $request->param('query', '');
        $expireType = $request->param('expire_type/d', 0);
        $expireTime = $request->param('expire_time/d', 0);
        $expireInterval = $request->param('expire_interval/d', 30);
        $envVersion = $request->param('env_version', 'release');
        $wallOwnerId = $request->param('wallOwnerId', '');

        try {
            // 如果没有指定path，使用默认的墙主页面
            if (empty($path)) {
                if (empty($wallOwnerId)) {
                    $wallOwnerId = $this->getUserId();
                }
                $path = 'pages/index/index';
                $query = 'wallOwnerId=' . $wallOwnerId;
            }

            // 构建请求参数
            $params = [
                'path' => $path,
                'query' => $query,
                'expire_type' => $expireType,
                'env_version' => $envVersion
            ];

            // 根据失效类型设置失效时间
            if ($expireType == 0) {
                // 到期失效
                if ($expireTime > 0) {
                    $params['expire_time'] = $expireTime;
                } else {
                    // 默认30天后失效
                    $params['expire_time'] = time() + (30 * 24 * 3600);
                }
            } else {
                // 间隔天数失效
                $params['expire_interval'] = $expireInterval;
            }

            // 调用微信API生成URL Link
            $wxService = new WechatService();
            $result = $wxService->generateUrlLink($params);

            if ($result && isset($result['url_link'])) {
                return $this->success('生成成功', null, [
                    'url_link' => $result['url_link'],
                    'expire_time' => $params['expire_type'] == 0 ? $params['expire_time'] : null,
                    'expire_interval' => $params['expire_type'] == 1 ? $params['expire_interval'] : null,
                    'path' => $path,
                    'query' => $query
                ]);
            } else {
                $errorMsg = isset($result['errmsg']) ? $result['errmsg'] : '生成失败';
                return $this->error($errorMsg);
            }
        } catch (Exception $e) {
            return $this->error('生成失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取渠道列表
     * @return Json
     */
    public function getChannelList()
    {
        $channel_list = Channels::where('status', 1)->column('channels_id,channels_name,status', 'channels_id');
        $channels = WallChannels::where('wall_id', $this->wall_id)->column('channels_id,channel_account,channel_fans_numbers,channel_fans_costs', 'channels_id');

        foreach ($channel_list as &$item) {
            $item['channel_enabled'] = false;
            if (isset($channels[$item['channels_id']])) {
                $item['channel_enabled'] = true;
                $item = array_merge($item, $channels[$item['channels_id']]);
            }
        }
        return $this->success('获取成功', null, array_values($channel_list));
    }


    /**
     * 保存渠道
     * @return Json
     */
    public function saveChannelInfo(Request $request)
    {
//        $channels = array(
//            array(
//                'channels_id' => 1,
//                'channel_account' => "123456",
//                'channel_fans_numbers' => 200,
//                'channel_fans_costs' => 8.2,
//            ),
//            array(
//                'channels_id' => 2,
//                'channel_account' => "454353",
//                'channel_fans_numbers' => 300,
//                'channel_fans_costs' => 5.2,
//            ),
//        );

        $channels = $request->param('channels/a', []);
        if (!is_array($channels) || empty($channels)) {
            return $this->error('参数错误');
        }
        $channels_list = Channels::where('status', 1)->column('channels_id,channels_name,status', 'channels_id');
        foreach ($channels as &$items) {
            unset($items['channel_enabled']);
            $items['wall_id'] = $this->wall_id;
            $items['wall_owner_id'] = $this->getUserId();

            $rule['channels_id'] = 'require|integer';
//            $rule['channel_account'] = 'string';
//            $rule['channel_fans_numbers'] = 'integer';
            $rule['channel_fans_costs'] = 'float';

            $this->validate($items, $rule);

            if (!isset($channels_list[$items['channels_id']])) {
                return $this->error('渠道' . $items['channels_id'] . '不存在');
            }
            $items['channels_name'] = $channels_list[$items['channels_id']]['channels_name'];
        }

        Db::startTrans();
        try {
            WallChannels::where('wall_id', $this->wall_id)->delete();
            WallChannels::insertAll($channels);
            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throwError('保存失败: ' . $e->getMessage());
        }

        return $this->success('保存成功');
    }


    public function getPreview()
    {
        //添加数据展示“昨日投稿数、今日投稿数（及环比）”
        //添加展示“可提现金额”，点击后进入金额明细页面
        $yesterday_post_count = $this->getPostCount(date('Y-m-d', strtotime('-1 day')));
        $today_post_count = $this->getPostCount(date('Y-m-d'));

        // 计算环比，确保进行整数运算并处理除零错误
        if ($yesterday_post_count > 0) {
            $post_count_ring = ($today_post_count - $yesterday_post_count) / $yesterday_post_count; // 百分比变化
        } else {
            // 昨天没有帖子时的处理逻辑，例如设置为特定值或避免除法
            $post_count_ring = $today_post_count > 0 ? 100 : 0; // 如果昨天为0今天有帖子，视为增长100%
        }

        $data['yesterday_post_count'] = $yesterday_post_count;
        $data['today_post_count'] = $today_post_count;
        $data['post_count_ring'] = round($post_count_ring * 100, 2);
        $data['withdrawable_amount'] = $this->getWithdrawableAmount();

        return $this->success('获取成功', null, $data);
    }

    protected function getPostCount($date)
    {
        $count = PostModel::whereBetweenTime('create_time', $date, $date . ' 23:59:59')
            ->where('status', 1)
            ->where('wall_owner_id', $this->userId)
            ->count();

        return (int)$count;
    }


    //提现申请

    protected function getWithdrawableAmount()
    {
        //获取用户已经提现金额
        // 去除驳回的 0=待审核/2=待打款/3=审核拒绝/5=已完成/10=已取消
        $withdrawn_amount = WithdrawModel::where('user_id', $this->userId)
            ->whereIn('withdraw_status', [0, 2, 5])
            ->sum('cash_amount');

        //获取用户可提现金额
        $withdrawn_amount_amount = PostModel::where('wall_owner_id', $this->userId)
            ->where('status', 1)
            ->where('is_single', 1)
//            ->fetchSql()
            ->sum('commission');


//        dd($withdrawn_amount_amount);
        $withdrawable_amount = (float)$withdrawn_amount_amount - (float)$withdrawn_amount;
        if ($withdrawable_amount < 0) {
            $withdrawable_amount = 0;
        }

        return round($withdrawable_amount, 2);
    }


    //提现记录
    public function Application(Request $request)
    {
        $params = $request->post();
        $rule['withdrawal_method'] = 'require|in:offline,wechat';
        $rule['cash_amount'] = 'require|float|min:0.01';
        if ($request->param('withdrawal_method') == 'offline') {
            $rule['bank_card'] = 'require';
            $rule['bank_name'] = 'require';
            $rule['payee_name'] = 'require';
        }

        $this->validate($params, $rule);

        $cash_amount = $params['cash_amount'];
        if ($cash_amount <= 0) {
            return $this->error('提现金额不能小于等于0');
        }
        if ($cash_amount > $this->getWithdrawableAmount()) {
            return $this->error('提现金额不能大于可提现金额');
        }

        $data['user_id'] = $this->userId;
        $data['withdrawal_no'] = 'T' . date('ymdhis') . mt_rand(1000, 9999);
        $data['cash_amount'] = $cash_amount;
        $data['withdrawal_method'] = $params['withdrawal_method'];
        $data['bank_card'] = $params['bank_card'] ?? '';
        $data['bank_name'] = $params['bank_name'] ?? '';
        $data['payee_name'] = $params['payee_name'] ?? '';

        WithdrawModel::create($data);
        return $this->success('提现申请成功');
    }

    public function getHistoryList(Request $request)
    {
        $rule['page'] = 'integer|>=:1';
        $rule['limit'] = 'integer|>=:1';
        $rule['start_date'] = 'dateFormat:Y-m-d';
        $rule['end_date'] = 'dateFormat:Y-m-d';
        $rule['withdrawal_method'] = 'in:offline,wechat';

        $this->validate($request->param(), $rule);

        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $start_date = $request->param('start_date', '');
        $end_date = $request->param('end_date', '');
        $withdrawal_method = $request->param('withdrawal_method', '');

        $where[] = ['user_id', '=', $this->userId];
        // 日期范围筛选
        if (!empty($start_date)) {
            $where[] = ['create_time', '>=', $start_date . ' 00:00:00'];
        }
        if (!empty($end_date)) {
            $where[] = ['create_time', '<=', $end_date . ' 23:59:59'];
        }

        if (!empty($withdrawal_method)) {
            $where[] = ['withdrawal_method', '=', $withdrawal_method];
        }

        $offset = ($page - 1) * $limit;


        $count = WithdrawModel::where($where)->count();

        $list = WithdrawModel::where($where)
            ->field('withdrawal_id,cash_amount,cash_time,create_time,reject_remarks,withdraw_status,withdrawal_evidence,withdrawal_method,bank_card,bank_name,payee_name')
            ->order('create_time', 'desc')
            ->limit($offset, $limit)
//            ->fetchSql()
            ->select();
//        dd($list);
        return $this->success('获取成功', null, [
            'list' => $list,
            'total' => $count,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $count > $limit
        ]);
    }

    public function getAmountDetailList(Request $request)
    {
        $rule['page'] = 'integer|>=:1';
        $rule['limit'] = 'integer|>=:1';
        $rule['start_date'] = 'dateFormat:Y-m-d';
        $rule['end_date'] = 'dateFormat:Y-m-d';

        $this->validate($request->param(), $rule);

        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $keyword = $request->param('keyword', '');
        $start_date = $request->param('start_date', '');
        $end_date = $request->param('end_date', '');

        $where[] = ['status', '=', 1];
        $where[] = ['is_single', '=', 1];
        $where[] = ['wall_owner_id', '=', $this->userId];

        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['title|content', 'like', '%' . $keyword . '%'];
        }

        // 日期范围筛选
        if (!empty($start_date)) {
            $where[] = ['create_time', '>=', $start_date . ' 00:00:00'];
        }
        if (!empty($end_date)) {
            $where[] = ['create_time', '<=', $end_date . ' 23:59:59'];
        }

        $count = PostModel::where($where)->count();

        $offset = ($page - 1) * $limit;
        $list = PostModel::where($where)
            ->field('post_id,title,images,commission,create_time,channels_name')
            ->order('create_time', 'desc')
            ->limit($offset, $limit)
            ->select();
//        dd($list);

        return $this->success('获取成功', null, [
            'list' => $list,
            'total' => $count,
            'page' => $page,
            'limit' => $limit,
            'hasMore' => $count > $limit
        ]);
    }


    public function getWithdrawalChannels()
    {
        $info = ConfigModel::where('name', 'withdrawal_channels')->value('value');
        return $this->success('生成成功', null, [
            'info' => $info,
        ]);
    }
}
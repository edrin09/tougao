<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Config as ConfigModel;
use app\common\model\Post as PostModel;
use app\common\model\User as UserModel;
use app\common\model\UserPosterSettings;
use app\common\model\WallChannels;
use app\common\model\WallSettings;
use app\common\service\SensitiveService;
use app\common\service\WechatMessage;
use app\common\service\WechatOfficialService;
use app\common\service\WechatPayService;
use Exception;
use GatewayClient\Gateway;
use think\Db;
use think\facade\Config;
use think\facade\Log;
use think\Request;
use think\response\Json;

class Post extends Api
{
    protected $noNeedLogin = ['list', 'detail'];
    protected $noNeedAuth = ['list', 'detail'];

    protected $wechatMessage;
    /**
     * 存储检查过的路径（用于调试）
     */
    private $checkedPaths = [];

    public function __construct()
    {
        parent::__construct();
        $this->wechatMessage = new WechatMessage();
    }

    /**
     * 获取投稿列表
     * @param Request $request
     * @return Json
     */

    public function list(Request $request)
    {
        $page = $request->param('page/d', 1);
        $size = $request->param('size/d', 10);
        $status = $request->param('status');
        $userId = $request->param('userId');
        $wallOwnerId = $request->param('wallOwnerId');
        $tagId = $request->param('tagId/d');
        $keyword = $request->param('keyword');

        // 构建查询参数
        $params = [
            'page' => $page,
            'size' => $size,
            'status' => $status,
            'userId' => $userId,
            'wallOwnerId' => $wallOwnerId,
            'keyword' => $keyword,
        ];

        // 如果是根据标签查询
        if ($tagId) {
            $params['tagId'] = $tagId;
        }

        // 权限控制：只能查看已审核通过的投稿或自己的投稿
        $currentUserId = $this->getUserId();

        if (empty($userId)) {
            $params['status'] = 1;
            // 只能查看已通过的投稿
        } elseif ($userId === $currentUserId) {
            // 查看自己的投稿，可以看到所有状态
        } else {
            // 查看别人的投稿，只能看已通过的
            $params['status'] = 1;
        }

        $postModel = new PostModel();
        $list = $postModel->getPostList($params);

        return $this->success('查询成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'size' => $size,
            'list' => $list->items(),
        ]);
    }

    /**
     * 获取投稿详情
     * @param Request $request
     * @return Json
     */

    public function detail(Request $request)
    {
//        $postId = $request->param('postId');
//        if (empty($postId)) {
//            return $this->error('投稿ID不能为空');
//        }

        $out_trade_no = $request->param('out_trade_no');
        if (empty($out_trade_no)) {
            return $this->error('订单号不能为空');
        }
        $post = PostModel::where('out_trade_no', $out_trade_no)->find();
        if (!$post) {
            return $this->error('投稿不存在');
        }

        // 权限控制：只能查看已审核通过的投稿或自己的投稿
//        $currentUserId = $this->getUserId();
//
//        if ($post['status'] !== 1 && $post['user_id'] !== $currentUserId) {
//            return $this->error('无权查看该投稿', null, '', 3`, ['code' => 403]);
//        }

        return $this->success('查询成功', null, [
            'postDetail' => $post,
        ]);
    }

    /**
     * 审核投稿
     * @param Request $request
     * @return Json
     */

    public function review(Request $request)
    {
        $postIds = $request->param('postId');
        $status = $request->param('status/d');
        $comment = $request->param('comment');
//dd($postIds);
        $res = $this->saveAudit($postIds, $status, $comment);
        //25-09-22 edwin 去除评论的链接
        return $this->success('审核成功', null, [
            'success' => true,
            'post_id' => $postIds,
            'short_link' => '',
            'page_url' => '',
            'total' => 1,
            'successCount' => $res['count'],
            'failCount' => 1 - $res['count'],
        ]);
    }

    protected function saveAudit($postIds, $status, $comment)
    {
        if (empty($postIds)) {
            return $this->error('投稿ID不能为空');
        }

        if ($status !== 1 && $status !== -1) {
            return $this->error('无效的审核状态');
        }
        $wall_info = WallSettings::where('id', $this->userInfo['wall_id'])->find();
        if (!$wall_info) {
            return $this->error('墙墙不存在');
        }

        $total = PostModel::where('wall_owner_id', $wall_info->wall_owner_id)
            ->where('create_time', '>=', date('Y-m-d') . ' 00:00:00')
            ->where('create_time', '<', date('Y-m-d') . ' 23:59:59')
            ->count();
        $no = date('md') . $total;

        $postModel = new PostModel();

        // 批量发送审核结果通知
        $posts = $postModel
            ->whereIn('post_id', $postIds)
            ->field('post_id,title,user_id,status,post_image,no_contact_post_image,tag,is_single,out_trade_no,channels_name,channel_fans_costs')
            ->with(['user' => function ($query) {
                $query->field('user_id,openid,is_official_subscribe,official_openid');
            }])
            ->select();
//        dd($posts);

        $i = 0;
        $list = [];
        $no_contact_list = [];
        $text = "👉墙墙智能投稿合集" . $no . "~";

        $wechatPayService = new WechatPayService();

        Db::startTrans();
        try {
            foreach ($posts as $key => $post) {

//                dd($post);
                // 处理单图投稿的退款逻辑
                if ($status == -1
                    && $post->is_single == 1
                    && !empty($post->out_trade_no)
                    && $post->channel_fans_costs > 0) {

                    $costs = (float)PostModel::where('out_trade_no', $post->out_trade_no)->sum('channel_fans_costs');
                    $total_fee = (int)(round($costs, 2) * 100);
                    $refund_fee = (int)(round($post->channel_fans_costs, 2) * 100);
                    $refund_desc = '墙名称：' . $wall_info->wall_name . '的渠道名称：' . $post->channels_name . '投稿被拒绝，退款' . $post->channel_fans_costs . '元';

                    $wechatPayService->refund($post->out_trade_no, $post->post_id, $total_fee, $refund_fee, $refund_desc);
//                dd($rsd);
                }

                $postModel->where('post_id', $post->post_id)->update([
                    'status' => $status,
                    'review_time' => date('Y-m-d H:i:s'),
                    'review_comment' => $comment,
                ]);
                // 仅处理未审核的投稿才发送消息
                if ($post->status == 0) {
                    try {
                        if ($post->user->is_official_subscribe == 1 && $post->user->official_openid) {
                            $WechatOfficialService = new WechatOfficialService();
                            $WechatOfficialService->sendAuditResult($post);

                        } else {
                            $statusText = $status === 1 ? '请在墙墙朋友圈查看稿件，有问题请私信哦' : '稿件重复/违规/疑似广告请联系墙墙发布';
                            $data = [
                                // 改为标题
                                'thing2' => ['value' => $this->substrText($post['title'], 20)],
                                'phrase1' => ['value' => $status === 1 ? '审核通过' : '审核未通过'],
                                'thing5' => ['value' => $statusText],
                            ];

                            $this->wechatMessage->sendSubscribeMessage($post->user->openid, 'review_result', $data);
                        }
                    } catch (Exception $e) {
                        Log::error('发送审核通知失败，投稿ID：' . $post['post_id'] . '，错误：' . $e->getMessage());
                        // 通知失败不影响审核结果
                    }
                }

                // 过滤空图片
                if (empty($post['post_image'])) {
                    continue;
                }
                // 过滤空标签
                if (empty($post['tag'])) {
                    continue;
                }

                // 拼接标题，用数组3个就换行
                if ($i % 3 === 0) {
                    $text .= "\n";
                }

                $text .= "☀️" . $post['tag'];
                //组装数组
                $list[$key]['post_id'] = $post['post_id'];
                $list[$key]['post_image'] = $post['post_image'];

                $no_contact_list[$key]['post_id'] = $post['post_id'];
                $no_contact_list[$key]['no_contact_post_image'] = $post['no_contact_post_image'];

                $i++;
            }

            if ($status == 1) {
                $body['text'] = $text;
                $body['no'] = $no;
                $body['exclusive_link'] = '👉点击一键投稿到多个渠道' . $wall_info->miniapp_short_link;
                $body['wall_id'] = $wall_info->id;
                $body['list'] = array_values($list);
                $body['no_contact_list'] = array_values($no_contact_list);

                $msg['type'] = 'auto_moment';
                $msg['data'] = $body;
//        dd($msg);
                try {
                    Log::notice('[向app发送数据]:openid=' . $this->userInfo['openid'] . json_encode($msg));
                    Gateway::sendToUid($this->userInfo['openid'], json_encode($msg));
                } catch (Exception $e) {
                    Log::notice('[向app发送数据失败]:openid=' . $this->userInfo['openid'] . json_encode($e->getMessage()));
                }
            }

//            $count = $postModel->batchUpdateStatus($postIds, $status, $comment);

            Db::commit();
            return ['count' => $i];
        } catch (Exception $e) {
            Db::rollback();

            throwError($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 更新投稿
     * @param Request $request
     * @return Json
     */

    public function update(Request $request)
    {
        $postId = $request->param('id');
        $title = $request->param('title');
        $content = $request->param('content');
        $contact = $request->param('contact');
        $tag = $request->param('tag');
        $images = $request->param('images/a', []);
        $openid = $request->param('openid');
        // 接收openid参数

        // 参数验证
        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        if (empty($postId)) {
            return $this->error('投稿ID不能为空');
        }

        if (empty($title)) {
            return $this->error('标题不能为空');
        }

        if (mb_strlen($title) > 50) {
            return $this->error('标题长度不能超过50个字符');
        }

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        if (mb_strlen($content) > 500) {
            return $this->error('内容长度不能超过500个字符');
        }

        $postModel = new PostModel();

        // 直接查询投稿信息（post表中已包含openid）
        $post = $postModel->where('post_id', $postId)->find();

        if (!$post) {
            return $this->error('投稿不存在');
        }

        // 权限检查：只有投稿作者可以编辑（通过openid验证）
        if ($post['openid'] !== $openid) {
            return $this->error('无权编辑该投稿', null, '', 3, ['code' => 403]);
        }

        // 只有待审核状态的投稿可以编辑
        if ($post['status'] !== 0) {
            return $this->error('只有待审核状态的投稿可以编辑');
        }

        // 处理图片数组
        if (!empty($images) && count($images) > 9) {
            return $this->error('图片数量不能超过9张');
        }

        // 处理图片URL
        $imageUrls = [];
        foreach ($images as $image) {
            if (is_array($image) && isset($image['url'])) {
                $imageUrls[] = $image['url'];
            } elseif (is_string($image)) {
                $imageUrls[] = $image;
            }
        }

        // 更新数据
        $updateData = [
            'title' => $title,
            'content' => $content,
            'contact' => $contact ?: '',
            'tag' => $tag ?: '', // 单个标签
            'images' => $imageUrls,
            'image_urls' => implode(',', $imageUrls),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $result = $postModel->where('post_id', $postId)->update($updateData);

        if ($result === false) {
            return $this->error('更新失败');
        }

        return $this->success('更新成功', null, [
            'success' => true,
        ]);
    }

    /**
     * 截取文本，确保不超过微信模板消息长度限制
     * @param string $text 原文本
     * @param int $length 最大长度
     * @return string
     */
    protected function substrText($text, $length = 20)
    {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length - 2) . '..';
        }
        return $text;
    }

    /**
     * 批量审核投稿
     * @param Request $request
     * @return Json
     */

    public function batchReview(Request $request)
    {
        $postIds = $request->param('postIds/a');
        $status = $request->param('status');
        $comment = $request->param('comment');

        $res = $this->saveAudit($postIds, $status, $comment);
        //25-09-22 edwin 去除评论的链接
        return $this->success('批量审核成功', null, [
            'success' => true,
            'total' => count($postIds),
            'successCount' => $res['count'],
            'failCount' => count($postIds) - $res['count'],
            'publish_id' => '',
            'short_link' => '',
            'page_url' => ''
        ]);
    }

    /**
     * 转审核（将未通过的投稿重新转为待审核状态）
     * @param Request $request
     * @return Json
     */

    public function moveToReview(Request $request)
    {
        $postId = $request->param('postId');
        $comment = $request->param('comment', '重新审核');

        if (empty($postId)) {
            return $this->error('投稿ID不能为空');
        }

        $postModel = new PostModel();
        $post = $postModel->where('post_id', $postId)->find();

        if (!$post) {
            return $this->error('投稿不存在');
        }

        // 只有未通过的投稿才能转审核
        if ($post['status'] != -1) {
            return $this->error('只有未通过的投稿才能转审核');
        }

        // 将状态改为待审核
        $result = $postModel->updatePostStatus($postId, 0, $comment);

        if ($result === false) {
            return $this->error('转审核失败');
        }

        return $this->success('转审核成功', null, [
            'success' => true,
        ]);
    }

    // 批量转审核

    public function batchMoveToReview(Request $request)
    {
        // 支持 JSON、表单两种方式传参
        $postIds = $request->param('postIds/a'); // TP5 参数类型转换：/a 表示数组
        $comment = $request->param('comment', '重新审核');

        if (empty($postIds)) {
            return $this->error('投稿ID不能为空');
        }

        $postModel = new PostModel();
        $successCount = $postModel->batchMoveToReview($postIds, $comment);
        $failCount = count($postIds) - $successCount;

        return $this->success('批量转审核完成', null, [
            'success' => true,
            'total' => count($postIds),
            'successCount' => $successCount,
            'failCount' => $failCount,
        ]);
    }

    /**
     * 删除投稿
     * @param Request $request
     * @return Json
     */

    public function delete(Request $request)
    {
        $postId = $request->param('postId');
        $openid = $request->param('openid');
        // 接收openid参数

        if (empty($postId)) {
            return $this->error('投稿ID不能为空');
        }

        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        $postModel = new PostModel();

        // 直接查询投稿信息（post表中已包含openid）
        $post = $postModel->where('post_id', $postId)->find();

        if (!$post) {
            return $this->error('投稿不存在');
        }

        // 权限检查：只有投稿作者可以删除（通过openid验证）
        if ($post['openid'] !== $openid) {
            return $this->error('无权删除该投稿', null, '', 3, ['code' => 403]);
        }

        $result = $postModel->where('post_id', $postId)->delete();

        if ($result === false) {
            return $this->error('删除失败');
        }

        return $this->success('删除成功', null, [
            'success' => true,
        ]);
    }

    /**
     * 获取墙主待审核投稿列表
     * @param Request $request
     * @return Json
     */

    public function getPendingPosts(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 9);
        $wallOwnerId = $request->param('wallOwnerId');
        $startDate = $request->param('startDate');
        $endDate = $request->param('endDate');

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }
        // 构建查询参数
        $params = [
            'page' => $page,
            'size' => $size,
            'status' => 0, // 待审核
            'wallOwnerId' => $wallOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $postModel = new PostModel();
        $list = $postModel->getPostList($params);

        return $this->success('获取成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'size' => $size,
            'list' => $list->items(),
        ]);
    }

    /**
     * 获取墙主已审核通过的投稿列表
     * @param Request $request
     * @return Json
     */

    public function getApprovedPosts(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 9);
        $wallOwnerId = $request->param('wallOwnerId');
        $startDate = $request->param('startDate');
        $endDate = $request->param('endDate');

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        // 构建查询参数
        $params = [
            'page' => $page,
            'size' => $size,
            'status' => 1, // 已通过
            'wallOwnerId' => $wallOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $postModel = new PostModel();
        $list = $postModel->getPostList($params);

        return $this->success('获取成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'size' => $size,
            'list' => $list->items(),
        ]);
    }

    /**
     * 获取墙主审核未通过的投稿列表
     * @param Request $request
     * @return Json
     */

    public function getRejectedPosts(Request $request)
    {
        $page = $request->param('page', 1);
        $size = $request->param('size', 9);
        $wallOwnerId = $request->param('wallOwnerId');
        $startDate = $request->param('startDate');
        $endDate = $request->param('endDate');

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        // 构建查询参数
        $params = [
            'page' => $page,
            'size' => $size,
            'status' => -1, // 未通过
            'wallOwnerId' => $wallOwnerId,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ];

        $postModel = new PostModel();
        $list = $postModel->getPostList($params);

        return $this->success('获取成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'size' => $size,
            'list' => $list->items(),
        ]);
    }

    /**
     * 获取墙主所有投稿统计
     * @param Request $request
     * @return Json
     */

    public function getPostsStats(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');
        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        // 获取各状态的投稿数量
        $pendingCount = $this->getPostCount($wallOwnerId, 0);
        $approvedCount = $this->getPostCount($wallOwnerId, 1);
        $rejectedCount = $this->getPostCount($wallOwnerId, -1);

        return $this->success('获取成功', null, [
            'pending' => $pendingCount,                                   // 待审核数量
            'approved' => $approvedCount,                                  // 已通过数量
            'rejected' => $rejectedCount,                                  // 未通过数量
            'total' => $pendingCount + $approvedCount + $rejectedCount, // 总数量
        ]);
    }

    protected function getPostCount($wallOwnerId, $status)
    {
        $count = PostModel::where([
            'wall_owner_id' => $wallOwnerId,
            'status' => $status,
        ])->count();

        return (int)$count;
    }

    /**
     * 获取我的投稿列表
     * @param Request $request
     * @return Json
     */

    public function myPosts(Request $request)
    {
        $page = $request->param('page/d', 1);
        $pageSize = $request->param('pageSize/d', 10);
        $status = $request->param('status');
        // pending, approved, rejected
        $openid = $request->param('openid');
        // 接收openid参数

        // 参数验证
        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        // 构建查询条件 - 直接使用openid查询
        $where = ['p.openid' => $openid];

        // 状态筛选
        if (!empty($status)) {
            switch ($status) {
                case 'pending':
                    $where['p.status'] = 0;
                    break;
                case 'approved':
                    $where['p.status'] = 1;
                    break;
                case 'rejected':
                    $where['p.status'] = -1;
                    break;
            }
        }

        $postModel = new PostModel();

        // 获取投稿列表 - 直接通过post表的openid查询
        $list = $postModel->alias('p')
            ->leftJoin('cw_user u', 'p.user_id = u.user_id')
            ->leftJoin('cw_user wu', 'p.wall_owner_id = wu.user_id')
            ->where($where)
            ->field('p.*, u.nickname, u.avatar, wu.nickname as wall_owner_name')
            ->order('p.create_time desc')
            ->paginate($pageSize, false, ['page' => $page]);

        // 处理返回数据
        $items = [];
        foreach ($list->items() as $item) {
            // 处理图片数据
            $images = [];
            if (!empty($item['images'])) {
                if (is_string($item['images'])) {
                    $images = json_decode($item['images'], true) ?: [];
                } else {
                    $images = $item['images'];
                }
            }

            // 处理状态
            $statusMap = [
                0 => 'pending',
                1 => 'approved',
                -1 => 'rejected',
            ];

            $items[] = [
                'id' => $item['post_id'],
                'title' => $item['title'],
                'content' => $item['content'],
                'tag' => $item['tag'] ?? '', // 单个标签
                'contact' => $item['contact'] ?? '',
                'images' => $images,
                'status' => $statusMap[$item['status']] ?? 'pending',
                'created_at' => is_numeric($item['create_time'])
                    ? date('Y-m-d H:i:s', ( int )$item['create_time'])
                    : $item['create_time'],
                'wall_owner_name' => $item['wall_owner_name'],
                'post_image' => $item['post_image'] ?? '',
                'is_single' => $item['is_single'],
                'channels_name' => $item['channels_name'],
                'channel_account' => $item['channel_account'],
                'channel_fans_costs' => $item['channel_fans_costs'],
            ];
        }

        return $this->success('获取成功', null, [
            'total' => $list->total(),
            'page' => $page,
            'pageSize' => $pageSize,
            'list' => $items,
        ]);
    }

    /**
     * 使用Puppeteer生成海报
     * @param Request $request
     * @return Json
     */
    public function generatePoster(Request $request)
    {
        $title = $request->param('title');
        $content = $request->param('content');
        $contact = $request->param('contact');
        $tag = $request->param('tag');
        $bgColor = $request->param('backgroundColor', '#ffffff');
        $custom_bg_image = $request->param('custom_bg_image', '');
        $images = $request->param('images/a', []);
        // 墙主头像
        $wallOwnerAvatar = $request->param('owner_avatar', '');
        // 墙主回复内容
        $wallOwnerReply = $request->param('submission_reply', '');
        // 用户头像
        $userAvatar = $request->param('userAvatar', '');
        // 是否匿名
        $isAnonymous = $request->param('isAnonymous', false);

        // 参数验证
        if (empty($title)) {
            return $this->error('标题不能为空');
        }

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        // 调用getPosterUrl方法生成海报
        $result = $this->getPosterUrl($title, $content, $contact, $tag, $bgColor, $images, $custom_bg_image, $wallOwnerAvatar, $wallOwnerReply, $userAvatar, $isAnonymous);

        if ($result['code'] === 200) {
            return $this->success('海报生成成功', null, $result['data']);
        } else {
            return $this->error($result['msg']);
        }
    }

    public function getPosterUrl(
        $title,
        $content,
        $contact,
        $tag,
        $bg_color,
        $images,
        $custom_bg_image = '',
        $wall_owner_avatar = '',
        $wall_owner_reply = '',
        $user_avatar = '',
        $is_anonymous = false
    )
    {
        // 获取上传配置
        $uploadConfig = Config::get('upload.');

        // 使用配置文件中的海报保存路径
        $posterDir = $uploadConfig['poster_path'];
        if (!is_dir($posterDir)) {
            mkdir($posterDir, 0755, true);
        }

        // 生成唯一文件名
        $fileName = md5(uniqid(mt_rand(), true)) . '.jpg';
        $filePath = $posterDir . DIRECTORY_SEPARATOR . $fileName;
        $fileUrl = request()->domain() . '/poster/' . $fileName;

        // 处理图片数组为逗号分隔的字符串
        $images = is_array($images) ? implode(',', $images) : '';

        $poster_data['title'] = $this->replaceSsensitiveWords($title);
        $poster_data['content'] = $this->replaceSsensitiveWords($content);
        $poster_data['contact'] = $contact;
        $poster_data['tag'] = $tag;
        $poster_data['bg_color'] = $bg_color;
        $poster_data['custom_bg_image'] = $custom_bg_image;
        $poster_data['images'] = $images;
        $poster_data['wall_owner_avatar'] = $wall_owner_avatar;
        $poster_data['wall_owner_reply'] = $wall_owner_reply;
        $poster_data['user_avatar'] = $user_avatar;
        $poster_data['is_anonymous'] = $is_anonymous;

        $info = UserPosterSettings::find($this->getUserId());
        if ($info) {
            $info->save($poster_data);
        } else {
            $poster_data['user_id'] = $this->getUserId();
            UserPosterSettings::create($poster_data);
        }

        $posterUrl = request()->domain() . '/posters/user_id/' . $this->getUserId() . '.html';
        $img_url = request()->domain() . '/take?full=1&viewport-width=480&url=' . $posterUrl;

        Log::info('Poster URL: ' . $posterUrl);
        Log::info('Img URL: ' . $img_url);

        //将图片下载到本地
        $img_content = file_get_contents($img_url);
        file_put_contents($filePath, $img_content);

        return ['code' => 200, 'msg' => '海报生成成功', 'data' => ['url' => $fileUrl]];
    }

    public function replaceSsensitiveWords($content)
    {
        $sensitive_word_filtering = ConfigModel::where('name', 'sensitive_word_filtering')->value('value');
        if ($sensitive_word_filtering) {
            $SensitiveService = new SensitiveService();
//        $content = '你大爷是你的哥哥，滚蛋，傻逼，SB';
//        $res = $SensitiveService->doCheck($content);
            $res = $SensitiveService->doReplace($content);
        }

        return $res ?? $content;
//        dd($res, 1, 1);
    }

    /**
     * 创建投稿
     * @param Request $request
     * @return Json
     */

    public function create(Request $request)
    {
        $userId = $this->getUserId();
        $openid = $request->param('openid');
        // 接收openid参数
        $wallOwnerId = $request->param('wallOwnerId');
        $title = $request->param('title');
        $content = $request->param('content');
        $contact = $request->param('contact');
        // 联系方式
        $images = $request->param('images/a');
        // 接收数组
        $tag = $request->param('tag');
        // 单个标签
        $bgColor = $request->param('bgColor');
        $posterUrl = $request->param('posterUrl');

        $outTradeNo = $request->param('outTradeNo');
        // 接收海报URL

        // 参数验证
        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        if (empty($title)) {
            return $this->error('标题不能为空');
        }

        if (mb_strlen($title) > 50) {
            return $this->error('标题长度不能超过50个字符');
        }

        if (empty($content)) {
            return $this->error('内容不能为空');
        }

        if (mb_strlen($content) > 500) {
            return $this->error('内容长度不能超过500个字符');
        }
        // 处理图片数组
        if (!empty($images) && count($images) > 9) {
            return $this->error('图片数量不能超过9张');
        }

        if (empty($outTradeNo)) {
            return $this->error('订单编号不能为空');
        }

        // 检查墙主是否存在
        $wallSettings = new WallSettings();
        $wallOwner = $wallSettings->getWallSettings($wallOwnerId);
        if (!$wallOwner) {
            return $this->error('墙主不存在');
        }
        // 获取用户信息
        $userModel = new UserModel();
        //获取墙主用户信息
        $wallOwnerUser = $userModel->where('user_id', $wallOwnerId)->find();
        if (!$wallOwnerUser || empty($wallOwnerUser['openid'])) {
            return $this->error('墙主信息不完整');
        }

        $channels = $request->param('selectedChannels', []);
        if (!is_array($channels)) {
            return $this->error('投稿渠道格式错误');
        }

        $post_count = PostModel::where('out_trade_no', $outTradeNo)->count();
        if ($post_count) {
            return $this->error('该订单已投稿，不能重复投稿');
        }

        $data['post_id'] = 'post_' . uniqid();
        $data['user_id'] = $userId;
        $data['openid'] = $openid;
        $data['wall_owner_id'] = $wallOwnerId;
        $data['title'] = $this->replaceSsensitiveWords($title);
        $data['content'] = $this->replaceSsensitiveWords($content);
        $data['contact'] = $contact ?: '';
        $data['images'] = $images ?: [];
        $data['image_urls'] = !empty($images) ? implode(',', $images) : '';
        $data['tag'] = $tag ?: ''; // 单个标签
        $data['bg_color'] = $bgColor ?: '#FFFFFF';
        $data['status'] = 0; // 默认待审核
        $data['post_image'] = $posterUrl;
        $data['no_contact_post_image'] = $this->getNoContactPostImage();
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['channels_id'] = 0;
        $data['channels_name'] = '';
        $data['channel_account'] = '';
        $data['channel_fans_costs'] = '0.00';
        $data['is_single'] = 0;
        $data['commission'] = '0.00';

        $data_arr = array($data);

        if (!empty($channels)) {
            $wall_channels = WallChannels::where('wall_id', $wallOwner['id'])
                ->column('wall_id,channels_id,channels_name', 'channels_id');

//            dd($channels);
            foreach ($channels as $key => &$value) {
                if (!isset($wall_channels[$value['channels_id']])) {
                    return $this->error('投稿渠道：' . $value['channels_id'] . '，不存在');
                }
                $data['post_id'] = 'post_' . uniqid();
                $data['out_trade_no'] = $outTradeNo;
                $data['channels_id'] = $value['channels_id'];
                $data['channels_name'] = $wall_channels[$value['channels_id']]['channels_name'];
                $data['channel_account'] = $value['channel_account'];
                $data['channel_fans_costs'] = $value['channel_fans_costs'];
                $data['is_single'] = 1;
                $data['commission'] = round($wallOwner->share_ratio / 100 * $value['channel_fans_costs'], 2);

                $value = array_merge($value, $data);
            }
        }

        $arr = array_merge($data_arr, $channels);

//        dd($arr);
        // 创建投稿
        Db::startTrans();
        try {
            foreach ($arr as $v) {
                PostModel::create($v);
            }

            Db::commit();
        } catch (Exception $e) {
            Db::rollback();
            throwError('创建投稿失败：' . $e->getMessage());
        }

        // 发送新投稿提醒给墙主
        try {
            // 获取投稿人信息
            $user = $userModel->where('user_id', $userId)->find();
            $userName = $this->substrText($user ? ($user['nickname'] ?: '匿名用户') : '匿名用户', 20);
            $title = $this->substrText($title, 20);
            // 查询墙主待审核投稿数量
            $pendingPosts = PostModel::where('wall_owner_id', $wallOwnerId)->where('status', 0)->count();


            if ($wallOwnerUser->is_official_subscribe == 1 && $wallOwnerUser->official_openid) {

                $WechatOfficialService = new WechatOfficialService();
                $WechatOfficialService->sendPendingReviewResult($title, $userName, $pendingPosts, $wallOwnerUser['official_openid']);

            } else {
                $data = [
                    'thing1' => ['value' => $userName], // 投稿标题
                    'thing4' => ['value' => $title],    // 投稿人
                    'time6' => ['value' => date('Y-m-d H:i:s')],              // 投稿时间
                    'thing8' => ['value' => '还有' . $pendingPosts . '条待审核'],
                ];

                // 使用template_pages配置自动匹配跳转页面
                $this->wechatMessage->sendSubscribeMessage($wallOwnerUser['openid'], 'new_post', $data);
            }

        } catch (Exception $e) {
            Log::error('发送新投稿提醒失败：' . $e->getMessage());
            // 通知失败不影响投稿结果
        }

        return $this->success('投稿成功，等待审核');
    }

    protected function getNoContactPostImage()
    {
        $info = UserPosterSettings::find($this->getUserId());
        if ($info) {
            // 获取上传配置
            $uploadConfig = Config::get('upload.');
            // 使用配置文件中的海报保存路径
            $noContactPosterDir = $uploadConfig['no_contact_poster_path'];
            if (!is_dir($noContactPosterDir)) {
                mkdir($noContactPosterDir, 0755, true);
            }

            // 生成唯一文件名
            $fileName = md5(uniqid(mt_rand(), true)) . '.jpg';
            $filePath = $noContactPosterDir . DIRECTORY_SEPARATOR . $fileName;
            $fileUrl = request()->domain() . '/no_contact_poster/' . $fileName;

            $posterUrl = request()->domain() . '/posters/user_id/' . $this->getUserId() . '/is_contact/0.html';
            $img_url = request()->domain() . '/take?full=1&viewport-width=480&url=' . $posterUrl;

            Log::info('No Contact Poster URL: ' . $posterUrl);
            Log::info('No Contact Img URL: ' . $img_url);

            //将图片下载到本地
            $img_content = file_get_contents($img_url);
            file_put_contents($filePath, $img_content);

            return $fileUrl;
        }

        return '';
    }
}

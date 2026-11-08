<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\model\Comment as CommentModel;
use app\common\model\Config as ConfigModel;
use app\common\model\Post as PostModel;
use app\common\model\WallSettings;
use app\common\service\SensitiveService;
use app\common\service\WechatService;
use Exception;
use think\Db;
use think\Request;
use think\response\Json;

class Comment extends Api
{
    // 无需登录的方法
    protected $noNeedLogin = [
        'getCommentList',
        'getWallInfo',
    ];

    // 需要openid验证但不需要完整登录的方法
    protected $needOpenidOnly = [
        'addComment',
        'deleteComment'
    ];

    /**
     * 获取评论列表
     * @param Request $request
     * @return Json
     */
    public function getCommentList(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');
        $postId = $request->param('postId'); // 新增：发布ID参数
        $page = intval($request->param('page', 1));
        $limit = intval($request->param('limit', 20));

        // 优先使用postId，如果没有则使用wallOwnerId（向后兼容）
        if (empty($postId) && empty($wallOwnerId)) {
            return $this->error('发布ID或墙主ID不能为空');
        }

        // 参数验证
        if ($page < 1) $page = 1;
        if ($limit < 1 || $limit > 100) $limit = 20;

        try {
            // 直接使用数据库查询，避免模型复杂性
            $offset = ($page - 1) * $limit;

            // 构建查询条件
            $query = Db::table('cw_comments');
            if (!empty($postId)) {
                // 基于发布ID查询（批量发布共享评论区）
                $query->where('post_id', $postId);
            } else {
                // 基于墙主ID查询（向后兼容）
                $query->where('wall_owner_id', $wallOwnerId)
                    ->where(function ($query) {
                        $query->whereNull('post_id')->whereOr('post_id', '');
                    });
            }

            $comments = $query->where('parent_id', 0)
                ->where('status', 1)  // 只显示审核通过的评论
                ->order('create_time', 'asc')
                ->limit($offset, $limit)
                ->select();

            // 统计总数
            $totalQuery = Db::table('cw_comments');
            if (!empty($postId)) {
                $totalQuery->where('post_id', $postId);
            } else {
                $totalQuery->where('wall_owner_id', $wallOwnerId)
                    ->where(function ($query) {
                        $query->whereNull('post_id')->whereOr('post_id', '');
                    });
            }

            $total = $totalQuery->where('parent_id', 0)
                ->where('status', 1)  // 只显示审核通过的评论
                ->count();

            // 转换为数组并处理数据
            $commentList = [];
            foreach ($comments as $comment) {
                $commentArray = (array)$comment;

                // 格式化时间（兼容字符串或时间戳）
                $timestamp = is_numeric($commentArray['create_time']) ? intval($commentArray['create_time']) : strtotime($commentArray['create_time']);
                $commentArray['create_time_formatted'] = $timestamp ? date('Y/m/d H:i', $timestamp) : '';

                // 处理images字段
                $commentArray['images'] = [];
                if (!empty($commentArray['images'])) {
                    $images = json_decode($commentArray['images'], true);
                    $commentArray['images'] = is_array($images) ? $images : [];
                }

                // 获取回复，只显示审核通过的回复
                $replies = Db::table('cw_comments')
                    ->where('parent_id', $commentArray['id'])
                    ->where('status', 1)  // 只显示审核通过的回复
                    ->order('create_time', 'asc')
                    ->select();

                $replyList = [];
                foreach ($replies as $reply) {
                    $replyArray = (array)$reply;
                    $rTimestamp = is_numeric($replyArray['create_time']) ? intval($replyArray['create_time']) : strtotime($replyArray['create_time']);
                    $replyArray['create_time_formatted'] = $rTimestamp ? date('Y/m/d H:i', $rTimestamp) : '';
                    $replyArray['images'] = [];
                    if (!empty($replyArray['images'])) {
                        $replyImages = json_decode($replyArray['images'], true);
                        $replyArray['images'] = is_array($replyImages) ? $replyImages : [];
                    }
                    $replyList[] = $replyArray;
                }

                $commentArray['replies'] = $replyList;
                $commentList[] = $commentArray;
            }

            return $this->success('获取成功', null, [
                'list' => $commentList,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'hasMore' => count($commentList) >= $limit
            ]);
        } catch (Exception $e) {
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 添加评论
     * @param Request $request
     * @return Json
     */
    public function addComment(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');
        $postId = $request->param('postId'); // 新增：发布ID参数
        $content = $request->param('content');
        $images = $request->param('images', []);
        $nickname = $request->param('nickname');
        $avatarUrl = $request->param('avatarUrl');
        $openid = $request->param('openid');
        $parentId = $request->param('parentId', 0);
        $replyToOpenid = $request->param('replyToOpenid');
        $replyToNickname = $request->param('replyToNickname');

        // 优先使用postId，如果没有则使用wallOwnerId（向后兼容）
        if (empty($postId) && empty($wallOwnerId)) {
            return $this->error('发布ID或墙主ID不能为空');
        }

        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        if (empty($content)) {
            return $this->error('评论内容不能为空');
        }

        if (mb_strlen($content) > 500) {
            return $this->error('评论内容不能超过500字');
        }

        try {
            $commentModel = new CommentModel();
            $data = [
                'wall_owner_id' => $wallOwnerId ?: '', // 兼容旧版本
                'post_id' => $postId ?: '', // 新增发布ID字段
                'openid' => $openid,
                'nickname' => $nickname ?: '匿名用户',
                'avatar_url' => $avatarUrl ?: '',
                'content' => $this->replaceSsensitiveWords($content),
                'images' => $images,
                'parent_id' => $parentId,
                'reply_to_openid' => $replyToOpenid,
                'reply_to_nickname' => $replyToNickname,
                'status' => 1  // 默认为待审核状态
            ];

            $result = $commentModel->addComment($data);
            if ($result) {
                return $this->success('评论成功');
            } else {
                return $this->error('评论失败');
            }
        } catch (Exception $e) {
            return $this->error('评论失败: ' . $e->getMessage());
        }
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
     * 删除评论
     * @param Request $request
     * @return Json
     */
    public function deleteComment(Request $request)
    {
        $commentId = $request->param('commentId');
        $wallOwnerId = $request->param('wallOwnerId');
        $openid = $request->param('openid');

        if (empty($commentId)) {
            return $this->error('评论ID不能为空');
        }

        if (empty($openid)) {
            return $this->error('用户openid不能为空');
        }

        try {
            $commentModel = new CommentModel();

            // 检查是否为墙主
            $wallSettingsModel = new WallSettings();
            $wallSettings = $wallSettingsModel->where('wall_owner_id', $wallOwnerId)->find();
            $isWallOwner = $wallSettings ? true : false;

            if ($isWallOwner) {
                // 墙主可以删除任意评论
                $result = $commentModel->deleteCommentByOwner($commentId, $wallOwnerId);
            } else {
                // 用户只能删除自己的评论
                $result = $commentModel->deleteCommentByUser($commentId, $openid);
            }

            if ($result) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败或无权限');
            }
        } catch (Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取墙主信息
     * @param Request $request
     * @return Json
     */
    public function getWallInfo(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');

        if (empty($wallOwnerId)) {
            return $this->error('墙主ID不能为空');
        }

        try {
            $wallSettingsModel = new WallSettings();
            $wallSettings = $wallSettingsModel->getWallSettings($wallOwnerId);

            if (!$wallSettings) {
                return $this->error('墙主不存在');
            }

            $wallInfo = [
                'wall_name' => $wallSettings['wall_name'] ?: '校园墙',
                'description' => $wallSettings['description'] ?: '',
                'owner_avatar' => $wallSettings['owner_avatar'] ?: '',
                'theme_color' => $wallSettings['theme_color'] ?: '#2979ff'
            ];

            return $this->success('获取成功', null, $wallInfo);
        } catch (Exception $e) {
            return $this->error('获取失败: ' . $e->getMessage());
        }
    }

    /**
     * 生成匿名评论区短链
     * @param Request $request
     * @return Json
     */
    public function generateLink(Request $request)
    {
        $wallOwnerId = $request->param('wallOwnerId');
        $publishId = $request->param('publishId');

        if (empty($wallOwnerId) || empty($publishId)) {
            return $this->error('参数不完整');
        }

        try {
            $wechatService = new WechatService();

            // 构建小程序页面路径
            $pageUrl = "pages/comment/index?wallOwnerId={$wallOwnerId}&publishId={$publishId}";
            $title = '匿名评论区';

            // 生成微信小程序短链
            $shortLink = $wechatService->generateWxShortLink($pageUrl, $title, true);

            if ($shortLink) {
                return $this->success('生成短链成功', null, [
                    'shortLink' => $shortLink,
                    'pageUrl' => $pageUrl
                ]);
            } else {
                return $this->error('生成短链失败');
            }

        } catch (Exception $e) {
            return $this->error('生成短链失败: ' . $e->getMessage());
        }
    }

    /**
     * 根据发布ID获取投稿列表
     * @param Request $request
     * @return Json
     */
    public function getPostsByPublishId(Request $request)
    {
        $publishId = $request->param('publishId');
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);

        if (empty($publishId)) {
            return $this->error('发布ID不能为空');
        }

        try {
            $postModel = new PostModel();

            // 获取该发布ID下的所有投稿
            $posts = $postModel->where('publisher_id', $publishId)
                ->where('status', 2) // 只获取已发布的投稿
                ->order('publish_time', 'desc')
                ->page($page, $limit)
                ->select();

            // 获取总数
            $total = $postModel->where('publisher_id', $publishId)
                ->where('status', 2)
                ->count();

            return $this->success('获取成功', null, [
                'posts' => $posts,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);

        } catch (Exception $e) {
            return $this->error('获取投稿列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取发布统计信息
     * @param Request $request
     * @return Json
     */
    public function getPublishStats(Request $request)
    {
        $publishId = $request->param('publishId');

        if (empty($publishId)) {
            return $this->error('发布ID不能为空');
        }

        try {
            $postModel = new PostModel();
            $commentModel = new CommentModel();

            // 获取投稿数量
            $postCount = $postModel->where('publisher_id', $publishId)
                ->where('status', 2)
                ->count();

            // 获取评论数量（通过post_id关联）
            $commentCount = $commentModel->where('post_id', $publishId)
                ->where('status', 1)
                ->count();

            // 获取最早发布时间
            $earliestPost = $postModel->where('publisher_id', $publishId)
                ->where('status', 2)
                ->order('publish_time', 'asc')
                ->find();

            $publishTime = $earliestPost ? $earliestPost->publish_time : null;

            return $this->success('获取统计信息成功', null, [
                'publishId' => $publishId,
                'postCount' => $postCount,
                'commentCount' => $commentCount,
                'publishTime' => $publishTime
            ]);

        } catch (Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage());
        }
    }
}

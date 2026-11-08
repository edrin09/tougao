<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Comment as CommentModel;
use app\common\service\SensitiveService;
use app\common\service\WechatMessage;
use Exception;
use think\facade\Log;
use think\Request;
use think\response\Json;

/**
 * 评论管理控制器
 */
class Comment extends AdminBase
{
    /**
     */
    protected $wechatMessage;

    public function index()
    {
        $SensitiveService = new SensitiveService();
        $content = '你大爷';

        $res = $SensitiveService->doReplace($content);
        dd($res, 1, 1);

        $res = $SensitiveService->doCheck($content);

        dd($res, 1, 1);
    }

    /**
     * 获取评论列表
     * @param Request $request
     * @return Json
     */

    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $status = $request->param('status');
        $keyword = $request->param('keyword');

        try {
            $commentModel = new CommentModel();

            // 构建查询条件
            $where = [];

            // 状态筛选（审核状态）
            if ($status !== null && $status !== '') {
                $where[] = ['status', '=', $status];
            }

            // 关键词搜索
            if (!empty($keyword)) {
                $where[] = ['wall_owner_id|content|nickname', 'like', '%' . $keyword . '%'];
            }

            // 获取评论列表
            $comments = $commentModel->where($where)
                ->order('create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);

            // 处理数据
            $list = [];
            foreach ($comments->items() as $comment) {
                $commentData = $comment->toArray();

                // 处理图片字段
                if (!empty($commentData['images'])) {
                    $images = json_decode($commentData['images'], true);
                    $commentData['images'] = is_array($images) ? $images : [];
                } else {
                    $commentData['images'] = [];
                }

                // 若为回复评论，查询父评论内容
                if (isset($commentData['parent_id']) && $commentData['parent_id'] > 0) {
                    // 尽量使用缓存减少查询，可按需优化
                    $parentContent = $commentModel->where('id', $commentData['parent_id'])->value('content');
                    $commentData['parent_content'] = $parentContent ?: '';
                } else {
                    $commentData['parent_content'] = '';
                }

                $list[] = $commentData;
            }

            // 分页信息
            $pagination = [
                'current_page' => $comments->getCurrentPage(),
                'total_pages' => $comments->lastPage(),
                'total' => $comments->total(),
                'per_page' => $limit,
                'has_prev' => $comments->getCurrentPage() > 1,
                'has_next' => $comments->getCurrentPage() < $comments->lastPage()
            ];

            return $this->success('获取成功', null, [
                'list' => $list,
                'pagination' => $pagination,
                'total' => $comments->total()
            ]);

        } catch (Exception $e) {
            return $this->error('获取评论列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 审核评论
     * @param Request $request
     * @param int $id
     * @return Json
     */

    public function review(Request $request, $id)
    {
        $action = $request->param('action');
        // approve 或 reject
        $comment = $request->param('comment', '');

        if (empty($action)) {
            return $this->error('审核操作不能为空');
        }

        if (!in_array($action, ['approve', 'reject'])) {
            return $this->error('无效的审核操作');
        }

        try {
            $commentModel = new CommentModel();
            $commentData = $commentModel->where('id', $id)->find();

            if (!$commentData) {
                return $this->error('评论不存在');
            }

            // 设置审核状态
            $status = $action === 'approve' ? 1 : -1;

            $result = $commentData->save([
                'status' => $status,
                'update_time' => date('Y-m-d H:i:s')
            ]);
            $actionText = $action === 'approve' ? '通过' : '驳回';
            // 发送审核结果通知
            try {
                $statusText = $status === 1 ? '您的评论已通过审核' : '您的评论未通过审核';
                $data = [
                    // 改为标题
                    'thing2' => ['value' => '评论审核结果'],
                    'phrase1' => ['value' => $status === 1 ? '审核通过' : '审核未通过'],
                    'thing5' => ['value' => $statusText],
                ];

                // 使用template_pages配置自动匹配跳转页面
                $this->wechatMessage->sendSubscribeMessage($commentData['openid'], 'review_result', $data);
            } catch (Exception $e) {
                Log::error('发送审核通知失败：' . $e->getMessage());
                // 通知失败不影响审核结果
            }

            if ($result) {
                $actionText = $action === 'approve' ? '通过' : '驳回';
                return $this->success("评论审核{$actionText}成功");
            } else {
                return $this->error('审核失败');
            }

        } catch (Exception $e) {
            return $this->error('审核评论失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量审核评论
     * @param Request $request
     * @return Json
     */

    public function batchReview(Request $request)
    {
        $commentIds = $request->param('comment_ids');
        $action = $request->param('action');

        if (empty($commentIds) || !is_array($commentIds)) {
            return $this->error('请选择要审核的评论');
        }

        if (empty($action)) {
            return $this->error('审核操作不能为空');
        }

        if (!in_array($action, ['approve', 'reject'])) {
            return $this->error('无效的审核操作');
        }

        try {
            $commentModel = new CommentModel();

            // 设置审核状态
            $status = $action === 'approve' ? 1 : -1;

            $result = $commentModel->whereIn('id', $commentIds)->update([
                'status' => $status,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            // 发送通知
            $comments = $commentModel->whereIn('id', $commentIds)->select();
            foreach ($comments as $comment) {
                try {

                    // 如果是回复的也要改回复人发送通知
                    if ($comment['parent_id'] > 0) {
                        $replyToOpenid = $comment['reply_to_openid'];
                        if ($replyToOpenid) {
                            $data = [
                                'thing3' => ['value' => $comment['nickname']],
                                'thing8' => ['value' => $comment['content']],
                            ];

                            // 传递wallOwnerId和publishId参数用于动态拼接页面路径
                            $pageParams = ['wallOwnerId' => $comment['wall_owner_id'], 'publishId' => $comment['post_id']];
                            $this->wechatMessage->sendSubscribeMessage($replyToOpenid, 'reply', $data, null, $pageParams);
                        }
                    }

                    $statusText = $status === 1 ? '您的评论已通过审核' : '您的评论未通过审核';
                    $data = [
                        // 改为标题
                        'thing2' => ['value' => '评论审核结果'],
                        'phrase1' => ['value' => $status === 1 ? '审核通过' : '审核未通过'],
                        'thing5' => ['value' => $statusText],
                    ];

                    // 使用template_pages配置自动匹配跳转页面
                    $this->wechatMessage->sendSubscribeMessage($comment['openid'], 'review_result', $data);
                } catch (Exception $e) {
                    Log::error('发送审核通知失败，评论ID：' . $comment['id'] . '，错误：' . $e->getMessage());
                    // 通知失败不影响审核结果
                }
            }

            if ($result) {
                $actionText = $action === 'approve' ? '通过' : '驳回';
                return $this->success("批量审核{$actionText}成功");
            } else {
                return $this->error('批量审核失败');
            }

        } catch (Exception $e) {
            return $this->error('批量审核失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取评论详情
     * @param Request $request
     * @param int $id
     * @return Json
     */

    public function getDetail(Request $request, $id)
    {
        try {
            $commentModel = new CommentModel();
            $comment = $commentModel->find($id);

            if (!$comment) {
                return $this->error('评论不存在');
            }

            $commentData = $comment->toArray();

            // 处理图片字段
            if (!empty($commentData['images'])) {
                $images = json_decode($commentData['images'], true);
                $commentData['images'] = is_array($images) ? $images : [];
            } else {
                $commentData['images'] = [];
            }

            // 获取回复列表
            $replies = $commentModel->where('parent_id', $id)
                ->where('status', 1)
                ->order('create_time', 'asc')
                ->select();

            $replyList = [];
            foreach ($replies as $reply) {
                $replyData = $reply->toArray();

                // 处理回复的图片字段
                if (!empty($replyData['images'])) {
                    $replyImages = json_decode($replyData['images'], true);
                    $replyData['images'] = is_array($replyImages) ? $replyImages : [];
                } else {
                    $replyData['images'] = [];
                }

                $replyList[] = $replyData;
            }

            $commentData['replies'] = $replyList;

            return $this->success('获取成功', null, $commentData);

        } catch (Exception $e) {
            return $this->error('获取评论详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 恢复评论
     * @param Request $request
     * @param int $id
     * @return Json
     */

    public function restore(Request $request, $id)
    {
        try {
            $commentModel = new CommentModel();
            $comment = $commentModel->find($id);

            if (!$comment) {
                return $this->error('评论不存在');
            }

            // 恢复：更新状态为0（待审核）
            $result = $comment->save(['status' => 0, 'update_time' => date('Y-m-d H:i:s')]);

            if ($result) {
                // 同时恢复该评论的所有回复为待审核状态
                $commentModel->where('parent_id', $id)->update([
                    'status' => 0,
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                return $this->success('恢复成功');
            } else {
                return $this->error('恢复失败');
            }

        } catch (Exception $e) {
            return $this->error('恢复评论失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量删除评论
     * @param Request $request
     * @return Json
     */

    public function batchDelete(Request $request)
    {
        $ids = $request->param('ids');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要删除的评论');
        }

        try {
            $commentModel = new CommentModel();

            // 批量软删除
            $result = $commentModel->whereIn('id', $ids)->update([
                'status' => -2,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                // 同时删除这些评论的所有回复
                $commentModel->whereIn('parent_id', $ids)->update([
                    'status' => -2,
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                return $this->success('批量删除成功');
            } else {
                return $this->error('批量删除失败');
            }

        } catch (Exception $e) {
            return $this->error('批量删除失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量恢复评论
     * @param Request $request
     * @return Json
     */

    public function batchRestore(Request $request)
    {
        $ids = $request->param('ids');

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择要恢复的评论');
        }

        try {
            $commentModel = new CommentModel();

            // 批量恢复为待审核状态
            $result = $commentModel->whereIn('id', $ids)->update([
                'status' => 0,
                'update_time' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                // 同时恢复这些评论的所有回复为待审核状态
                $commentModel->whereIn('parent_id', $ids)->update([
                    'status' => 0,
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                return $this->success('批量恢复成功');
            } else {
                return $this->error('批量恢复失败');
            }

        } catch (Exception $e) {
            return $this->error('批量恢复失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取评论统计信息
     * @param Request $request
     * @return Json
     */

    public function getStats(Request $request)
    {
        try {
            $commentModel = new CommentModel();

            $stats = [
                'total' => $commentModel->count(),
                'pending' => $commentModel->where('status', 0)->count(),  // 待审核
                'approved' => $commentModel->where('status', 1)->count(), // 审核通过
                'rejected' => $commentModel->where('status', -1)->count(), // 审核驳回
                'deleted' => $commentModel->where('status', -2)->count(),  // 已删除
                'today' => $commentModel->whereTime('create_time', 'today')->count(),
                'this_week' => $commentModel->whereTime('create_time', 'week')->count(),
                'this_month' => $commentModel->whereTime('create_time', 'month')->count()
            ];

            return $this->success('获取成功', null, $stats);

        } catch (Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage());
        }
    }

    /**
     * 初始化测试数据
     * @param Request $request
     * @return Json
     */

    public function initTestData(Request $request)
    {
        try {
            $commentModel = new CommentModel();

            // 清空现有测试数据
            $commentModel->where('wall_owner_id', 'test_wall_owner')->delete();

            // 创建测试数据
            $testData = [
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'openid' => 'test_openid_1',
                    'nickname' => '测试用户1',
                    'avatar_url' => 'https://via.placeholder.com/50',
                    'content' => '这是第一条测试评论（已审核通过）',
                    'images' => json_encode([]),
                    'parent_id' => 0,
                    'status' => 1,  // 审核通过
                    'create_time' => time() - 3600,
                    'update_time' => time() - 3600
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'openid' => 'test_openid_2',
                    'nickname' => '测试用户2',
                    'avatar_url' => 'https://via.placeholder.com/50',
                    'content' => '这是第二条测试评论（待审核）',
                    'images' => json_encode(['https://via.placeholder.com/300x200']),
                    'parent_id' => 0,
                    'status' => 0,  // 待审核
                    'create_time' => time() - 1800,
                    'update_time' => time() - 1800
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'openid' => 'test_openid_3',
                    'nickname' => '测试用户3',
                    'avatar_url' => '',
                    'content' => '这是一条被驳回的测试评论',
                    'images' => json_encode([]),
                    'parent_id' => 0,
                    'status' => -1,  // 审核驳回
                    'create_time' => time() - 1200,
                    'update_time' => time() - 1200
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'openid' => 'test_openid_4',
                    'nickname' => '测试用户4',
                    'avatar_url' => '',
                    'content' => '这是一条已删除的测试评论',
                    'images' => json_encode([]),
                    'parent_id' => 0,
                    'status' => -2,  // 已删除
                    'create_time' => time() - 900,
                    'update_time' => time() - 900
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'openid' => 'test_openid_5',
                    'nickname' => '测试用户5',
                    'avatar_url' => 'https://via.placeholder.com/50',
                    'content' => '这是对第一条评论的回复（已审核通过）',
                    'images' => json_encode([]),
                    'parent_id' => 1,
                    'reply_to_openid' => 'test_openid_1',
                    'reply_to_nickname' => '测试用户1',
                    'status' => 1,  // 审核通过
                    'create_time' => time() - 600,
                    'update_time' => time() - 600
                ]
            ];

            foreach ($testData as $data) {
                $commentModel->create($data);
            }

            return $this->success('测试数据初始化成功', null, [
                'message' => '已创建5条测试评论数据：1条审核通过，1条待审核，1条驳回，1条删除，1条回复',
                'count' => count($testData)
            ]);

        } catch (Exception $e) {
            return $this->error('初始化测试数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除评论
     * @param Request $request
     * @param int $id
     * @return Json
     */

    public function delete(Request $request, $id)
    {
        try {
            $commentModel = new CommentModel();
            $comment = $commentModel->find($id);

            if (!$comment) {
                return $this->error('评论不存在');
            }

            // 软删除：更新状态为-2
            $result = $comment->save(['status' => -2, 'update_time' => date('Y-m-d H:i:s')]);

            if ($result) {
                // 同时删除该评论的所有回复
                $commentModel->where('parent_id', $id)->update([
                    'status' => -2,
                    'update_time' => date('Y-m-d H:i:s')
                ]);

                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }

        } catch (Exception $e) {
            return $this->error('删除评论失败: ' . $e->getMessage());
        }
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->wechatMessage = new WechatMessage();
    }

}

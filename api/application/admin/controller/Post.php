<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Config;
use app\common\model\Post as PostModel;
use Exception;
use think\Request;
use think\response\Json;

/**
 * 投稿管理控制器
 */
class Post extends AdminBase
{
    /**
     * 获取投稿列表
     * @param Request $request
     * @return Json
     */
    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $status = $request->param('status');
        $keyword = $request->param('keyword');
        $wallOwnerId = $request->param('wall_owner_id');

        try {
            $postModel = new PostModel();

            // 构建查询条件
            $where = [];

            // 状态筛选
            if ($status !== null && $status !== '') {
                $where[] = ['p.status', '=', $status];
            }

            // 墙主ID筛选
            if (!empty($wallOwnerId)) {
                $where[] = ['p.wall_owner_id', 'like', '%' . $wallOwnerId . '%'];
            }

            // 关键词搜索
            if (!empty($keyword)) {
                $where[] = ['p.title|p.content', 'like', '%' . $keyword . '%'];
            }

            // 获取投稿列表，联查墙主设置表获取墙主名字
            $posts = $postModel->alias('p')
                ->leftJoin('cw_wall_settings ws', 'p.wall_owner_id = ws.wall_owner_id')
                ->field('p.*, ws.wall_name')
                ->where($where)
                ->order('p.create_time', 'desc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);

            // 处理数据
            $list = [];
            foreach ($posts->items() as $post) {
                $postData = $post->toArray();

                // 处理图片字段
                $postData['images'] = $this->processImages($postData['images'] ?? null);

                $list[] = $postData;
            }

            // 分页信息
            $pagination = [
                'current_page' => $posts->getCurrentPage(),
                'total_pages' => $posts->lastPage(),
                'total' => $posts->total(),
                'per_page' => $limit,
                'has_prev' => $posts->getCurrentPage() > 1,
                'has_next' => $posts->getCurrentPage() < $posts->lastPage()
            ];

            return $this->success('获取成功', null, [
                'list' => $list,
                'pagination' => $pagination,
                'total' => $posts->total()
            ]);

        } catch (Exception $e) {
            return $this->error('获取投稿列表失败: ' . $e->getMessage());
        }
    }

    /**
     * 处理图片字段
     * @param mixed $images
     * @return array
     */
    private function processImages($images)
    {
        if (empty($images)) {
            return [];
        }

        if (is_string($images)) {
            // 如果是字符串，尝试解析JSON
            $decoded = json_decode($images, true);
            return is_array($decoded) ? $decoded : [];
        } elseif (is_array($images)) {
            // 如果已经是数组，直接使用
            return $images;
        }

        return [];
    }

    /**
     * 获取投稿详情
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function getDetail(Request $request, $id)
    {
        try {
            $postModel = new PostModel();
            $post = $postModel->where('post_id', $id)->find();

            if (!$post) {
                return $this->error('投稿不存在');
            }

            $postData = $post->toArray();

            // 处理图片字段
            $postData['images'] = $this->processImages($postData['images'] ?? null);

            return $this->success('获取成功', null, $postData);

        } catch (Exception $e) {
            return $this->error('获取投稿详情失败: ' . $e->getMessage());
        }
    }

    /**
     * 审核投稿
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function review(Request $request, $id)
    {
        $action = $request->param('action'); // approve 或 reject
        $comment = $request->param('comment');

        if (empty($action) || empty($comment)) {
            return $this->error('参数不完整');
        }

        try {
            $postModel = new PostModel();
            $post = $postModel->where('post_id', $id)->find();

            if (!$post) {
                return $this->error('投稿不存在');
            }

            // 设置审核状态
            $status = $action === 'approve' ? 1 : -1;

            // 使用模型的updatePostStatus方法，确保时间格式正确
            $result = $postModel->updatePostStatus($id, $status, $comment);

            if ($result) {
                $actionText = $action === 'approve' ? '通过' : '驳回';
                return $this->success("审核{$actionText}成功");
            } else {
                return $this->error('审核失败');
            }

        } catch (Exception $e) {
            return $this->error('审核投稿失败: ' . $e->getMessage());
        }
    }

    /**
     * 发布投稿
     * @param Request $request
     * @param int $id
     * @return Json
     */
    public function publish(Request $request, $id)
    {
        try {
            $postModel = new PostModel();
            $post = $postModel->where('post_id', $id)->find();

            if (!$post) {
                return $this->error('投稿不存在');
            }

            if ($post->status != 1) {
                return $this->error('只有已通过审核的投稿才能发布');
            }

            $result = $post->save([
                'status' => 2,
                'publish_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                return $this->success('发布成功');
            } else {
                return $this->error('发布失败');
            }

        } catch (Exception $e) {
            return $this->error('发布投稿失败: ' . $e->getMessage());
        }
    }

    /**
     * 批量审核投稿
     * @param Request $request
     * @return Json
     */
    public function batchReview(Request $request)
    {
        $postIds = $request->param('post_ids');
        $action = $request->param('action');
        $comment = $request->param('comment');

        if (empty($postIds) || !is_array($postIds) || empty($action) || empty($comment)) {
            return $this->error('参数不完整');
        }

        try {
            $postModel = new PostModel();

            // 设置审核状态
            $status = $action === 'approve' ? 1 : -1;

            // 使用模型的batchUpdateStatus方法，确保时间格式正确
            $result = $postModel->batchUpdateStatus($postIds, $status, $comment);

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
     * 批量发布投稿
     * @param Request $request
     * @return Json
     */
    public function batchPublish(Request $request)
    {
        $postIds = $request->param('post_ids');

        if (empty($postIds) || !is_array($postIds)) {
            return $this->error('请选择要发布的投稿');
        }

        try {
            $postModel = new PostModel();

            // 只发布状态为1（已通过）的投稿
            $result = $postModel->whereIn('post_id', $postIds)
                ->where('status', 1)
                ->update([
                    'status' => 2,
                    'publish_time' => date('Y-m-d H:i:s'),
                    'update_time' => date('Y-m-d H:i:s')
                ]);

            if ($result) {
                return $this->success('批量发布成功');
            } else {
                return $this->error('批量发布失败，请确保选中的投稿都已通过审核');
            }

        } catch (Exception $e) {
            return $this->error('批量发布失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取投稿统计信息
     * @param Request $request
     * @return Json
     */
    public function getStats(Request $request)
    {
        try {
            $postModel = new PostModel();

            $stats = [
                'total' => $postModel->count(),
                'pending' => $postModel->where('status', 0)->count(),
                'approved' => $postModel->where('status', 1)->count(),
                'rejected' => $postModel->where('status', -1)->count(),
                'published' => $postModel->where('status', 2)->count(),
                'today' => $postModel->whereTime('create_time', 'today')->count(),
                'this_week' => $postModel->whereTime('create_time', 'week')->count(),
                'this_month' => $postModel->whereTime('create_time', 'month')->count()
            ];

            return $this->success('获取成功', null, $stats);

        } catch (Exception $e) {
            return $this->error('获取统计信息失败: ' . $e->getMessage());
        }
    }

    /**
     * 是否开启敏感词过滤
     * @param Request $request
     * @return Json
     */
    public function sensitiveWordFiltering(Request $request)
    {
        $audit = $request->param('audit', 0);
        $configModel = new Config();

        $config_info = $configModel->getConfigInfo('sensitive_word_filtering');
        $config_info->value = $audit;
        $config_info->save();

        return $this->success('操作成功');
    }

    /**
     * 初始化测试数据
     * @param Request $request
     * @return Json
     */
    public function initTestData(Request $request)
    {
        try {
            $postModel = new PostModel();

            // 清空现有测试数据
            $postModel->where('wall_owner_id', 'test_wall_owner')->delete(true);

            // 创建测试数据
            $testData = [
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'user_id' => 'test_user_1',
                    'openid' => 'test_openid_1',
                    'title' => '测试投稿标题1',
                    'content' => '这是第一条测试投稿内容，用于测试投稿管理功能。包含一些详细的描述信息。',
                    'tag' => '表白',
                    'contact' => 'test@example.com',
                    'images' => json_encode(['https://via.placeholder.com/300x200', 'https://via.placeholder.com/400x300']),
                    'status' => 0, // 待审核
                    'create_time' => time() - 3600,
                    'update_time' => time() - 3600
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'user_id' => 'test_user_2',
                    'openid' => 'test_openid_2',
                    'title' => '测试投稿标题2',
                    'content' => '这是第二条测试投稿内容，状态为已通过审核。可以用来测试发布功能。',
                    'tag' => '吐槽',
                    'contact' => '',
                    'images' => json_encode([]),
                    'status' => 1, // 已通过
                    'review_comment' => '内容符合要求，审核通过。',
                    'review_time' => time() - 1800,
                    'create_time' => time() - 1800,
                    'update_time' => time() - 1800
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'user_id' => 'test_user_3',
                    'openid' => 'test_openid_3',
                    'title' => '测试投稿标题3',
                    'content' => '这是第三条测试投稿内容，状态为已驳回。用于测试驳回状态的显示。',
                    'tag' => '求助',
                    'contact' => 'WeChat: test123',
                    'images' => json_encode(['https://via.placeholder.com/500x400']),
                    'status' => -1, // 已驳回
                    'review_comment' => '内容不符合社区规范，请修改后重新提交。',
                    'review_time' => time() - 900,
                    'create_time' => time() - 900,
                    'update_time' => time() - 900
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'user_id' => 'test_user_4',
                    'openid' => 'test_openid_4',
                    'title' => '测试投稿标题4',
                    'content' => '这是第四条测试投稿内容，状态为已通过。可以用来测试已通过状态的显示。',
                    'tag' => '分享',
                    'contact' => 'QQ: 123456789',
                    'images' => json_encode(['https://via.placeholder.com/600x400', 'https://via.placeholder.com/300x300']),
                    'status' => 1, // 已通过
                    'review_comment' => '优质内容，审核通过。',
                    'review_time' => time() - 600,
                    'create_time' => time() - 600,
                    'update_time' => time() - 600
                ],
                [
                    'wall_owner_id' => 'test_wall_owner',
                    'user_id' => 'test_user_5',
                    'openid' => 'test_openid_5',
                    'title' => '测试投稿标题5',
                    'content' => '这是第五条测试投稿内容，也是待审核状态，用于测试批量操作功能。',
                    'tag' => '闲聊',
                    'contact' => '',
                    'images' => json_encode([]),
                    'status' => 0, // 待审核
                    'create_time' => time() - 300,
                    'update_time' => time() - 300
                ]
            ];

            foreach ($testData as $data) {
                $postModel->create($data);
            }

            return $this->success('测试数据初始化成功', null, [
                'message' => '已创建5条测试投稿数据：2条待审核，2条已通过，1条已驳回',
                'count' => count($testData)
            ]);

        } catch (Exception $e) {
            return $this->error('初始化测试数据失败: ' . $e->getMessage());
        }
    }

    /**
     * 删除投稿
     * @param Request $request
     * @param int $id 投稿ID
     * @return Json
     */
    public function delete(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('投稿ID不能为空');
        }

        try {
            $postModel = new PostModel();

            // 查找投稿
            $post = $postModel->where('post_id', $id)->find();
            if (!$post) {
                return $this->error('投稿不存在');
            }

            // 删除投稿
            $result = $postModel->where('post_id', $id)->delete();

            if ($result) {
                return $this->success('删除成功');
            } else {
                return $this->error('删除失败');
            }

        } catch (Exception $e) {
            return $this->error('删除失败: ' . $e->getMessage());
        }
    }
}

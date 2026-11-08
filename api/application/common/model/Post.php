<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\Paginator;

class Post extends Model
{
    // 设置数据表名
    protected $table = 'cw_post';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 设置JSON类型字段
    protected $json = ['images'];


    // 字段列表
    protected $fields = [
        'post_id', 'user_id', 'openid', 'wall_owner_id', 'title', 'content', 'contact',
        'images', 'image_urls', 'tag', 'bg_color', 'status',
        'view_count', 'like_count', 'comment_count', 'create_time',
        'update_time', 'review_admin_id', 'review_time', 'review_comment',
        'publisher_id', 'publish_time', 'post_image', 'is_single', 'channels_name', 'channel_account'
    ];

    /**
     * 关联用户（投稿人）
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'user_id');
    }

    /**
     * 关联墙
     * @return BelongsTo
     */
    public function wall()
    {
        // 使用 wall_owner_id 字段关联到 Wall 模型（实际上是 cw_wall_settings 表）
        return $this->belongsTo('Wall', 'wall_owner_id', 'wall_owner_id');
    }

    /**
     * 关联审核管理员
     * @return BelongsTo
     */
    public function reviewAdmin()
    {
        return $this->belongsTo('Admin', 'review_admin_id');
    }

    /**
     * 关联发布管理员
     * @return BelongsTo
     */
    public function publisher()
    {
        return $this->belongsTo('Admin', 'publisher_id');
    }

    /**
     * 关联评论
     * @return HasMany
     */
    public function comments()
    {
        return $this->hasMany('Comment', 'post_id');
    }


    /**
     * 获取投稿列表
     * @param array $params 查询参数
     * @return Paginator
     */
    public function getPostList($params)
    {
        $page = isset($params['page']) ? intval($params['page']) : 1;
        $size = isset($params['size']) ? intval($params['size']) : 9;

        $where = [];

        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $where[] = ['status', '=', $params['status']];
        }

        // 用户筛选
        if (!empty($params['userId'])) {
            $where[] = ['user_id', '=', $params['userId']];
        }

        // 墙主筛选
        if (!empty($params['wallOwnerId'])) {
            $where[] = ['wall_owner_id', '=', $params['wallOwnerId']];
        }

        // 关键词搜索
        if (!empty($params['keyword'])) {
            $keyword = '%' . $params['keyword'] . '%';
            $where[] = ['title|content', 'like', $keyword];
        }

        // 日期范围筛选
        if (!empty($params['startDate'])) {
            $where[] = ['create_time', '>=', $params['startDate'] . ' 00:00:00'];
        }
        if (!empty($params['endDate'])) {
            $where[] = ['create_time', '<=', $params['endDate'] . ' 23:59:59'];
        }

//        dd($where);

        // 查询列表
        $list = $this->where($where)
            ->order('create_time', 'desc')
            ->paginate($size, false, [
                'page' => $page
            ]);

        // 关联用户信息
        $userIds = [];
        foreach ($list as $post) {
            $userIds[] = $post['user_id'];
        }

        if (!empty($userIds)) {
            $userModel = new User();
            $userList = $userModel
                ->where('user_id', 'in', $userIds)
                ->column('nickname,avatar,wechat_id', 'user_id');

            foreach ($list as &$post) {
                if (isset($userList[$post['user_id']])) {
                    $post['nickname'] = $userList[$post['user_id']]['nickname'];
                    $post['avatar'] = $userList[$post['user_id']]['avatar'];
                    $post['wechat_id'] = $userList[$post['user_id']]['wechat_id'];
                } else {
                    $post['nickname'] = '未知用户';
                    $post['avatar'] = '';
                    $post['wechat_id'] = '';
                }
            }
        }

        return $list;
    }

    /**
     * 获取投稿详情
     * @param string $postId
     * @return array|null
     */
    public function getPostDetail($postId)
    {
        $post = $this->where('post_id', $postId)->find();
        if (!$post) {
            return null;
        }

        // 关联用户信息
        $userModel = new User();
        $user = $userModel->where('user_id', $post['user_id'])->find();
        if ($user) {
            $post['nickname'] = $user['nickname'];
            $post['avatar'] = $user['avatar'];
        } else {
            $post['nickname'] = '未知用户';
            $post['avatar'] = '';
        }

        // 标签是前端硬编码的，直接返回标签名称即可
        if (!empty($post['tag'])) {
            $post['tag_info'] = [
                'name' => $post['tag']
            ];
        }

        return $post;
    }

    /**
     * 更新投稿状态
     * @param string $postId
     * @param int $status
     * @param string $comment
     * @return bool
     */
    public function updatePostStatus($postId, $status, $comment = '')
    {
        $data = [
            'status' => $status,
            'review_time' => date('Y-m-d H:i:s'), // 使用datetime格式
            'review_comment' => $comment
        ];
        return $this->where('post_id', $postId)->update($data);
    }

    /**
     * 批量更新投稿状态
     * @param array $postIds
     * @param int $status
     * @param string $comment
     * @return int
     */
    public function batchUpdateStatus($postIds, $status, $comment = '')
    {
        $data = [
            'status' => $status,
            'review_time' => date('Y-m-d H:i:s'), // 使用datetime格式
            'review_comment' => $comment
        ];
        return $this->whereIn('post_id', $postIds)->update($data);
    }

    /**
     * 批量将投稿从"未通过"转为"待审核"
     * 仅 status = -1 的记录会被更新，返回成功更新的行数
     * @param array $postIds 投稿ID数组
     * @param string $comment 备注
     * @return int 影响行数
     */
    public function batchMoveToReview(array $postIds, $comment = '重新审核')
    {
        if (empty($postIds)) {
            return 0;
        }

        $data = [
            'status' => 0, // 待审核
            'review_time' => date('Y-m-d H:i:s'),
            'review_comment' => $comment,
        ];

        // 仅更新当前状态为 -1 的投稿
        return $this->whereIn('post_id', $postIds)
            ->where('status', -1)
            ->update($data);
    }
}
<?php
namespace app\common\model;
use app\common\model\BaseModel as Model;

class Comment extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'cw_comments';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 字段列表
    protected $fields = [
        'id', 'wall_owner_id', 'post_id', 'openid', 'nickname', 'avatar_url',
        'content', 'images', 'parent_id', 'reply_to_openid', 'reply_to_nickname',
        'status', 'create_time', 'update_time'
    ];

    // JSON类型字段
    protected $json = ['images'];

    // JSON数据返回数组
    protected $jsonAssoc = true;

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'openid', 'openid');
    }

    /**
     * 关联墙主
     * @return \think\model\relation\BelongsTo
     */
    public function wallOwner()
    {
        return $this->belongsTo('User', 'wall_owner_id', 'user_id');
    }

    /**
     * 关联父评论
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('Comment', 'parent_id', 'id');
    }

    /**
     * 关联子评论
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany('Comment', 'parent_id', 'id')->where('status', 1);
    }

    /**
     * 获取评论列表（包含回复）
     * @param string $wallOwnerId 墙主ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getCommentList($wallOwnerId, $page = 1, $limit = 20)
    {
        // 确保参数类型正确
        $page = intval($page);
        $limit = intval($limit);
        $offset = ($page - 1) * $limit;

        try {
            // 获取主评论
            $mainComments = $this->where('wall_owner_id', $wallOwnerId)
                ->where('parent_id', 0)
                ->where('status', 1)
                ->order('create_time', 'asc')
                ->limit($offset, $limit)
                ->select();

            if (!$mainComments) {
                return [];
            }

            $mainComments = $mainComments->toArray();

            // 为每个主评论获取回复
            foreach ($mainComments as &$comment) {
                $replies = $this->where('parent_id', $comment['id'])
                    ->where('status', 1)
                    ->order('create_time', 'asc')
                    ->select();

                $comment['replies'] = $replies ? $replies->toArray() : [];
            }

            return $mainComments;
        } catch (\Exception $e) {
            \think\facade\Log::error('获取评论列表失败: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 添加评论
     * @param array $data 评论数据
     * @return bool|Comment
     */
    public function addComment($data)
    {
        try {
            return $this->save($data);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 删除评论（墙主权限）
     * @param int $commentId 评论ID
     * @param string $wallOwnerId 墙主ID
     * @return bool
     */
    public function deleteCommentByOwner($commentId, $wallOwnerId)
    {
        try {
            return $this->where('id', $commentId)
                ->where('wall_owner_id', $wallOwnerId)
                ->delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 删除评论（用户删除自己的）
     * @param int $commentId 评论ID
     * @param string $openid 用户openid
     * @return bool
     */
    public function deleteCommentByUser($commentId, $openid)
    {
        try {
            return $this->where('id', $commentId)
                ->where('openid', $openid)
                ->delete();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取主评论总数
     * @param string $wallOwnerId 墙主ID
     * @return int
     */
    public function getCommentCount($wallOwnerId)
    {
        return $this->where('wall_owner_id', $wallOwnerId)
            ->where('parent_id', 0)
            ->where('status', 1)
            ->count();
    }
}

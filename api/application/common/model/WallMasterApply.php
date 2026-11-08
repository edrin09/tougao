<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;
use Exception;
use think\model\relation\BelongsTo;

class WallMasterApply extends Model
{
    // 设置数据表名
    protected $table = 'cw_wall_master_apply';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳 - 从 'datetime' 修改为 'timestamp'
    protected $autoWriteTimestamp = 'timestamp';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    /**
     * 关联用户
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'user_id');
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
     * 创建申请
     * @param array $data
     * @return bool
     */
    public function createApply($data)
    {
        // 添加申请时间
        $data['apply_time'] = date('Y-m-d H:i:s');

        // 创建新实例并保存
        return $this->allowField(true)->save($data);
    }

    /**
     * 审核申请
     * @param int $id
     * @param int $status
     * @param string $comment
     * @param int $adminId
     * @return WallMasterApply|bool
     */
    public function reviewApply($id, $status, $comment = '', $adminId = 0)
    {
        return $this->where('id', $id)->update([
            'status' => $status,
            'review_time' => date('Y-m-d H:i:s'),
            'review_comment' => $comment,
            'review_admin_id' => $adminId
        ]);
    }

    /**
     * 获取默认墙主申请
     * @return WallMasterApply
     */
    public function getDefaultWallMasterApply()
    {
        return $this->where('cw_wall_master_apply.is_default_wall_master', 1)
            ->where('cw_wall_master_apply.status', 1)
            ->withJoin('user')
            ->find();
    }

    /**
     * 设置默认墙主申请
     * @param int $applyId
     * @return bool
     */
//    public function setDefaultWallMasterApply($applyId)
//    {
//        // 开启事务
//        $this->startTrans();
//        try {
//            // 先清除所有默认墙主标记
//            $this->where('is_default_wall_master', 1)->update(['is_default_wall_master' => 0]);
//
//            // 设置新的默认墙主（只能是已通过审核的申请）
//            $result = $this->where('id', $applyId)
//                ->where('status', 1)
//                ->update(['is_default_wall_master' => 1]);
//
//            if ($result) {
//                $this->commit();
//                return true;
//            } else {
//                $this->rollback();
//                return false;
//            }
//        } catch (Exception $e) {
//            $this->rollback();
//            return false;
//        }
//    }

    /**
     * 检查是否为默认墙主申请
     * @return bool
     */
    public function isDefaultWallMasterApply()
    {
        return $this->is_default_wall_master == 1;
    }

    public function getCreateTimeAttr($value)
    {
        return $this->formatTime($value);
    }

    /**
     * 获取器 - 格式化时间
     */
    protected function formatTime($value)
    {
        if (empty($value)) {
            return '';
        }
        // 如果是数字时间戳
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        // 如果是日期时间字符串
        return date('Y-m-d H:i:s', strtotime($value));
    }

    public function getUpdateTimeAttr($value)
    {
        return $this->formatTime($value);
    }

    public function getApplyTimeAttr($value)
    {
        return $this->formatTime($value);
    }

    public function getReviewTimeAttr($value)
    {
        return $this->formatTime($value);
    }
}
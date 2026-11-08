<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

class Withdraw extends Model
{
    // 设置数据表名
    protected $table = 'cw_withdrawal';

    // 设置主键
    protected $pk = 'withdrawal_id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 字段列表
    protected $fields = [
        'user_id', 'openid', 'nickname', 'avatar', 'create_time',
        'update_time', 'gender', 'phone', 'is_wall_master', 'is_vip',
        'is_default_wall_master', 'status', 'last_login_time'
    ];

    /**
     * 关联用户
     * @return \think\model\relation\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('User', 'user_id', 'user_id');
    }
}
<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

class WallToUser extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'cw_wall_to_user';

    // 设置主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
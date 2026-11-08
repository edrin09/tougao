<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

class UserPosterSettings extends Model
{
    // 设置数据表名
    protected $table = 'cw_user_poster_settings';

    // 设置主键
    protected $pk = 'user_id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
}
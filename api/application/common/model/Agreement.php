<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

/**
 * 使用教程（单条记录，主键固定为 1）
 */
class Agreement extends Model
{
    // 表名
    protected $table = 'cw_agreement';

    // 主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 关闭批量写保护，让所有字段都可以写入
    protected $field = true;
} 
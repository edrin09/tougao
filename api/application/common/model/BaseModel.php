<?php
namespace app\common\model;

use think\Model;

/**
 * 统一模型基类：
 * 1. 全局开启 DATETIME 自动写入
 * 2. 兼容旧代码中仍然传入的 10 位 Unix 时间戳
 */
class BaseModel extends Model
{
    // 让 ThinkPHP 用 datetime 字符串写入
    protected $autoWriteTimestamp = 'datetime';

    // 字段名统一
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    // 输出格式
    protected $dateFormat = 'Y-m-d H:i:s';

    // -------- setter：把传入的 10 位时间戳转换为 datetime --------
    protected function setCreateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        return $value ?: date('Y-m-d H:i:s');
    }

    protected function setUpdateTimeAttr($value)
    {
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }
        return $value ?: date('Y-m-d H:i:s');
    }
} 
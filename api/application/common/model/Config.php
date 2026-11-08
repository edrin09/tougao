<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

class Config extends Model
{
    // 表名
    protected $table = 'cw_config';

    // 主键
    protected $pk = 'name';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 关闭批量写保护，让所有字段都可以写入
    protected $field = true;

    public function getAll()
    {
        $config = $this->column('value,remark', 'name');
        return $config;
    }

    /**
     * 获取配置项
     */
    public function getConfigInfo($name)
    {
        $config_info = $this->where('name', $name)->find();
        return $config_info;
    }
}
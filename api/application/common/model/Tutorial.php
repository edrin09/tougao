<?php
namespace app\common\model;

use app\common\model\BaseModel as Model;

/**
 * 使用教程（单条记录，主键固定为 1）
 */
class Tutorial extends Model
{
    // 表名
    protected $table = 'cw_tutorial';

    // 主键
    protected $pk = 'id';

    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 关闭批量写保护，让所有字段都可以写入
    protected $field = true;

    /**
     * 获取教程 HTML 内容
     * @return string
     */
    public function getContent(): string
    {
        $info = $this->find(1);
        return $info ? (string)$info['content'] : '';
    }

    /**
     * 保存教程内容（存在即更新，不存在则插入）
     * @param string $html 富文本 HTML
     * @return bool
     */
    public function saveContent(string $html): bool
    {
        // 这里可以做 XSS 过滤 —— 略
        $exists = $this->where('id', 1)->find();
        $data = [
            'id'          => 1,
            'content'     => $html,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if ($exists) {
            return false !== $this->save($data, ['id' => 1]);
        }
        return false !== $this->insert($data);
    }
} 
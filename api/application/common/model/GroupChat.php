<?php
namespace app\common\model;

use app\common\model\BaseModel as Model;

class GroupChat extends Model
{
    // 设置当前模型对应的完整数据表名称
    protected $table = 'cw_group_chat';
    
    // 设置主键
    protected $pk = 'id';
    
    // 设置字段信息
    protected $schema = [
        'id'                => 'int',
        'wall_owner_id'     => 'string',
        'group_name'        => 'string',
        'group_description' => 'string',
        'group_avatar'      => 'string',
        'group_qrcode'      => 'string',
        'weight'            => 'int',
        'status'            => 'int',
        'create_time'       => 'datetime',
        'update_time'       => 'datetime',
    ];
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    
    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 定义时间戳字段名
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 追加属性
    protected $append = [
        'create_time_formatted',
        'update_time_formatted'
    ];

    /**
     * 格式化创建时间
     * @param $value
     * @param $data
     * @return string
     */
    public function getCreateTimeFormattedAttr($value, $data)
    {
        // 兼容旧数据时间戳与新版本 datetime 字符串
        return is_numeric($data['create_time'])
            ? date('Y-m-d H:i:s', (int)$data['create_time'])
            : $data['create_time'];
    }

    /**
     * 格式化更新时间
     * @param $value
     * @param $data
     * @return string
     */
    public function getUpdateTimeFormattedAttr($value, $data)
    {
        // 兼容旧数据时间戳与新版本 datetime 字符串
        return is_numeric($data['update_time'])
            ? date('Y-m-d H:i:s', (int)$data['update_time'])
            : $data['update_time'];
    }

    /**
     * 根据墙主ID获取群聊列表
     * @param string $wallOwnerId
     * @return array
     */
    public function getGroupsByWallOwner($wallOwnerId)
    {
        return $this->where('wall_owner_id', $wallOwnerId)
                   ->where('status', 1)
                   ->order('weight desc, create_time desc')
                   ->select();
    }

    /**
     * 创建群聊
     * @param array $data
     * @return int|false
     */
    public function createGroup($data)
    {
        $data['status'] = 1;
        
        $result = $this->insert($data);
        return $result ? $this->getLastInsID() : false;
    }

    /**
     * 更新群聊信息
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function updateGroup($id, $data)
    {
        return $this->where('id', $id)->update($data) !== false;
    }

    /**
     * 删除群聊（软删除）
     * @param int $id
     * @return bool
     */
    public function deleteGroup($id)
    {
        return $this->where('id', $id)->update([
            'status' => 0
        ]) !== false;
    }

    /**
     * 检查群聊名称是否存在
     * @param string $groupName
     * @param string $wallOwnerId
     * @param int $excludeId
     * @return bool
     */
    public function checkGroupNameExists($groupName, $wallOwnerId, $excludeId = 0)
    {
        $query = $this->where('group_name', $groupName)
                     ->where('wall_owner_id', $wallOwnerId)
                     ->where('status', 1);
        
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        
        return $query->find() ? true : false;
    }

    /**
     * 获取群聊详情
     * @param int $id
     * @return array|null
     */
    public function getGroupDetail($id)
    {
        return $this->where('id', $id)
                   ->where('status', 1)
                   ->find();
    }

    /**
     * 批量更新权重
     * @param array $weightData [['id' => 1, 'weight' => 10], ...]
     * @return bool
     */
    public function batchUpdateWeight($weightData)
    {
        $updateTime = time();
        foreach ($weightData as $item) {
            $this->where('id', $item['id'])->update([
                'weight' => $item['weight'],
                'update_time' => $updateTime
            ]);
        }
        return true;
    }
}

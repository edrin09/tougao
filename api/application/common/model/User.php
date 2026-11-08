<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;
use think\model\relation\HasMany;
use think\model\relation\HasOne;

class User extends Model
{
    // 设置数据表名
    protected $table = 'cw_user';

    // 设置主键
    protected $pk = 'user_id';

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
     * 关联投稿
     * @return HasMany
     */
    public function posts()
    {
        return $this->hasMany('Post', 'user_id', 'user_id');
    }

    /**
     * 关联墙主设置
     * @return HasOne
     */
    public function wallSettings()
    {
        return $this->hasOne('WallSettings', 'wall_owner_id', 'user_id');
    }

    /**
     * 关联绑定的墙
     * @return HasOne
     */
    public function wall()
    {
        return $this->hasOne('WallSettings', 'id', 'wall_id');
    }

    /**
     * 检查是否为墙主
     * @return bool
     */
    public function isWallMaster()
    {
        return $this->is_wall_master == 1;
    }

    /**
     * 检查是否为默认墙主
     * @return bool
     */
    public function isDefaultWallMaster()
    {
        return $this->is_default_wall_master == 1;
    }

    /**
     * 根据openid查询用户
     * @param string $openid
     * @return array|null
     */
    public function getUserByOpenid($openid)
    {
        return $this->where('openid', $openid)->find();
    }

    /**
     * 创建用户
     * @param array $data
     * @return string|bool
     */
    public function createUser($data)
    {
        // 生成用户ID
        $data['user_id'] = 'user_' . uniqid();

        if ($this->save($data)) {
            return $data['user_id'];
        }

        return false;
    }

    /**
     * 获取默认墙主
     * @return array|null
     */
    public function getDefaultWallMaster()
    {
        return $this->where('is_default_wall_master', 1)
            ->where('status', 1)
            ->find();
    }

    /**
     * 设置默认墙主
     * @param string $userId
     * @return bool
     */
//    public function setDefaultWallMaster($userId)
//    {
//        // 开启事务
//        $this->startTrans();
//        try {
//            // 先清除所有默认墙主标记
//            $this->where('is_default_wall_master', 1)->update(['is_default_wall_master' => 0]);
//
//            // 设置新的默认墙主
//            $result = $this->where('user_id', $userId)->update(['is_default_wall_master' => 1]);
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
     * 更新用户VIP状态
     * @param string $userId
     * @param int $isVip 0-不是VIP 1-是VIP
     * @return bool
     */
    public function updateVipStatus($userId, $isVip = 1)
    {
        return $this->updateUserInfo($userId, [
            'is_vip' => $isVip ? 1 : 0
        ]);
    }

    /**
     * 更新用户信息
     * @param string $userId
     * @param array $data
     * @return bool
     */
    public function updateUserInfo($userId, $data)
    {
        return $this->where('user_id', $userId)->update($data);
    }

    /**
     * 判断用户是否为VIP
     * @param string $userId
     * @return bool
     */
    public function isVip($userId)
    {
        $user = $this->where('user_id', $userId)->find();
        return $user && $user['is_vip'] == 1;
    }
}
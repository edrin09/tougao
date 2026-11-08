<?php
namespace app\common\model;

use app\common\model\BaseModel as Model;
use app\common\helper\DateHelper;

class Admin extends Model
{
    // 设置数据表名
    protected $table = 'cw_admin';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';
    
    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 创建时间和更新时间字段
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 字段列表
    protected $fields = [
        'id', 'username', 'password', 'nickname', 'avatar', 'email',
        'phone', 'status', 'last_login_time', 'last_login_ip', 'role',
        'create_time', 'update_time'
    ];
    
    // 隐藏字段
    protected $hidden = ['password'];
    
    /**
     * 检查密码是否正确
     * @param string $password
     * @return bool
     */
    public function checkPassword($password)
    {
        trace('登录密码: ' . $password, 'info');
        trace('登录密码MD5: ' . md5($password), 'info');
        trace('数据库密码: ' . $this->password, 'info');
        return md5($password) === $this->password;
    }
    
    /**
     * 关联日志
     * @return \think\model\relation\HasMany
     */
    public function logs()
    {
        return $this->hasMany('AdminLog', 'admin_id', 'id');
    }
    
    /**
     * 重写新增方法，处理时间
     * @param array $data
     * @param string|array $field 允许字段
     * @param bool $replace 是否replace
     * @return Admin
     */
    public static function create($data = [], $field = null, $replace = false)
    {
        if (!isset($data['create_time'])) {
            $data['create_time'] = DateHelper::now();
        }
        
        if (!isset($data['update_time'])) {
            $data['update_time'] = DateHelper::now();
        }
        
        return parent::create($data, $field, $replace);
    }
    
    /**
     * 重写更新前钩子
     * @param Admin $model
     * @return void
     */
    public static function onBeforeUpdate($model)
    {
        // 更新时间
        $model->update_time = DateHelper::now();
    }
    
    /**
     * 检查是否为超级管理员
     * @return bool
     */
    public function isSuper()
    {
        return $this->role === 'super';
    }
}
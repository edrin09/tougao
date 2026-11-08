<?php
namespace app\common\model;

use app\common\model\BaseModel as Model;
use app\common\helper\DateHelper;

class AdminLog extends Model
{
    // 设置数据表名
    protected $table = 'cw_admin_log';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = 'datetime';
    
    // 时间格式
    protected $dateFormat = 'Y-m-d H:i:s';
    
    // 创建时间字段
    protected $createTime = 'create_time';
    protected $updateTime = false;
    
    // 字段列表
    protected $fields = [
        'id', 'admin_id', 'username', 'action', 'details', 'ip',
        'user_agent', 'create_time'
    ];
    
    /**
     * 关联管理员
     * @return \think\model\relation\BelongsTo
     */
    public function admin()
    {
        return $this->belongsTo('Admin', 'admin_id', 'id');
    }
    
    /**
     * 记录管理员操作日志
     * @param string $action 操作名称
     * @param string $details 详细信息
     * @param int $adminId 管理员ID
     * @return bool
     */
    public static function record($action, $details = '', $adminId = null)
    {
        // 如果没有提供管理员ID，尝试从session获取
        if ($adminId === null) {
            $admin = session('admin');
            $adminId = $admin ? $admin['id'] : 0;
        }
        
        // 获取管理员信息
        $admin = Admin::get($adminId);
        $username = $admin ? $admin->username : '';
        
        // 获取请求信息
        $request = request();
        
        return self::create([
            'admin_id' => $adminId,
            'username' => $username,
            'action' => $action,
            'details' => $details,
            'ip' => $request->ip(),
            'user_agent' => $request->header('user-agent')
        ]);
    }
    
    /**
     * 获取管理员用户名
     * @param int $adminId 管理员ID
     * @return string
     */
    private static function getAdminUsername($adminId)
    {
        $admin = Admin::where('id', $adminId)->value('username');
        return $admin ?: '';
    }
}
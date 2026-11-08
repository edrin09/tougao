<?php

namespace app\common\model;

use app\common\model\BaseModel as Model;

class Notification extends Model
{
    // 设置表名
    protected $table = 'notifications';

    // 设置主键
    protected $pk = 'id';

    // 设置字段信息
    protected $schema = [
        'id'         => 'int',
        'type'       => 'int',
        'content'    => 'string',
        'created_at' => 'timestamp',
        'updated_at' => 'timestamp',
    ];

    // 通知类型常量
    const TYPE_PERSONAL = 1;  // 个人中心通知
    const TYPE_WALL_MASTER = 2;  // 墙主设置通知

    // 通知类型映射
    public static $typeMap = [
        self::TYPE_PERSONAL => '个人中心通知',
        self::TYPE_WALL_MASTER => '墙主设置通知'
    ];

    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';

    // 定义时间戳字段名
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    // 时间戳格式 - 使用datetime格式而不是timestamp
    protected $dateFormat = 'Y-m-d H:i:s';

    // 类型转换
    protected $type = [
        'id'         => 'integer',
        'type'       => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    // 获取器 - 确保时间格式正确
    public function getCreatedAtAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', is_numeric($value) ? $value : strtotime($value)) : null;
    }

    public function getUpdatedAtAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', is_numeric($value) ? $value : strtotime($value)) : null;
    }

    // 追加属性
    protected $append = [];

    // 隐藏属性
    protected $hidden = [];

    /**
     * 获取所有通知
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public static function getNotifications($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $list = self::order('created_at', 'desc')
            ->limit($offset, $limit)
            ->select()
            ->toArray();

        $total = self::count();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }

    /**
     * 获取通知列表（支持搜索和类型筛选）
     * @param int $page 页码
     * @param int $limit 每页数量
     * @param string $keyword 搜索关键词
     * @param int $type 通知类型
     * @return array
     */
    public static function getNotificationList($page = 1, $limit = 10, $keyword = '', $type = 0)
    {
        $offset = ($page - 1) * $limit;

        $query = self::order('created_at', 'desc');

        // 如果有搜索关键词，添加搜索条件
        if (!empty($keyword)) {
            $query->where('content', 'like', '%' . $keyword . '%');
        }

        // 如果指定了类型，添加类型筛选
        if ($type > 0) {
            $query->where('type', $type);
        }

        $list = $query->limit($offset, $limit)->select()->toArray();

        // 为列表项添加类型文本
        foreach ($list as &$item) {
            // 确保type字段存在且有效
            if (!isset($item['type']) || !array_key_exists($item['type'], self::$typeMap)) {
                $item['type'] = self::TYPE_PERSONAL; // 默认为个人中心通知
            }
            $item['type_text'] = self::$typeMap[$item['type']];
        }

        // 获取总数（也要考虑搜索条件和类型筛选）
        $totalQuery = self::where('1', '1');
        if (!empty($keyword)) {
            $totalQuery->where('content', 'like', '%' . $keyword . '%');
        }
        if ($type > 0) {
            $totalQuery->where('type', $type);
        }
        $total = $totalQuery->count();

        return [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'keyword' => $keyword,
            'type' => $type
        ];
    }

    /**
     * 添加通知
     * @param string $content 通知内容
     * @param int $type 通知类型
     * @return bool|int
     */
    public static function addNotification($content, $type = self::TYPE_PERSONAL)
    {
        $notification = new self();
        $notification->content = $content;
        $notification->type = $type;
        return $notification->save();
    }

    /**
     * 更新通知
     * @param int $id 通知ID
     * @param string $content 通知内容
     * @param int $type 通知类型
     * @return bool
     */
    public static function updateNotification($id, $content, $type = null)
    {
        $data = ['content' => $content];
        if ($type !== null) {
            $data['type'] = $type;
        }
        return self::where('id', $id)->update($data);
    }

    /**
     * 删除通知
     * @param int $id 通知ID
     * @return bool
     */
    public static function deleteNotification($id)
    {
        return self::where('id', $id)->delete();
    }

    /**
     * 获取通知详情
     * @param int $id 通知ID
     * @return array|null
     */
    public static function getNotificationById($id)
    {
        $notification = self::where('id', $id)->find();
        if (!$notification) {
            return null;
        }

        $data = $notification->toArray();
        $data['type_text'] = self::$typeMap[$data['type']] ?? '未知类型';

        return $data;
    }

    /**
     * 获取最新的通知
     * @param int $limit 数量限制
     * @return array
     */
    public static function getLatestNotifications($limit = 5)
    {
        return self::order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取通知统计信息
     * @return array
     */
    public static function getNotificationStats()
    {
        $total = self::count();

        // 今日新增
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');
        $todayCount = self::where('created_at', 'between', [$todayStart, $todayEnd])->count();

        // 本周新增
        $weekStart = date('Y-m-d 00:00:00', strtotime('this week'));
        $weekEnd = date('Y-m-d 23:59:59');
        $weekCount = self::where('created_at', 'between', [$weekStart, $weekEnd])->count();

        // 本月新增
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');
        $monthCount = self::where('created_at', 'between', [$monthStart, $monthEnd])->count();

        // 最新通知
        $latestNotification = self::order('created_at', 'desc')->find();

        return [
            'total' => $total,
            'today_count' => $todayCount,
            'week_count' => $weekCount,
            'month_count' => $monthCount,
            'latest_notification' => $latestNotification ? $latestNotification->toArray() : null
        ];
    }

    /**
     * 批量删除通知
     * @param array $ids 通知ID数组
     * @return int 删除成功的数量
     */
    public static function batchDeleteNotifications($ids)
    {
        if (empty($ids) || !is_array($ids)) {
            return 0;
        }

        return self::where('id', 'in', $ids)->delete();
    }

    /**
     * 根据类型获取通知列表
     * @param int $type 通知类型
     * @param int $limit 数量限制
     * @return array
     */
    public static function getNotificationsByType($type, $limit = 10)
    {
        $list = self::where('type', $type)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        // 为列表项添加类型文本
        foreach ($list as &$item) {
            $item['type_text'] = self::$typeMap[$item['type']] ?? '未知类型';
        }

        return $list;
    }

    /**
     * 获取个人中心通知
     * @param int $limit 数量限制
     * @return array
     */
    public static function getPersonalNotifications($limit = 10)
    {
        return self::getNotificationsByType(self::TYPE_PERSONAL, $limit);
    }

    /**
     * 获取墙主设置通知
     * @param int $limit 数量限制
     * @return array
     */
    public static function getWallMasterNotifications($limit = 10)
    {
        return self::getNotificationsByType(self::TYPE_WALL_MASTER, $limit);
    }

    /**
     * 获取通知类型列表
     * @return array
     */
    public static function getTypeList()
    {
        return self::$typeMap;
    }
}

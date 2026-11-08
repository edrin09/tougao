<?php

namespace app\wss\service\comm;

use PDO;
use think\Exception;
use think\exception\PDOException;

/**
 * 增强版数据库操作类
 * 支持 MySQL, PostgreSQL, SQLite 等多种数据库
 * 支持 IN、LIKE、BETWEEN 等复杂查询
 */
class DatabasesService
{
    private static $instance = null;
    private $pdo;
    private $config;
    private $lastQuery;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $defaultConfig = [
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        ];

        $config = include __DIR__ . '/../../../../config/database.php';
        $this->config = array_merge($defaultConfig, $config);
        $this->connect();
    }

    /**
     * 连接数据库
     */
    private function connect()
    {
        try {
            $dsn = $this->buildDsn();
            $this->pdo = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (PDOException $e) {
            throw new Exception("数据库连接失败: " . $e->getMessage());
        }
    }

    /**
     * 构建DSN字符串
     */
    private function buildDsn()
    {
        $type = strtolower($this->config['type']);

        switch ($type) {
            case 'mysql':
                return "mysql:host={$this->config['hostname']};port={$this->config['hostport']};dbname={$this->config['database']};charset={$this->config['charset']}";

            case 'pgsql':
                return "pgsql:host={$this->config['hostname']};port={$this->config['hostport']};dbname={$this->config['database']}";

            case 'sqlite':
                return "sqlite:{$this->config['database']}";

            case 'sqlsrv':
                return "sqlsrv:Server={$this->config['hostname']},{$this->config['hostport']};Database={$this->config['database']}";

            default:
                throw new Exception("不支持的数据库类型: {$type}");
        }
    }

    /**
     * 单例模式获取实例
     */
    public static function getInstance($config = [])
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    /**
     * 执行查询语句，返回结果集
     */
    public function query($sql, $params = [])
    {
        try {
            $stmt = $this->executeStatement($sql, $params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }

    /**
     * 执行预处理语句
     */
    private function executeStatement($sql, $params = [])
    {
        $this->lastQuery = [
            'sql' => $sql,
            'params' => $params
        ];

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt;
    }

    /**
     * 执行更新语句，返回影响行数
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->executeStatement($sql, $params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("更新执行失败: " . $e->getMessage());
        }
    }

    /**
     * 获取单条记录
     */
    public function getOne($sql, $params = [])
    {
        try {
            $stmt = $this->executeStatement($sql, $params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }

    /**
     * 获取单个值
     */
    public function getValue($sql, $params = [])
    {
        try {
            $stmt = $this->executeStatement($sql, $params);
            $result = $stmt->fetch(PDO::FETCH_NUM);
            return $result ? $result[0] : null;
        } catch (PDOException $e) {
            throw new Exception("查询执行失败: " . $e->getMessage());
        }
    }

    /**
     * 插入数据
     */
    public function insert($table, $data)
    {
        $table = $this->getTableName($table);
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";

        return $this->execute($sql, $data);
    }

    /**
     * 获取带前缀的表名
     */
    private function getTableName($table)
    {
        return $this->config['prefix'] . $table;
    }

    /**
     * 更新数据
     */
    public function update($table, $data, $where, $whereParams = [])
    {
        $table = $this->getTableName($table);
        $setParts = [];

        foreach (array_keys($data) as $field) {
            $setParts[] = "{$field} = :{$field}";
        }

        $sql = "UPDATE {$table} SET " . implode(', ', $setParts) . " WHERE {$where}";
        $params = array_merge($data, $whereParams);

        return $this->execute($sql, $params);
    }

    /**
     * 删除数据
     */
    public function delete($table, $where, $params = [])
    {
        $table = $this->getTableName($table);
        $sql = "DELETE FROM {$table} WHERE {$where}";

        return $this->execute($sql, $params);
    }

    /**
     * 开启事务
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        return $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        return $this->pdo->rollBack();
    }

    /**
     * 获取最后插入的ID
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * 获取最后执行的SQL
     */
    public function getLastQuery()
    {
        return $this->lastQuery;
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        $this->pdo = null;
        self::$instance = null;
    }

    /**
     * 防止反序列化
     */
    public function __wakeup()
    {
    }

    /**
     * 防止克隆
     */
    private function __clone()
    {
    }
}

/**
 * 增强版查询构建器类
 * 支持 IN、LIKE、BETWEEN 等复杂查询
 */
class QueryBuilder
{
    private $db;
    private $table;
    private $fields = '*';
    private $conditions = [];
    private $params = [];
    private $orderBy = '';
    private $limit = '';
    private $joins = [];
    private $groupBy = '';
    private $having = '';

    public function __construct(DatabasesService $db)
    {
        $this->db = $db;
    }

    /**
     * 设置表名
     */
    public function table($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 设置查询字段
     */
    public function fields($fields)
    {
        if (is_array($fields)) {
            $this->fields = implode(', ', $fields);
        } else {
            $this->fields = $fields;
        }
        return $this;
    }

    /**
     * 基础WHERE条件
     */
    public function where($field, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $paramName = $this->generateParamName($field);
        $this->conditions[] = "{$field} {$operator} :{$paramName}";
        $this->params[$paramName] = $value;

        return $this;
    }

    /**
     * 生成参数名
     */
    private function generateParamName($field)
    {
        static $paramCount = 0;
        $paramCount++;
        return 'param_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $field) . '_' . $paramCount;
    }

    /**
     * WHERE IN 条件
     */
    public function whereIn($field, $values)
    {
        if (empty($values)) {
            $this->conditions[] = "1 = 0"; // 空数组返回无结果
            return $this;
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = $this->generateParamName($field . '_in_' . $index);
            $placeholders[] = ":{$paramName}";
            $this->params[$paramName] = $value;
        }

        $this->conditions[] = "{$field} IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    /**
     * WHERE NOT IN 条件
     */
    public function whereNotIn($field, $values)
    {
        if (empty($values)) {
            return $this; // 空数组返回所有结果
        }

        $placeholders = [];
        foreach ($values as $index => $value) {
            $paramName = $this->generateParamName($field . '_not_in_' . $index);
            $placeholders[] = ":{$paramName}";
            $this->params[$paramName] = $value;
        }

        $this->conditions[] = "{$field} NOT IN (" . implode(', ', $placeholders) . ")";
        return $this;
    }

    /**
     * WHERE LIKE 条件
     */
    public function whereLike($field, $value, $position = 'both')
    {
        $paramName = $this->generateParamName($field);

        switch ($position) {
            case 'left':
                $searchValue = "%{$value}";
                break;
            case 'right':
                $searchValue = "{$value}%";
                break;
            case 'both':
            default:
                $searchValue = "%{$value}%";
                break;
        }

        $this->conditions[] = "{$field} LIKE :{$paramName}";
        $this->params[$paramName] = $searchValue;

        return $this;
    }

    /**
     * WHERE NOT LIKE 条件
     */
    public function whereNotLike($field, $value, $position = 'both')
    {
        $paramName = $this->generateParamName($field);

        switch ($position) {
            case 'left':
                $searchValue = "%{$value}";
                break;
            case 'right':
                $searchValue = "{$value}%";
                break;
            case 'both':
            default:
                $searchValue = "%{$value}%";
                break;
        }

        $this->conditions[] = "{$field} NOT LIKE :{$paramName}";
        $this->params[$paramName] = $searchValue;

        return $this;
    }

    /**
     * WHERE BETWEEN 条件
     */
    public function whereBetween($field, $value1, $value2)
    {
        $paramName1 = $this->generateParamName($field . '_from');
        $paramName2 = $this->generateParamName($field . '_to');

        $this->conditions[] = "{$field} BETWEEN :{$paramName1} AND :{$paramName2}";
        $this->params[$paramName1] = $value1;
        $this->params[$paramName2] = $value2;

        return $this;
    }

    /**
     * WHERE IS NULL 条件
     */
    public function whereNull($field)
    {
        $this->conditions[] = "{$field} IS NULL";
        return $this;
    }

    /**
     * WHERE IS NOT NULL 条件
     */
    public function whereNotNull($field)
    {
        $this->conditions[] = "{$field} IS NOT NULL";
        return $this;
    }

    /**
     * OR WHERE 条件
     */
    public function orWhere($field, $operator, $value = null)
    {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }

        $paramName = $this->generateParamName($field);
        $this->conditions[] = "OR {$field} {$operator} :{$paramName}";
        $this->params[$paramName] = $value;

        return $this;
    }

    /**
     * 原始WHERE条件
     */
    public function whereRaw($condition, $params = [])
    {
        $this->conditions[] = $condition;
        $this->params = array_merge($this->params, $params);
        return $this;
    }

    /**
     * ORDER BY
     */
    public function orderBy($orderBy)
    {
        $this->orderBy = $orderBy ? "ORDER BY {$orderBy}" : '';
        return $this;
    }

    /**
     * JOIN
     */
    public function join($table, $condition, $type = 'INNER')
    {
        $this->joins[] = "{$type} JOIN {$table} ON {$condition}";
        return $this;
    }

    /**
     * GROUP BY
     */
    public function groupBy($groupBy)
    {
        $this->groupBy = $groupBy ? "GROUP BY {$groupBy}" : '';
        return $this;
    }

    /**
     * HAVING
     */
    public function having($having)
    {
        $this->having = $having ? "HAVING {$having}" : '';
        return $this;
    }

    /**
     * 获取所有记录
     */
    public function getAll()
    {
        $sql = $this->buildSelectSql();
        return $this->db->query($sql, $this->params);
    }

    /**
     * 构建SELECT SQL
     */
    private function buildSelectSql()
    {
        $joins = implode(' ', $this->joins);
        $where = $this->buildWhereClause();

        $sql = "SELECT {$this->fields} FROM {$this->table}";
        if ($joins) $sql .= " {$joins}";
        if ($where) $sql .= " WHERE {$where}";
        if ($this->groupBy) $sql .= " {$this->groupBy}";
        if ($this->having) $sql .= " {$this->having}";
        if ($this->orderBy) $sql .= " {$this->orderBy}";
        if ($this->limit) $sql .= " {$this->limit}";

        return $sql;
    }

    /**
     * 构建WHERE子句
     */
    private function buildWhereClause()
    {
        if (empty($this->conditions)) {
            return '';
        }

        $where = '';
        foreach ($this->conditions as $condition) {
            if (empty($where)) {
                $where = $condition;
            } else {
                if (strpos($condition, 'OR ') === 0) {
                    $where .= " {$condition}";
                } else {
                    $where .= " AND {$condition}";
                }
            }
        }

        return $where;
    }

    /**
     * 计数
     */
    public function count()
    {
        $this->fields = 'COUNT(*) as count';
        $result = $this->getOne();
        return $result ? (int)$result['count'] : 0;
    }

    /**
     * 获取单条记录
     */
    public function getOne()
    {
        $this->limit(1);
        $sql = $this->buildSelectSql();
        $result = $this->db->query($sql, $this->params);
        return $result ? $result[0] : null;
    }

    /**
     * LIMIT
     */
    public function limit($limit, $offset = 0)
    {
        if ($offset > 0) {
            $this->limit = "LIMIT {$offset}, {$limit}";
        } else {
            $this->limit = "LIMIT {$limit}";
        }
        return $this;
    }

    /**
     * 插入数据
     */
    public function insert($data)
    {
        return $this->db->insert($this->table, $data);
    }

    /**
     * 更新数据
     */
    public function update($data)
    {
        $where = $this->buildWhereClause();
        return $this->db->update($this->table, $data, $where, $this->params);
    }

    /**
     * 删除数据
     */
    public function delete()
    {
        $where = $this->buildWhereClause();
        return $this->db->delete($this->table, $where, $this->params);
    }

    /**
     * 重置查询构建器
     */
    public function reset()
    {
        $this->fields = '*';
        $this->conditions = [];
        $this->params = [];
        $this->orderBy = '';
        $this->limit = '';
        $this->joins = [];
        $this->groupBy = '';
        $this->having = '';
        return $this;
    }
}


/**
 * 使用示例
 */
//try {
//    // 数据库配置
//    $config = [
//        'type' => 'mysql',
//        'host' => 'localhost',
//        'port' => 3306,
//        'dbname' => 'test_db',
//        'username' => 'root',
//        'password' => 'password',
//        'charset' => 'utf8mb4',
//        'prefix' => 'tbl_'
//    ];
//
//    // 创建数据库实例
//    $db = Database::getInstance($config);
//    $query = new QueryBuilder($db);
//
//    echo "=== IN 查询示例 ===\n";
//    // IN 查询：查询ID在指定列表中的用户
//    $users = $query->table('users')
//        ->whereIn('id', [1, 3, 5, 7, 9])
//        ->where('status', 1)
//        ->orderBy('id DESC')
//        ->getAll();
//
//    print_r($users);
//
//    echo "\n=== LIKE 查询示例 ===\n";
//    // LIKE 查询：查询名字包含"张"的用户
//    $query->reset();
//    $users = $query->table('users')
//        ->whereLike('name', '张')
//        ->where('age', '>', 18)
//        ->getAll();
//
//    print_r($users);
//
//    echo "\n=== 多条件组合查询示例 ===\n";
//    $query->reset();
//    $users = $query->table('users')
//        ->where('status', 1)
//        ->whereIn('role', ['admin', 'editor', 'user'])
//        ->whereLike('email', '@gmail.com', 'right')
//        ->whereBetween('age', 20, 35)
//        ->whereNotNull('phone')
//        ->orderBy('created_at DESC')
//        ->limit(10)
//        ->getAll();
//
//    print_r($users);
//
//    echo "\n=== 复杂OR条件示例 ===\n";
//    $query->reset();
//    $users = $query->table('users')
//        ->where('status', 1)
//        ->orWhere('vip_level', '>', 3)
//        ->whereLike('name', '王', 'right')
//        ->getAll();
//
//    print_r($users);
//
//    echo "\n=== 更新操作示例 ===\n";
//    $query->reset();
//    $affectedRows = $query->table('users')
//        ->whereIn('id', [2, 4, 6])
//        ->where('status', 0)
//        ->update([
//            'status' => 1,
//            'updated_at' => date('Y-m-d H:i:s')
//        ]);
//
//    echo "更新了 {$affectedRows} 条记录\n";
//
//    echo "\n=== 删除操作示例 ===\n";
//    $query->reset();
//    $affectedRows = $query->table('users')
//        ->where('status', -1)
//        ->whereNotNull('deleted_at')
//        ->delete();
//
//    echo "删除了 {$affectedRows} 条记录\n";
//
//    echo "\n=== JOIN 查询示例 ===\n";
//    $query->reset();
//    $results = $query->table('users as u')
//        ->fields(['u.id', 'u.name', 'p.title as post_title', 'p.created_at'])
//        ->join('posts as p', 'u.id = p.user_id')
//        ->where('u.status', 1)
//        ->where('p.status', 'published')
//        ->whereLike('p.title', '教程')
//        ->orderBy('p.created_at DESC')
//        ->limit(5)
//        ->getAll();
//
//    print_r($results);
//
//    echo "\n=== 分组统计示例 ===\n";
//    $query->reset();
//    $stats = $query->table('users')
//        ->fields(['role', 'COUNT(*) as count', 'AVG(age) as avg_age'])
//        ->where('status', 1)
//        ->groupBy('role')
//        ->having('COUNT(*) > 5')
//        ->orderBy('count DESC')
//        ->getAll();
//
//    print_r($stats);
//
//} catch (Exception $e) {
//    echo "数据库错误: " . $e->getMessage() . "\n";
//}
//
//// 基础Database类的IN和LIKE使用示例
//echo "\n=== 基础Database类的IN和LIKE使用 ===\n";
//try {
//    // IN 查询
//    $ids = [1, 2, 3, 4, 5];
//    $placeholders = str_repeat('?,', count($ids) - 1) . '?';
//    $users = $db->query("SELECT * FROM users WHERE id IN ({$placeholders}) AND status = ?", array_merge($ids, [1]));
//
//    // LIKE 查询
//    $users = $db->query("SELECT * FROM users WHERE name LIKE ? AND email LIKE ?", ['%张%', '%@gmail.com']);
//
//} catch (Exception $e) {
//    echo "基础查询错误: " . $e->getMessage() . "\n";
//}
//
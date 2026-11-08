<?php
namespace app\http\middleware;

use think\Request;
use think\facade\Cache;
use think\Db;

class AdminAuth
{
    public function handle(Request $request, \Closure $next)
    {
        // 登录接口无需验证
        if ($request->controller() == 'Manager' && $request->action() == 'login') {
            return $next($request);
        }
        
        // 获取token
        $token = $request->header('Authorization');
        if (empty($token)) {
            return json(['code' => 401, 'message' => '未授权，请先登录']);
        }
        
        // 解析token
        if (strpos($token, 'Bearer ') === 0) {
            $token = substr($token, 7);
        }
        
        // 根据token获取管理员ID
        $adminId = $this->getAdminIdByToken($token);
        if (!$adminId) {
            return json(['code' => 401, 'message' => '登录已过期，请重新登录']);
        }
        
        // 将管理员ID添加到请求中，便于控制器使用
        $request->adminId = $adminId;
        
        return $next($request);
    }
    
    /**
     * 根据token获取管理员ID
     * @param string $token
     * @return int|null
     */
    private function getAdminIdByToken($token)
    {
        // 直接使用备用方法，因为ThinkPHP的Cache没有getCacheList方法
        return $this->getAdminIdByTokenFallback($token);
    }
    
    /**
     * 备用方法：遍历数据库查找token对应的管理员ID
     * @param string $token
     * @return int|null
     */
    private function getAdminIdByTokenFallback($token)
    {
        // 使用模型查询所有管理员
        try {
            $admins = \app\common\model\Admin::field('id')->select();
            
            // 遍历检查每个管理员的token
            foreach ($admins as $admin) {
                $cacheKey = 'admin_token_' . $admin['id'];
                $cachedToken = Cache::get($cacheKey);
                
                if ($cachedToken && $cachedToken === $token) {
                    return (int)$admin['id'];
                }
            }
        } catch (\Exception $e) {
            // 如果查询失败，记录错误并返回null
            trace('[AdminAuth] Token验证失败: ' . $e->getMessage(), 'error');
        }
        
        return null;
    }
} 
<?php
namespace app\common\exception;

use Exception;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Request;
use think\Response;

class ApiException extends Handle
{
    public function render(Exception $e)
    {
        // 参数验证错误
        if ($e instanceof ValidateException) {
            return json([
                'code' => 605,
                'msg' => $e->getMessage()
            ])->code(200);
        }
        
        // 请求异常
        if ($e instanceof HttpException && Request::isAjax()) {
            return json([
                'code' => $e->getStatusCode(),
                'msg' => $e->getMessage(),
            ])->code($e->getStatusCode());
        }
        
        // 自定义异常处理
        $data = [
            'code' => $e->getCode() ?: 500,
            'msg' => Config::get('app_debug') ? $e->getMessage() : '服务器内部错误',
        ];
        
        // 调试模式下输出更多信息
        if (Config::get('app_debug')) {
            $data['line'] = $e->getLine();
            $data['file'] = $e->getFile();
//            $data['trace'] = $e->getTraceAsString();
        }
        
        return json($data)->code(200);
    }
} 
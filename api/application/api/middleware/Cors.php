<?php
namespace app\api\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        $header = [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET,POST,OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type,Authorization,X-Requested-With',
        ];
        
        if ($request->isOptions()) {
            return response()->code(204)->header($header);
        }
        
        return $next($request)->header($header);
    }
} 
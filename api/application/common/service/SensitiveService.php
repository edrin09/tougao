<?php

namespace app\common\service;

use app\common\model\Config as ConfigModel;
use isszz\sensitive\Sensitive;

class SensitiveService
{
    protected $sensitive;

    // 构造函数
    public function __construct()
    {
        // 实例化 Sensitive 类
        $this->sensitive = new Sensitive();
        // 1. 设置干扰因子（根据您的需要设置）
        $this->sensitive->interferenceFactor([' ', '&', '*', '/', '|', '@', '.', '^', '~', '$']);

        // 2. 加载自定义敏感词库 - 文件模式
        $sensitive = ConfigModel::where('name', 'sensitive_file_path')->find();

        if ($sensitive) {
            $customDictPath = $sensitive->value;
        } else {
            $customDictPath = app()->getRootPath() . 'config/sensitive/customize.txt'; // 加载默认敏感词库
        }

        if (file_exists($customDictPath)) {
            $this->sensitive->custom($customDictPath);
        }
    }

    public function doCheck($content)
    {
        return $this->sensitive->is($content);
    }


    public function doReplace($content)
    {
        $replaced = $this->sensitive->replace($content, '*');
        return $replaced;
    }

    public function remove($content)
    {
        return $this->sensitive->remove($content);
    }

    public function add($content)
    {
        $this->sensitive->add($content);
    }
}
<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Config as ConfigModel;
use think\response\Json;

class Config extends AdminBase
{
    /**
     * 获取配置
     * @return Json
     */
    public function getConfig()
    {
        $ConfigModel = new ConfigModel();
        $config = $ConfigModel->getAll();
        return $this->success('获取成功', null, ['config' => $config]);
    }

    /**
     * 敏感词上传
     * @return Json
     */
    public function sensitiveUpload()
    {
        $ConfigModel = new ConfigModel();
        $sensitive = $ConfigModel->where('name', 'sensitive_file_path')->find();
        if (!$sensitive) {
            return $this->error('敏感词配置不存在');
        }


        $file = request()->file('file');
        //只能上传txt

        $info = $file->getInfo();
        $filename = $info['name'];
        // 获取文件扩展名
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array($extension, ['txt', 'csv'])) {
            return $this->error('只能上传txt文件');
        }
        $info = $file->move('sensitive');
        if ($info) {
            $sensitive->value = app()->getRootPath() . 'public/' . $info->getpath() . '/' . $info->getFilename();
            $sensitive->save();

            return $this->success('上传成功');
        } else {
            // 上传失败获取错误信息
            return $this->error($file->getError());
        }
    }
} 
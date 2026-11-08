<?php
namespace app\api\controller;

use app\common\controller\Api;
use think\facade\Config;
use think\Request;
use think\File;
use app\common\helper\UrlHelper;

class Upload extends Api
{
    protected $noNeedLogin = ['image', 'sign'];

    /**
     * 获取上传签名
     * @param Request $request
     * @return \think\response\Json
     */
    public function sign(Request $request)
    {
        $fileType = $request->param('fileType');
        $fileName = $request->param('fileName');

        if (empty($fileType) || empty($fileName)) {
            return $this->error('参数错误');
        }

        // 这里简化处理，实际应该对接具体的云存储服务如阿里云OSS、腾讯云COS等
        // 获取上传策略和签名
        $uploadConfig = Config::get('upload.');
        $dir = $fileType === 'image' ? 'images/' : 'videos/';
        $key = $dir . date('Ymd') . '/' . uniqid() . '_' . $fileName;

        // 模拟获取签名
        $policy = base64_encode(json_encode([
            'expiration' => date('Y-m-d\TH:i:s\Z', time() + 3600),
            'conditions' => [
                ['content-length-range', 0, 10485760], // 10MB
            ]
        ]));
        $signature = base64_encode(hash_hmac('sha1', $policy, $uploadConfig['secret'], true));

        return $this->success('获取签名成功', null, [
            'uploadUrl' => $uploadConfig['upload_url'],
            'sign' => $signature,
            'key' => $key,
            'policy' => $policy
        ]);
    }

    /**
     * 上传图片
     * @param Request $request
     * @return \think\response\Json
     */
    public function image(Request $request)
    {
        try {
        $file = $request->file('file');
        if (empty($file)) {
                trace('[Upload Error] 未获取到上传文件', 'error');
            return $this->error('请选择上传文件');
        }

        // 获取上传配置
        $uploadConfig = Config::get('upload.');
            if (empty($uploadConfig)) {
                trace('[Upload Error] 无法获取上传配置', 'error');
                return $this->error('系统配置错误');
            }

            // 检查上传目录
            $savePath = $uploadConfig['save_path'] . '/images';
            if (!is_dir($savePath)) {
                // 尝试创建目录
                if (!mkdir($savePath, 0755, true)) {
                    trace('[Upload Error] 无法创建上传目录: ' . $savePath, 'error');
                    return $this->error('无法创建上传目录');
                }
            }

        $sizeLimit = isset($uploadConfig['image_size']) ? $uploadConfig['image_size'] : 2; // 默认2MB
        $sizeLimit = $sizeLimit * 1024 * 1024;

        // 类型限制
        $allowExts = isset($uploadConfig['image_exts']) ? $uploadConfig['image_exts'] : 'jpg,jpeg,png,gif';
        $allowExts = explode(',', $allowExts);

            trace('[Upload Debug] 开始验证文件: ' . json_encode($file), 'info');

        // 验证文件
        $info = $file->validate([
            'size' => $sizeLimit,
            'ext' => $allowExts
        ])->rule(function() {
            return date('Ymd') . '/' . md5(microtime(true));
            })->move($savePath);

        if (!$info) {
                trace('[Upload Error] 验证或移动文件失败: ' . $file->getError(), 'error');
            return $this->error($file->getError());
        }

        // 生成访问URL
        $url = UrlHelper::generateImageUrl($info->getSaveName());
        trace('[Upload Debug] 上传成功: ' . $url, 'info');

            return $this->success('上传成功', null, [
            'url' => $url
        ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => $e->getMessage(), 'data' => null]);
        }
    }
}
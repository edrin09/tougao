<?php
// +----------------------------------------------------------------------
// | URL辅助类
// +----------------------------------------------------------------------

namespace app\common\helper;

use think\facade\Config;

class UrlHelper
{
    /**
     * 生成完整的文件访问URL
     * @param string $relativePath 相对路径，如 '/uploads/images/20250126/abc.jpg'
     * @return string 完整的URL
     */
    public static function generateFileUrl($relativePath)
    {
        return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $relativePath;


//        $uploadConfig = Config::get('upload.');
//        $domain = $uploadConfig['domain'] ?? 'http://localhost';
//        $pathPrefix = $uploadConfig['path_prefix'] ?? '';
//
//        // 确保相对路径以/开头
//        if (substr($relativePath, 0, 1) !== '/') {
//            $relativePath = '/' . $relativePath;
//        }
//
//        return $domain . $pathPrefix . $relativePath;
    }

    /**
     * 生成上传图片的完整URL
     * @param string $fileName 文件名，如 '20250126/abc.jpg'
     * @return string 完整的URL
     */
    public static function generateImageUrl($fileName)
    {
        return self::generateFileUrl('/uploads/images/' . $fileName);
    }

    /**
     * 生成头像的完整URL
     * @param string $fileName 头像文件名，如 'avatar1.jpg'
     * @return string 完整的URL
     */
    public static function generateAvatarUrl($fileName)
    {
        return self::generateFileUrl('/avatar/' . $fileName);
    }

    /**
     * 生成二维码的完整URL
     * @param string $fileName 二维码文件名，如 'qrcode123.jpg'
     * @return string 完整的URL
     */
    public static function generateQrcodeUrl($fileName)
    {
        return self::generateFileUrl('/uploads/qrcode/' . $fileName);
    }

    /**
     * 生成打款凭证的完整URL
     * @param string $fileName 打款凭证文件名，如 'withdrawal123.jpg'
     * @return string 完整的URL
     */
    public static function generateWithdrawalUrl($fileName)
    {
        return self::generateFileUrl('/uploads/withdrawal/' . $fileName);
    }

    /**
     * 生成视频的完整URL
     * @param string $fileName 视频文件名，如 '20250126/video.mp4'
     * @return string 完整的URL
     */
    public static function generateVideoUrl($fileName)
    {
        return self::generateFileUrl('/uploads/videos/' . $fileName);
    }
}

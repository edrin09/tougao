<?php
namespace app\admin\controller;

use think\Request;
use app\common\controller\AdminBase;
use app\common\model\Tutorial as TutorialModel;

/**
 * 使用教程管理（仅一条记录）
 * 路由示例：
 *   GET    api/admin/tutorial     => 获取内容
 *   POST   api/admin/tutorial     => 保存内容
 */
class Tutorial extends AdminBase
{
    /**
     * 获取使用教程内容
     * @return \think\response\Json
     */
    public function get()
    {
        $model   = new TutorialModel();
        $content = $model->getContent();
        return $this->success('获取成功', null, ['content' => $content]);
    }

    /**
     * 保存使用教程
     * @param Request $request
     * @return \think\response\Json
     */
    public function save(Request $request)
    {
        $html = $request->param('content', '', '');
        if ($html === null) {
            $html = '';
        }
        $model = new TutorialModel();
        $ok    = $model->saveContent($html);
        if ($ok) {
            return $this->success('保存成功');
        }
        return $this->error('保存失败');
    }
} 
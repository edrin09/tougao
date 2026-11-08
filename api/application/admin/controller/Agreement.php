<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\model\Agreement as AgreementModel;
use think\Request;
use think\response\Json;

/**
 * 用户协议管理（仅一条记录）
 * 路由示例：
 *   GET    api/admin/agreement     => 获取内容
 *   POST   api/admin/agreement     => 保存内容
 */
class Agreement extends AdminBase
{
    /**
     * 获取使用教程内容
     * @return Json
     */
    public function get(Request $request)
    {
        $id = $request->param('id', '');
        if (empty($id)) {
            return $this->error('id必传');
        }

        $content = AgreementModel::find($id);
        if (!$content) {
            return $this->error('记录不存在');
        }
        return $this->success('获取成功', null, ['content' => $content['content']]);
    }

    /**
     * 保存使用教程
     * @param Request $request
     * @return Json
     */
    public function save(Request $request)
    {
        $id = $request->param('id', '');
        if (empty($id)) {
            return $this->error('id必传');
        }
        $html = $request->param('content', '', '');
        if ($html === null) {
            $html = '';
        }

        $exists = AgreementModel::where('id', $id)->find();
        if (!$exists) {
            return $this->error('记录不存在');
        }

        $exists->content = $html;
        $exists->update_time = date('Y-m-d H:i:s');
        $exists->save();

        return $this->success('保存成功');
    }
} 
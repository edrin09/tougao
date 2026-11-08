<?php
namespace app\admin\controller;

use think\Request;
use app\common\model\WallMasterApply;
use app\common\model\User;
use app\common\model\WallSettings;

class VipManager extends Manager
{
    /**
     * 更新申请的VIP状态
     * @param Request $request
     * @return \think\response\Json
     */
    public function updateApplyVipStatus(Request $request)
    {
        $applyId = $request->post('applyId');
        $isVip = (int)$request->post('isVip');
        
        if (empty($applyId)) {
            return json(['code' => 400, 'message' => '申请ID不能为空']);
        }
        
        if (!in_array($isVip, [0, 1])) {
            return json(['code' => 400, 'message' => 'VIP状态参数错误']);
        }
        
        $apply = WallMasterApply::get($applyId);
        if (!$apply) {
            return json(['code' => 404, 'message' => '申请不存在']);
        }
        
        try {
            // 更新申请的VIP状态
            $apply->is_vip = $isVip;
            $apply->save();
            
            // 更新用户VIP状态
            $userModel = new User();
            $userModel->updateVipStatus($apply->user_id, $isVip);
            
            // 如果是已通过的墙主，同时更新墙设置的VIP状态
            if ($apply->status == 1) {
                $wallSettingsModel = new WallSettings();
                $wallSettingsModel->updateVipStatus($apply->user_id, $isVip);
            }
            
            return json([
                'code' => 200, 
                'message' => 'VIP状态更新成功',
                'data' => [
                    'applyId' => $applyId,
                    'isVip' => $isVip
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '更新失败：' . $e->getMessage()]);
        }
    }
    
    /**
     * 批量更新VIP状态
     * @param Request $request
     * @return \think\response\Json
     */
    public function batchUpdateVipStatus(Request $request)
    {
        $userIds = $request->post('userIds/a');
        $isVip = (int)$request->post('isVip');
        
        if (empty($userIds) || !is_array($userIds)) {
            return json(['code' => 400, 'message' => '用户ID列表不能为空']);
        }
        
        if (!in_array($isVip, [0, 1])) {
            return json(['code' => 400, 'message' => 'VIP状态参数错误']);
        }
        
        try {
            $userModel = new User();
            $wallSettingsModel = new WallSettings();
            
            $successCount = 0;
            $failCount = 0;
            
            foreach ($userIds as $userId) {
                try {
                    // 更新用户VIP状态
                    if ($userModel->updateVipStatus($userId, $isVip)) {
                        // 同时更新墙设置的VIP状态
                        $wallSettingsModel->updateVipStatus($userId, $isVip);
                        $successCount++;
                    } else {
                        $failCount++;
                    }
                } catch (\Exception $e) {
                    $failCount++;
                }
            }
            
            return json([
                'code' => 200, 
                'message' => 'VIP状态批量更新完成',
                'data' => [
                    'success' => $successCount,
                    'fail' => $failCount,
                    'total' => count($userIds)
                ]
            ]);
        } catch (\Exception $e) {
            return json(['code' => 500, 'message' => '批量更新失败：' . $e->getMessage()]);
        }
    }
}

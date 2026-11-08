<?php

namespace app\admin\controller;

use app\common\controller\AdminBase;
use app\common\helper\UrlHelper;
use app\common\model\Withdraw as WithdrawModel;
use app\common\service\WechatOfficialService;
use think\Request;

/**
 * 提现管理控制器
 */
class Withdraw extends AdminBase
{
    protected $model;

    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $withdraw_status = $request->param('withdraw_status', '');
        $withdrawal_method = $request->param('withdrawal_method', '');

        list($list, $pagination) = $this->getCommList($page, $limit, '', $withdraw_status, $withdrawal_method);

        return $this->success('获取成功', null, [
            'list' => $list->items(),
            'pagination' => $pagination,
            'total' => $list->total()
        ]);
    }


    public function getCommList($page, $limit, $user_id, $withdraw_status, $withdrawal_method)
    {
        // 构建查询条件
        $where['is_wall_master'] = 1;

        // 状态筛选（审核状态）
        if ($withdraw_status != '') {
            $where['withdraw_status'] = $withdraw_status;
        }

        // 关键词搜索
        if ($withdrawal_method != '') {
            $where['withdrawal_method'] = $withdrawal_method;
        }

        // 人员筛选
        if (!empty($user_id)) {
            $where['cw_withdrawal.user_id'] = $user_id;
        }

        // 获取提现列表
        $list = $this->model
            ->where($where)
            ->field('withdrawal_id,cash_amount,cash_time,create_time,reject_remarks,withdraw_status,withdrawal_evidence,withdrawal_method,bank_card,bank_name,payee_name')
            ->withJoin(['user' => ['user_id', 'openid', 'nickname', 'avatar']])
            ->order('cw_withdrawal.create_time', 'desc')
            ->paginate([
                'list_rows' => $limit,
                'page' => $page
            ]);

        foreach ($list as $item) {
            $item->bank_card = $item->user->openid;
        }
        // 分页信息
        $pagination = [
            'current_page' => $list->getCurrentPage(),
            'total_pages' => $list->lastPage(),
            'total' => $list->total(),
            'per_page' => $limit,
            'has_prev' => $list->getCurrentPage() > 1,
            'has_next' => $list->getCurrentPage() < $list->lastPage()
        ];

        return [$list, $pagination];
    }


    //提现记录
    public function getHistoryList(Request $request)
    {
        $user_id = $request->param('user_id');
        if (empty($user_id)) {
            return $this->error('用户ID不能为空');
        }
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $withdraw_status = [3, 5, 10];
        $withdrawal_method = $request->param('withdrawal_method', '');

        list($list, $pagination) = $this->getCommList($page, $limit, $user_id, $withdraw_status, $withdrawal_method);

        return $this->success('获取成功', null, [
            'list' => $list->items(),
            'pagination' => $pagination,
            'total' => $list->total()
        ]);
    }

    //审核提现,线下需要上传打款凭证， 微信对接微信零钱
    public function reviewWithdrawal(Request $request)
    {
        $withdrawal_id = $this->request->post('withdrawal_id', 0);
        $withdraw_status = $this->request->post('withdraw_status', 0);
        $reject_remarks = $this->request->post('reject_remarks', '');
        $withdrawal = $this->model
            ->field('withdrawal_id,withdrawal_no,withdrawal_method,withdraw_status,cash_amount,cash_time,update_time')
            ->withJoin(['user' => ['user_id', 'openid', 'nickname', 'official_openid', 'unionid', 'is_official_subscribe']])
            ->where('withdrawal_id', $withdrawal_id)
            ->where('user.is_wall_master', 1)
//            ->fetchSql()
            ->find();
//        dd($withdrawal);
        if (!$withdrawal) {
            return $this->error('提现记录错误');
        }

        switch ($withdrawal->withdrawal_method) {
            case 'wechat':
                $rule['withdrawal_id'] = 'require|integer';
                $rule['withdraw_status'] = 'require|in:3,5';
                if ($withdraw_status == 3) {
                    $rule['reject_remarks'] = 'require';
                }
                $this->validate($this->request->post(), $rule);

                $this->chechWithdrawal($withdrawal, 0);

                if ($withdraw_status == 5) {
//                    $wechatPayService = new WechatPayService();
////                    $openid = $withdrawal->user->openid;
////                    $amount = $withdrawal->cash_amount * 100;  // 金额单位为分
////                    $desc = '墙主提现';
//
//                    $openid = 'oIBcD7mECFyu91NdeKx92GmC8Nto';
//                    $amount = '2';
//                    $desc = '测试付款';
//                    $res = $wechatPayService->paymentToChange($openid, $amount, $desc);
//
//                    dd($res);
//                    if ($res['code'] != 0) {
//                        return $this->error($res['msg']);
//                    }
                }

                break;

            case 'offline':

                $rule['withdrawal_id'] = 'require|integer';
                $rule['withdraw_status'] = 'require|in:2,3,5';
                if ($withdraw_status == 3) {
                    $this->chechWithdrawal($withdrawal, 0);
                    $rule['reject_remarks'] = 'require';
                }

                $this->validate($this->request->post(), $rule);

                if ($withdraw_status == 5) {
                    $this->chechWithdrawal($withdrawal, 2);
                    $file = $request->file('file');
                    if (!$file) {
                        return $this->error('请上传打款凭证');
                    }
                    $info = $file->move('uploads/withdrawal');
                    if (!$info) {
                        return $this->error('上传打款凭证失败: ' . $file->getError());
                    }

                    $file_url = UrlHelper::generateWithdrawalUrl($info->getSaveName());
                    $withdrawal->withdrawal_evidence = $file_url;
                    $withdrawal->cash_time = date('Y-m-d H:i:s');
                }

                break;
            default:

                return $this->error('提现方式不存在');
                break;
        }

        $withdrawal->reject_remarks = $reject_remarks;
        $withdrawal->withdraw_status = $withdraw_status;
        $withdrawal->save();

        if (in_array($withdraw_status, [3, 5])) {
            $WechatOfficialService = new WechatOfficialService();
            $WechatOfficialService->sendWithdrawalResult($withdrawal);
        }

        return $this->success('操作成功');
    }


    public function chechWithdrawal($withdrawal, $withdraw_status)
    {
        if ($withdrawal->withdraw_status != $withdraw_status) {
            throwError('提现状态错误');
        }

        return true;
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        parent::initialize();
        $this->model = new WithdrawModel();
    }
}
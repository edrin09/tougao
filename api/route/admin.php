<?php
// 后台管理系统路由
use think\facade\Route;

// 管理员路由组
Route::group('api/admin', function () {
    Route::post('login', 'admin/Manager/login');                                       //管理员登录
    Route::post('resetPassword', 'admin/Manager/resetPassword');                       //重置密码
    Route::get('dashboardStats', 'admin/Manager/dashboardStats');                      //获取仪表盘统计数据
    Route::get('config', 'admin/Config/getConfig');                                    //获取配置
    Route::get('recentActivities', 'admin/Manager/getRecentActivities');               //获取近期活动
    Route::get('wallMasterApplyList', 'admin/Manager/wallMasterApplyList');            //获取墙主申请列表
    Route::get('wallMasterApplyDetail', 'admin/Manager/wallMasterApplyDetail');        //获取墙主申请详情
    Route::post('reviewWallMasterApply', 'admin/Manager/reviewWallMasterApply');       //审核墙主申请
    Route::post('deleteWallMasterApply', 'admin/Manager/deleteWallMasterApply');       //删除墙主申请
//    Route::get('getDefaultWallMaster', 'admin/Manager/getDefaultWallMaster');                   //获取默认墙主
//    Route::post('setDefaultWallMaster', 'admin/Manager/setDefaultWallMaster');                  //设置默认墙主

    // 设置墙主分成比例
    Route::post('setShareRatio', 'admin/Manager/setShareRatio');                       //设置墙主分成比例

    // 墙列表
//    Route::get('wallList', 'admin/Manager/wallList');
//    Route::post('cancelWallMaster', 'admin/Manager/cancelWallMaster');
//    Route::post('updateWallMasterStatus', 'admin/Manager/updateWallMasterStatus');
//之前的，不知道用没有用到，新写逻辑
    Route::get('wallList', 'admin/Wall/getList');                                      //获取墙列表
    Route::get('getCommunityList', 'admin/Wall/getCommunityList');                    //获取社区列表
    Route::post('saveWallInfo', 'admin/Wall/saveWallInfo');                            //保存墙列表

    // 投稿管理
    Route::get('postList', 'admin/Manager/postList');                                  //获取投稿列表
    Route::get('postDetail', 'admin/Manager/postDetail');                              //获取投稿详情
    Route::post('reviewPost', 'admin/Manager/reviewPost');                             //审核投稿
    Route::post('publishPost', 'admin/Manager/publishPost');                           //发布投稿

    // VIP管理
    Route::post('updateApplyVipStatus', 'admin/VipManager/updateApplyVipStatus');      //更新VIP申请状态
    Route::post('batchUpdateVipStatus', 'admin/VipManager/batchUpdateVipStatus');      //批量更新VIP申请状态

    // 评论管理
    Route::get('comment/index', 'admin/comment/index');
    Route::get('comment/list', 'admin/comment/getList');
    Route::get('comment/detail/:id', 'admin/comment/getDetail');
    Route::post('comment/review/:id', 'admin/comment/review');
    Route::post('comment/batch-review', 'admin/comment/batchReview');
    Route::post('comment/delete/:id', 'admin/comment/delete');
    Route::post('comment/restore/:id', 'admin/comment/restore');
    Route::post('comment/batch-delete', 'admin/comment/batchDelete');
    Route::post('comment/batch-restore', 'admin/comment/batchRestore');
    Route::get('comment/stats', 'admin/comment/getStats');
    Route::post('comment/init-test-data', 'admin/comment/initTestData');

    // 投稿管理
    Route::get('post/list', 'admin/post/getList');                                     //获取投稿列表
    Route::get('post/detail/:id', 'admin/post/getDetail');                             //获取投稿详情
    Route::post('post/review/:id', 'admin/post/review');                               //审核投稿
    Route::post('post/publish/:id', 'admin/post/publish');                             //发布投稿
    Route::post('post/batch-review', 'admin/post/batchReview');                        //批量审核投稿
    Route::post('post/batch-publish', 'admin/post/batchPublish');                      //批量发布投稿
    Route::post('post/delete/:id', 'admin/post/delete');                               //删除投稿
    Route::get('post/stats', 'admin/post/getStats');                                   //获取投稿统计数据
    Route::post('post/init-test-data', 'admin/post/initTestData');                     //初始化测试数据
    Route::post('post/sensitiveWordFiltering', 'admin/post/sensitiveWordFiltering');   //开启/关闭敏感词过滤

    // 通知管理
    Route::get('notification/list', 'admin/notification/getList');                     //获取通知列表
    Route::get('notification/detail/:id', 'admin/notification/getDetail');             //获取通知详情
    Route::post('notification/add', 'admin/notification/add');                         //添加通知
    Route::post('notification/update/:id', 'admin/notification/update');               //更新通知
    Route::post('notification/delete/:id', 'admin/notification/delete');               //删除通知
    Route::get('notification/latest', 'admin/notification/getLatest');                 //获取最新通知
    Route::get('notification/count', 'admin/notification/getCount');                   //获取未读通知数量

    // 通知管理模块
    Route::get('notificationManager/list', 'admin/notificationManager/getList');                //获取通知管理列表
    Route::get('notificationManager/detail/:id', 'admin/notificationManager/getDetail');        //获取通知管理详情
    Route::post('notificationManager/add', 'admin/notificationManager/add');                    //添加通知管理
    Route::post('notificationManager/update/:id', 'admin/notificationManager/update');          //更新通知管理
    Route::post('notificationManager/delete/:id', 'admin/notificationManager/delete');          //删除通知管理
    Route::post('notificationManager/batchDelete', 'admin/notificationManager/batchDelete');    //批量删除通知管理
    Route::get('notificationManager/stats', 'admin/notificationManager/getStats');              //获取通知管理统计数据
    Route::get('notificationManager/typeList', 'admin/notificationManager/getTypeList');        //获取通知管理类型列表

    // 用户管理
//    Route::get('user/list', 'admin/UserManager/getUserList');
//    Route::post('user/delete', 'admin/UserManager/deleteUser');
    Route::post('getChannelsInfo', 'admin/UserManager/getChannelsInfo');                         //获取渠道信息

    // 使用教程（单条记录）
    Route::get('tutorial', 'admin/tutorial/get');                                  //获取使用教程
    Route::post('tutorial', 'admin/tutorial/save');                                //保存使用教程

    // 用户协议（单条记录）
    Route::get('agreement', 'admin/agreement/get');                                //获取用户协议
    Route::post('agreement', 'admin/agreement/save');                              //保存用户协议

    // 敏感词词库上传覆盖
    Route::post('config/sensitiveUpload', 'admin/config/sensitiveUpload');         //敏感词词库上传覆盖

    //提现管理
    Route::get('withdraw/list', 'admin/withdraw/getList');                         //获取提现列表
    Route::post('withdraw/review', 'admin/withdraw/reviewWithdrawal');             //审核提现
    Route::post('withdraw/history', 'admin/withdraw/getHistoryList');              //获取提现历史列表

    //社区管理
    Route::get('community/list', 'admin/community/getList');                       //获取社区列表
    Route::post('community/add', 'admin/community/add');                           //添加社区
    Route::post('community/update', 'admin/community/update');                     //更新社区
    Route::post('community/update_status', 'admin/community/updateStatus');        //更新社区状态

    //渠道管理
    Route::get('channels/list', 'admin/channels/getList');                         //获取渠道列表
    Route::post('channels/add', 'admin/channels/add');                             //添加渠道
    Route::post('channels/update', 'admin/channels/update');                       //更新渠道
    Route::post('channels/update_status', 'admin/channels/updateStatus');          //更新渠道状态
})->allowCrossDomain();

// 拦截处理跨域预检请求
Route::options('api/admin/:any', function () {
    return json(['code' => 200, 'message' => 'success']);
});
<?php
// +----------------------------------------------------------------------
// | API路由
// +----------------------------------------------------------------------
use think\facade\Route;

// 用户模块
Route::group('api/user', function () {
    Route::post('login', 'api/user/login');                                             //登录
    Route::post('loginWithOpenid', 'api/user/loginWithOpenid');                         //openid登录
    Route::post('update', 'api/user/update');                                           //更新用户信息
    Route::get('info', 'api/user/getUserInfo');                                         //获取用户信息
    Route::get('randomAvatar', 'api/user/getRandomAvatar');                             //获取随机头像
//    Route::get('getDefaultWallMaster', 'api/user/getDefaultWallMaster');                         //获取默认墙主
//    Route::post('setDefaultWallMaster', 'api/user/setDefaultWallMaster');                        //设置默认墙主
    Route::get('getAnonymousPostSetting', 'api/user/getAnonymousPostSetting');          //获取匿名投稿设置
    Route::get('getUserAgreement', 'api/user/getUserAgreement');                        //获取用户协议
    Route::get('getWallList', 'api/user/getWallList');                                  //获取墙列表
    Route::get('getCommunityList', 'api/user/getCommunityList');                        //获取社区列表

})->allowCrossDomain();

// 投稿模块
Route::group('api/post', function () {
    Route::post('create', 'api/post/create');                                           //创建投稿
    Route::get('list', 'api/post/list');                                                //获取投稿列表
    Route::get('detail', 'api/post/detail');                                            //获取投稿详情
    Route::post('review', 'api/post/review');                                           //审核投稿
    Route::post('batchReview', 'api/post/batchReview');                                 //批量审核投稿
    Route::post('moveToReview', 'api/post/moveToReview');                               //将投稿移动到审核队列
    Route::post('batchMoveToReview', 'api/post/batchMoveToReview');                     //批量将投稿移动到审核队列
    Route::post('delete', 'api/post/delete');                                           //删除投稿
    Route::post('update', 'api/post/update');                                           //更新投稿
    Route::post('generatePoster', 'api/post/generatePoster');                           //生成海报
    Route::get('getPendingPosts', 'api/post/getPendingPosts');                          //获取待审核投稿列表
    Route::get('getApprovedPosts', 'api/post/getApprovedPosts');                        //获取已审核投稿列表
    Route::get('getRejectedPosts', 'api/post/getRejectedPosts');                        //获取被拒绝投稿列表
    Route::get('getPostsStats', 'api/post/getPostsStats');                              //获取投稿统计数据
    Route::get('myPosts', 'api/post/myPosts');                                          //获取我的投稿列表
})->allowCrossDomain();

// 上传模块
Route::group('api/upload', function () {
    Route::get('sign', 'api/upload/sign');                                                  //获取上传签名
    Route::post('image', 'api/upload/image');                                               //上传图片
})->allowCrossDomain();

// 墙主管理模块
Route::group('api/wallowner', function () {
    Route::get('generateQRCode', 'api/wallowner/generateQRCode');                           //生成墙主二维码
    Route::post('generatewallownerLink', 'api/wallowner/generatewallownerLink');            //生成墙主Link外链
    Route::get('wallownerLinks', 'api/wallowner/wallownerLinks');                           //获取墙主Link外链列表
    Route::get('statistics', 'api/wallowner/statistics');                                   //获取墙主统计数据
    Route::get('wallSettings', 'api/wallowner/wallSettings');                               //获取墙主设置
    Route::post('updateWallSettings', 'api/wallowner/updateWallSettings');                  //更新墙主设置
    Route::post('applywallowner', 'api/wallowner/applywallowner');                          //申请墙主
    Route::get('getSettings', 'api/wallowner/getSettings');                                   //获取墙主设置
    Route::post('updateSettings', 'api/wallowner/updateSettings');                          //更新墙主设置
    Route::post('getSubscribeTemplates', 'api/wallowner/getSubscribeTemplates');            //获取订阅消息模板ID
    Route::post('generateCommentAreaLink', 'api/wallowner/generateCommentAreaLink');        //生成评论区Link外链
    Route::post('generateUrlLink', 'api/wallowner/generateUrlLink');                        //生成小程序URL Link外链
    Route::post('getReplyNoticeTemplate', 'api/wallowner/getReplyNoticeTemplate');          //获取回复提醒消息模板ID
    Route::post('getReviewResultTemplate', 'api/wallowner/getReviewResultTemplate');        //获取审核结果通知模板ID
    Route::get('getChannelList', 'api/wallowner/getChannelList');                           //获取渠道列表
    Route::get('saveChannelInfo', 'api/wallowner/saveChannelInfo');                         //保存渠道信息
    Route::get('preview', 'api/wallowner/getPreview');                                      //佣金预览
    Route::get('detail', 'api/wallowner/getAmountDetailList');                              //佣金明细
    Route::get('history', 'api/wallowner/getHistoryList');                                  //提现记录
    Route::post('application', 'api/wallowner/Application');                                //提现申请
    Route::get('withdrawal_channels', 'api/wallowner/getWithdrawalChannels');               //提现渠道
})->allowCrossDomain();

// 评论区模块
Route::group('api/comment', function () {
    Route::get('list', 'api/comment/getCommentList');                           //获取评论列表
    Route::post('add', 'api/comment/addComment');                               //添加评论
    Route::post('delete', 'api/comment/deleteComment');                         //删除评论
    Route::get('wallInfo', 'api/comment/getWallInfo');                          //获取评论区设置
    Route::get('initTable', 'api/comment/initTable');                           //初始化评论区数据库表
    Route::post('generateLink', 'api/comment/generateLink');                    // 生成匿名评论区短链
    Route::get('posts', 'api/comment/getPostsByPublishId');                     // 根据发布ID获取投稿列表
    Route::get('stats', 'api/comment/getPublishStats');                         // 获取发布统计信息
})->allowCrossDomain();

// 群聊管理模块
Route::group('api/groupchat', function () {
    Route::get('list', 'api/groupchat/getList');                             //获取群聊列表
    Route::post('create', 'api/groupchat/create');                           //创建群聊
    Route::post('update', 'api/groupchat/update');                           //更新群聊
    Route::post('delete', 'api/groupchat/delete');                           //删除群聊
    Route::get('detail', 'api/groupchat/detail');                            //获取群聊详情
    Route::post('batchUpdateWeight', 'api/groupchat/batchUpdateWeight');     //批量更新群聊权重
})->allowCrossDomain();

// 通知模块
Route::group('api/notification', function () {
    Route::get('list', 'api/notification/getList');                            //获取通知列表
    Route::get('detail/:id', 'api/notification/getDetail');                    //获取通知详情
    Route::get('latest', 'api/notification/getLatest');                        //获取最新通知
    Route::get('count', 'api/notification/getCount');                          //获取未读通知数量
})->allowCrossDomain();

// 使用教程
Route::group('api/tutorial', function () {
    Route::get('get', 'api/tutorial/get');                            //获取使用教程
})->allowCrossDomain();

// 支付模块
Route::group('api/payment', function () {
    Route::post('jssdkConfig', 'api/payment/getJssdkConfig');           //获取支付配置
    Route::post('saveUnify', 'api/payment/saveUnify');                  //支付到余额
})->allowCrossDomain();

<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\helper\UrlHelper;
use app\common\model\Agreement;
use app\common\model\Community;
use app\common\model\User as UserModel;
use app\common\model\WallSettings;
use app\common\model\WallToUser;
use app\common\service\WechatService;
use Exception;
use think\Db;
use think\facade\Log;
use think\Request;
use think\response\Json;

class User extends Api
{
    protected $noNeedLogin = ['login', 'loginwithopenid'];

    /**
     * 微信授权登录
     * @param Request $request
     * @return Json
     */
    public function login(Request $request)
    {
        $code = $request->param('code');
        if (empty($code)) {
            return $this->error('登录失败，缺少code参数');
        }

        // 微信登录获取openid
        $wxService = new WechatService();
        $wxResult = $wxService->getOpenidByCode($code);

        if (empty($wxResult['openid'])) {
            return $this->error('登录失败，获取openid失败');
        }

        // 查询用户是否已存在
        $userModel = new UserModel();
        $user = $userModel->getUserByOpenid($wxResult['openid']);

        if (!$user) {
            // 创建新用户
            $userId = $userModel->createUser([
                'openid' => $wxResult['openid'],
                'unionid' => $wxResult['unionid'],
                'is_wall_master' => 0,
            ]);

            if (!$userId) {
                return $this->error('用户创建失败');
            }

            $user = $userModel->getUserByOpenid($wxResult['openid']);
        } else {
            $user->unionid = $wxResult['unionid'];
            $user->save();
        }

        // 返回用户信息和openid
        $data = [
            'userId' => $user['user_id'],
            'openId' => $user['openid'],
            'unionId' => $user['unionid'],
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'isWallMaster' => $user['is_wall_master'],
            'wall_id' => $user['wall_id'],
        ];
        return $this->success('登录成功', '', $data);
    }


    /**
     * 微信openid登录
     * @param Request $request
     * @return Json
     */
    public function loginWithOpenid(Request $request)
    {
        $open_id = $request->param('open_id');
        if (empty($open_id)) {
            return $this->error('登录失败，缺少open_id参数');
        }

        // 查询用户是否已存在
        $userModel = new UserModel();
        $user = $userModel->getUserByOpenid($open_id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 返回用户信息和openid
        $data = [
            'userId' => $user['user_id'],
            'openId' => $user['openid'],
            'unionId' => $user['unionid'],
            'nickname' => $user['nickname'],
            'avatar' => $user['avatar'],
            'isWallMaster' => $user['is_wall_master'],
            'wall_id' => $user['wall_id'],
        ];
        return $this->success('登录成功', '', $data);
    }


    /**
     * 更新用户信息
     * @param Request $request
     * @return Json
     */
    public function update(Request $request)
    {
        $rule['nickname'] = 'require';
        $rule['avatar'] = 'require|url';
        $rule['phone'] = 'mobile';
        $rule['email'] = 'email';
        $rule['wall_id'] = 'number|>=:0';

        $this->validate($this->request->post(), $rule);

        $userId = $this->getUserId();
        if (empty($userId)) {
            return $this->error('用户未登录');
        }

        $userModel = new UserModel();
        $user = $userModel->where('user_id', $userId)->find();
        if (!$user) {
            return $this->error('用户不存在');
        }

        $data = $request->post();

        $user->nickname = $data['nickname'];
        $user->avatar = $data['avatar'];
        $user->phone = $data['phone'] ?? '';
        $user->email = $data['email'] ?? '';
        $user->school = $data['school'] ?? '';
        $user->college = $data['college'] ?? '';
        $user->major = $data['major'] ?? '';
        $user->classes = $data['classes'] ?? '';
        $user->wall_id = $data['wall_id'] ?? 0;
        $user->wechat_id = $data['wechat_id'] ?? '';

        Db::startTrans();
        try {
            $user->save();

            if (isset($data['wall_id']) && !empty($data['wall_id'])) {
                WallToUser::where('user_id', $userId)->delete();
                WallToUser::create([
                    'wall_id' => $data['wall_id'],
                    'user_id' => $userId,
                ]);
            }

            Db::commit();
            return json(['code' => 200, 'message' => '审核成功']);

        } catch (Exception $e) {
            Db::rollback();
            return json(['code' => 500, 'message' => '审核失败: ' . $e->getMessage()]);
        }


        return $this->success('更新成功', '');
    }

    /**
     * 获取用户信息
     * @return Json
     */
    public function getUserInfo()
    {
        $userId = $this->getUserId();

        $userModel = new UserModel();
        $user = $userModel
            ->where('user_id', $userId)
            ->with(['wall' => function ($query) {
                $query->field('id,community_id,wall_name');
            }])
            ->find();

        if (!$user) {
            return $this->error('用户不存在');
        }

        $data = [
            'userId' => $user->user_id,
            'openId' => $user->openid,
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'isWallMaster' => $user->is_wall_master,
            'isVip' => $user->is_vip,
            'phone' => $user->phone,
            'email' => $user->email,
            'wechat_id' => $user->wechat_id,
            'school' => $user->school,
            'college' => $user->college,
            'major' => $user->major,
            'classes' => $user->classes,
            'community_id' => $user->wall ? $user->wall->community_id : 0,
            'wall_id' => $user->wall_id,
            'wall_name' => $user->wall ? $user->wall->wall_name : '',

        ];
        return $this->success('获取成功', '', $data);
    }

    /**
     * 获取随机头像
     * @return Json
     */
    public function getRandomAvatar()
    {
        try {
            // 头像目录路径 - 项目根目录下的public/avatar
            $avatarDir = dirname(dirname(dirname(__DIR__))) . '/public/avatar';

            // 检查目录是否存在
            if (!is_dir($avatarDir)) {
                return $this->error('头像目录不存在: ' . $avatarDir);
            }

            // 获取目录中的所有图片文件
            $files = scandir($avatarDir);
            $imageFiles = [];

            // 支持的图片格式
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $filePath = $avatarDir . '/' . $file;
                if (is_file($filePath)) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    if (in_array($extension, $allowedExtensions)) {
                        $imageFiles[] = $file;
                    }
                }
            }

            // 检查是否有可用的图片
            if (empty($imageFiles)) {
                return $this->error('没有可用的头像图片');
            }

            // 随机选择一张图片
            $randomIndex = array_rand($imageFiles);
            $randomImage = $imageFiles[$randomIndex];

            // 构建完整的URL路径
            $avatarUrl = UrlHelper::generateAvatarUrl($randomImage);

            return $this->success('获取随机头像成功', '', [
                'avatar_url' => $avatarUrl,
                'filename' => $randomImage
            ]);

        } catch (Exception $e) {
            Log::error('获取随机头像失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('获取随机头像失败: ' . $e->getMessage());
        }
    }

//    /**
//     * 获取默认墙主信息
//     * @param Request $request
//     * @return Json
//     */
//    public function getDefaultWallMaster(Request $request)
//    {
//        $WallSettings = new WallSettings();
//
//        if ($this->userInfo['wall_id']) {
//            $wall_info = $WallSettings->getWallInfo($this->userInfo['wall_id']);
//            return $this->success('获取默认墙主成功', null, $wall_info);
//
//        }
//
//        return $this->error('未绑定墙');
//    }

    /**
     * 设置默认墙主（管理员接口）
     * @param Request $request
     * @return Json
     */
//    public function setDefaultWallMaster(Request $request)
//    {
//        $applyId = $request->param('apply_id');
//        $userId = $request->param('user_id'); // 兼容旧接口
//
//        if (empty($applyId) && empty($userId)) {
//            return $this->error('申请ID或用户ID不能为空');
//        }
//
//        try {
//            $applyModel = new WallMasterApply();
//
//            // 如果传入的是用户ID，需要找到对应的申请ID
//            if (!empty($userId) && empty($applyId)) {
//                $apply = $applyModel->where('user_id', $userId)
//                    ->where('status', 1)
//                    ->find();
//                if (!$apply) {
//                    return $this->error('该用户没有通过审核的墙主申请');
//                }
//                $applyId = $apply['id'];
//            }
//
//            // 检查申请是否存在且已通过审核
//            $apply = $applyModel->where('id', $applyId)
//                ->where('status', 1)
//                ->with('user')
//                ->find();
//
//            if (!$apply) {
//                return $this->error('申请不存在或未通过审核');
//            }
//
//            // 设置为默认墙主
//            $result = $applyModel->setDefaultWallMasterApply($applyId);
//
//            if ($result) {
//                return $this->success('设置默认墙主成功', null, [
//                    'apply_id' => $apply['id'],
//                    'user_id' => $apply['user_id'],
//                    'nickname' => $apply['user']['nickname'],
//                    'is_default_wall_master' => 1
//                ]);
//            } else {
//                return $this->error('设置默认墙主失败');
//            }
//
//        } catch (Exception $e) {
//            Log::error('设置默认墙主失败', [
//                'apply_id' => $applyId ?? '',
//                'user_id' => $userId ?? '',
//                'error' => $e->getMessage(),
//                'trace' => $e->getTraceAsString()
//            ]);
//            return $this->error('设置默认墙主失败: ' . $e->getMessage());
//        }
//    }

    /**
     * 获取是否显示匿名发布设置
     * @return Json
     */
    public function getAnonymousPostSetting()
    {
        try {
            // 硬编码返回值，表示是否显示匿名发布选项
            $data = [
                'show_anonymous_post' => true, // true表示显示匿名发布选项，false表示不显示
                'anonymous_enabled' => true,   // 是否启用匿名发布功能
                'description' => '匿名发布功能已启用' // 功能描述
            ];

            return $this->success('获取匿名发布设置成功', '', $data);

        } catch (Exception $e) {
            Log::error('获取匿名发布设置失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('获取匿名发布设置失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取用户协议
     * @return Json
     */
    public function getUserAgreement()
    {
        $id = $this->userInfo['isWallMaster'] ? 2 : 1;

        $info = Agreement::find($id);
        if (!$info) {
            return $this->error('协议不存在');
        }

        return $this->success('获取协议成功', null, ['content' => $info['content']]);
    }


    /**
     * 获取墙列表
     * @return Json
     */
    public function getWallList(Request $request)
    {
        $rule['community_id'] = 'require|integer|>=:1';

        $this->validate($request->param(), $rule);

        $keyword = $request->param('keyword', '');
        $community_id = $request->param('community_id', '');

        $where[] = ['cw_wall_settings.status', '=', 1];
        $where[] = ['cw_wall_settings.community_id', '=', $community_id];
        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['wall_name', 'like', '%' . $keyword . '%'];
        }

        $wall_list = WallSettings::where($where)
            ->field('id,wall_name')
            ->withJoin(['wallOwner'])
            ->select();

        return $this->success('获取成功', null, ['list' => $wall_list]);
    }

    /**
     * 获取社区列表
     * @return Json
     */
    public function getCommunityList(Request $request)
    {
        $keyword = $request->param('keyword', '');
        $where[] = ['status', '=', 1];
        // 关键词搜索
        if (!empty($keyword)) {
            $where[] = ['community_name|alias', 'like', "%{$keyword}%"];
        }

        $community_list = Community::where($where)
            ->field('community_id,community_name')
            ->select();

        return $this->success('获取成功', null, ['list' => $community_list,]);
    }
}

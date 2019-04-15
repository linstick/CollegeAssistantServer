<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/14/014
 * Time: 16:49
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\model\CollegeInfo;
use app\index\model\User;
use app\index\response\Response;
use app\index\response\UserResponseBean;
use think\Db;
use think\Request;

class Users
{
    public function index() {

    }

    public function pull() {

    }

    /**
     * 用户登录
     * @return Response 登录成功，返回用户信息
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login() {
        $request = Request::instance();
        $account = $request->get(Config::PARAM_KEY_ACCOUNT);
        $password = $request->get(Config::PARAM_KEY_PASSWORD);
        if ($account == null || $password == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_CELL_NUMBER.'|'.User::COLUMN_EMAIL;
        $user = Db::table(User::TABLE_NAME)
            ->where($condition, $account)
            ->where(User::COLUMN_PASSWORD, $password)
            ->find();
        if ($user == null) {
            $response = new Response();
            $response->code = Config:: CODE_ERROR;
            $response->status = Config:: STATUS_LOGIN_FAIL;
            return $response;
        }
        $data = self::getUserInfo($user, true);
        return Response::newSuccessInstance($data);
    }

    /**
     * 获取某个用户的信息
     * @return Response 用户信息
     */
    public function fetchDetail() {
        $request = Request::instance();
        $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
        $user = self::getUserInfo($other_uid, false);
        if ($user == null) {
            return Response::newNoDataInstance();
        }
        return Response::newSuccessInstance($user);
    }

    /**
     * 登录时根据用户账号获取用户头像信息(ID/手机号/邮箱)
     * @return Response 用户头像资源
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function fetchAvatar() {
        $request = Request::instance();
        $account = $request->get(Config::PARAM_KEY_ACCOUNT);
        if ($account == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_EMAIL.'|'.User::COLUMN_EMAIL;
        $user = User::where($condition, $account)->find();
        if ($user == null) {
            return Response::newNoDataInstance();
        }
        return Response::newSuccessInstance($user->avatar);
    }

    /**
     * 注册检查用户的账号是否已经存在(ID/手机号/邮箱)
     * @return Response 账号是否存在
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkAccount() {
        $request = Request::instance();
        $account = $request->get(Config::PARAM_KEY_ACCOUNT);
        if ($account == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_EMAIL.'|'.User::COLUMN_EMAIL;
        $user = User::where($condition, $account)->find();
        if ($user == null) {
            return Response::newSuccessInstance(null);
        }
        return Response::newAccountExistsInstance();
    }

    /**
     * 修改密码
     * @return Response
     * @throws \think\exception\DbException
     */
    public function modifyPassword() {
        $request = Request::instance();
        $uid = $request->get(Config::PARAM_KEY_UID);
        $password = $request->get(Config::PARAM_KEY_PASSWORD);
        $new_password = $request->get(Config::PARAM_KEY_NEW_PASSWORD);
        if ($uid == null || $password == null || $new_password == null) {
            return Response::newIllegalInstance();
        }
        $user = User::get($uid);
        if ($user == null) {
            return Response::newIllegalInstance();
        }
        if ($user->password != $password) {
            $response = new Response();
            $response->code = Config::CODE_ERROR;
            $response->status = Config::STATUS_PASSWORD_NOT_MATCH;
            return $response;
        }
        $user->password = $new_password;
        $user->save();
        return Response::newSuccessInstance(null);
    }

    public function signUp() {
        $request = Request::instance();
        $account = $request->get(Config::PARAM_KEY_ACCOUNT);
        $nickname = $request->get(Config::PARAM_KEY_NICKNAME);
        $password = $request->get(Config::PARAM_KEY_PASSWORD);
        if ($account == null || $nickname == null || $password == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_EMAIL.'|'.User::COLUMN_EMAIL;
        $user = User::where($condition, $account)->find();
        if ($user != null) {
            return Response::newAccountExistsInstance();
        }
        $user = new User();
        $user->id = $account;
        $user->nickname = $nickname;
        $user->password = $password;
        $user->save();
        return Response::newSuccessInstance(self::getUserInfo($user->uid, true));
    }

    public function signOut() {
        $request = Request::instance();
        $uid = $request->get(Config::PARAM_KEY_UID);
        if ($uid == null) {
            return Response::newIllegalInstance();
        }
        $user = User::get($uid);
        if ($user == null) {
            return Response::newSignOutFailInstance();
        }
        $user->delete();
        return Response::newSuccessInstance(null);
    }

    private function getUserInfo($uid, $is_login) {
        if ($uid != null && $uid > 0) {
            $user =  User::get($uid);
            if ($user != null) {
                return self::buildUserData($user, $is_login);
            }
        }
        return null;
    }

    private static function buildUserData($obj, $is_login) {
        $result = new UserResponseBean();
        $result->id = $obj->id;
        $result->uid = $obj->uid;
        $result->avatar = $obj->avatar;
        $result->nickname = $obj->nickname;
        $result->age = $obj->age;
        $result->gender = $obj->gender;
        $result->email = $obj->email;
        $result->description = $obj->description;
        if ($is_login) {
            $result->signTime = $obj->sign_time;
            $result->cellNumber = $obj->cell_number;
        }
        $result->collegeInfo = Db::table(CollegeInfo::TABLE_NAME)
            ->where(CollegeInfo::COLUMN_UID, $obj->uid)
            ->limit(1)
            ->find();
        return $result;
    }
}
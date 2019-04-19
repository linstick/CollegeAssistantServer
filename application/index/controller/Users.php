<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/14/014
 * Time: 16:49
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\model\Activity;
use app\index\model\ActivityCollectRelation;
use app\index\model\CollegeInfo;
use app\index\model\Discover;
use app\index\model\Topic;
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
        $request = Request::instance();
        $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $offset = $request->get(Config::PARAM_KEY_OFFSET);
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        if ($page_id != Config::PAGE_ID_USER_SEARCH || $keyword == null) {
            return Response::newIllegalInstance();
        }
        $offset == max($offset, 0);
        $users = self::search($keyword, $offset, $request_count);
        if (empty($users)) {
            return Response::newNoSearchResult();
        }
        return Response::newSuccessInstance($users);
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
        $account = $request->post(Config::PARAM_KEY_ACCOUNT);
        $password = $request->post(Config::PARAM_KEY_PASSWORD);
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
        if ($other_uid == null) {
            return Response::newIllegalInstance();
        }
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
    public function checkAccountExist() {
        $request = Request::instance();
        $account = $request->get(Config::PARAM_KEY_ACCOUNT);
        if ($account == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_EMAIL.'|'.User::COLUMN_EMAIL;
        $user = User::where($condition, $account)->find();
        return Response::newSuccessInstance($user == null ? false : true);
    }

    /**
     * 修改密码
     * @return Response
     * @throws \think\exception\DbException
     */
    public function modifyPassword() {
        $request = Request::instance();
        $uid = $request->post(Config::PARAM_KEY_UID);
        $password = $request->post(Config::PARAM_KEY_PASSWORD);
        $new_password = $request->post(Config::PARAM_KEY_NEW_PASSWORD);
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

    /**
     * 用户注册
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function signUp() {
        $request = Request::instance();
        $account = $request->post(Config::PARAM_KEY_ACCOUNT);
        $nickname = $request->post(Config::PARAM_KEY_NICKNAME);
        $password = $request->post(Config::PARAM_KEY_PASSWORD);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($account == null || $nickname == null || $password == null) {
            return Response::newIllegalInstance();
        }
        $condition = User::COLUMN_ID.'|'.User::COLUMN_EMAIL.'|'.User::COLUMN_EMAIL;
        $user = User::where($condition, $account)->find();
        if ($user != null) {
            return Response::newAccountExistsInstance();
        }
        $avatar = null;
        if ($files) {
            // 存在头像
            $file = $files[0];
            $avatar = Upload::uploadImage($file);
            if ($avatar == null) {
                $response = new Response();
                $response->code = Config::CODE_ERROR;
                $response->status = Config::STATUS_SIGN_UP_FAIL;
                return $response;
            }
        }
        $user = new User();
        $user->id = $account;
        $user->avatar = $avatar;
        $user->nickname = $nickname;
        $user->password = $password;
        $user->save();
        return Response::newSuccessInstance(self::getUserInfo($user->uid, true));
    }

    /**
     * 用户注销
     * @return Response
     * @throws \think\exception\DbException
     */
    public function signOut() {
        $request = Request::instance();
        $uid = $request->post(Config::PARAM_KEY_UID);
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

    public function modifyProfile() {
        $request = Request::instance();
        $user_json = $request->post(Config::PARAM_KEY_USER);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($user_json == null) {
            return Response::newIllegalInstance();
        }
        $avatar = null;
        if ($files) {
            $file = $files[0];
            $avatar = Upload::uploadImage($file);
            if ($avatar == null) {
                $response = new Response();
                $response->code = Config::CODE_ERROR;
                $response->status = Config::STATUS_MODIFY_PROFILE_FAIL;
                return $response;
            }
        }
        $user = json_decode($user_json);
        $origin_user = User::get($user->uid);
        $origin_user->nickname = $user->nickname;
        $origin_user->age = $user->age;
        $origin_user->gender = $user->gender;
        $origin_user->description = $user->description;
        if ($avatar != null) {
            $origin_user->avatar = $avatar;
        }
        $college_info = $user->collegeInfo;
        $original_college_info = CollegeInfo::get($user->uid);
        if ($original_college_info == null) {
            $original_college_info = new CollegeInfo();
        }
        $original_college_info->name = $college_info->name;
        $original_college_info->department = $college_info->department;
        $original_college_info->major = $college_info->major;
        $original_college_info->klass = $college_info->klass;
        $origin_user->save();
        $original_college_info->save();
        $data = self::getUserInfo($origin_user->uid, true);
        return Response::newSuccessInstance($data);
    }

    public static function search($keyword, $offset, $request_count) {
        $field = User::COLUMN_ID.'|'.User::COLUMN_NICKNAME;
        $condition = "%$keyword%";
        $users = User::where($field, Config::WORD_LIKE, $condition)
            ->field(User::COLUMN_ID.','.User::COLUMN_UID.','.User::COLUMN_NICKNAME.','.User::COLUMN_DESCRIPTION.','.User::COLUMN_AVATAR)
            ->limit($offset, $request_count)
            ->order(User::COLUMN_UID, Config::WORD_ASC)
            ->select()
            ->toArray();
        return $users;
    }

    public static function searchHot($request_count) {
        $hot_users = Activity::field('publisher_uid as uid, count(publisher_uid) as activityCount')
            ->group(Activity::COLUMN_PUBLISHER_UID)
            ->order('activityCount', Config::WORD_DESC)
            ->limit($request_count)
            ->select();
        foreach ($hot_users as $user) {
            $temp = User::get($user['uid']);
            $user['nickname'] = $temp->nickname;
            $user['avatar'] = $temp->avatar;
        }
        return $hot_users;
    }

    public static function searchSimple($keyword, $request_count) {
        $field = User::COLUMN_NICKNAME;
        $condition = "%$keyword%";
        $users = User::where($field,Config::WORD_LIKE,  $condition)
            ->order(User::COLUMN_UID, Config::WORD_DESC)
            ->field(User::COLUMN_UID.','.User::COLUMN_NICKNAME.','.User::COLUMN_AVATAR)
            ->limit($request_count)
            ->select();
        return $users;
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
            $result->collectCount = self::getCollectCount($obj->uid);
        }
        $result->activityCount = self::getActivityCount($obj->uid);
        $result->topicCount = self::getTopicCount($obj->uid);
        $result->discoverCount = self::getDiscoverCount($obj->uid);
        $result->collegeInfo = self::getCollegeInfo($obj->uid);
        return $result;
    }

    private static function getCollectCount($uid) {
        return ActivityCollectRelation::where(ActivityCollectRelation::COLUMN_COLLECTOR_UID, $uid)->count('*');
    }

    private static function getActivityCount($uid) {
        return Activity::where(Activity::COLUMN_PUBLISHER_UID, $uid)->count('*');
    }

    private static function getTopicCount($uid) {
        return Topic::where(Topic::COLUMN_PUBLISHER_UID, $uid)->count('*');
    }

    private static function getDiscoverCount($uid) {
        return Discover::where(Discover::COLUMN_PUBLISHER_UID, $uid)->count('*');
    }

    private static function getCollegeInfo($uid) {
        return Db::table(CollegeInfo::TABLE_NAME)
            ->where(CollegeInfo::COLUMN_UID, $uid)
            ->limit(1)
            ->find();
    }
}
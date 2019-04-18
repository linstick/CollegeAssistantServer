<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/13/013
 * Time: 11:15
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\model\Activity;
use app\index\model\ActivityAddition;
use app\index\model\ActivityCollectRelation;
use app\index\model\ActivityComment;
use app\index\model\ActivityPictureRelation;
use app\index\model\CollegeInfo;
use app\index\model\Discover;
use app\index\model\DiscoverComment;
use app\index\model\DiscoverLikeRelation;
use app\index\model\DiscoverPictureRelation;
use app\index\model\Message;
use app\index\model\Topic;
use app\index\model\TopicJoinRelation;
use app\index\model\User;
use app\index\response\ActivityResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\MessageResponseBean;
use app\index\response\Response;
use think\Db;
use think\Paginator;
use think\Request;

class Messages
{
    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $pull_type = $request->get(Config::PARAM_KEY_PULL_TYPE);
        $time_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $messages = null;
        if ($page_id == Config::PAGE_ID_MESSAGE) {
            $messages = Db::table(Message::TABLE_NAME)->where(Message::COLUMN_RECEIVER_UID, $uid)
                ->where(Message::COLUMN_CREATE_TIME, $time_opt, $time_stamp)
                ->order(Message::COLUMN_CREATE_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        }
        if ($messages == null) {
            return Response::newIllegalInstance();
        }
        if ($messages->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($time_stamp, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = self::buildActivityListData($messages);
        return Response::newSuccessInstance($data);
    }

    private static function buildActivityListData($messages) {
        // 组装数据返回
        $result = array();
        foreach ($messages as $key => $message) {
            $temp = new MessageResponseBean();
            $temp->content = $message['content'];
            $temp->type = $message['type'];
            $temp->publishTime = $message['create_time'];
            $temp->targetId = $message['target_id'];
            $temp->targetCoverUrl = $message['target_cover'];
            $temp->targetTitle = $message['target_title'];
            $temp->targetContent = $message['target_content'];
            $result[$key] = $temp;
            // 获取用户的基本数据
            $user = User::get($message['creator_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

}
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
use app\index\response\CommentResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\MessageResponseBean;
use app\index\response\Response;
use think\Db;
use think\Paginator;
use think\Request;

class Comments
{
    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $pull_type = $request->get(Config::PARAM_KEY_PULL_TYPE);
        $time_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $comments = null;
        if ($page_id == Config::PAGE_ID_ACTIVITY_COMMENT) {
            $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
            $comments = Db::table(ActivityComment::TABLE_NAME)
                ->where(ActivityComment::COLUMN_ACTIVITY_ID, $activity_id)
                ->where(ActivityComment::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                ->order(ActivityComment::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        } else if ($page_id == Config::PAGE_ID_DISCOVER_COMMENT) {
            $discover_id = $request->get(Config::PARAM_KEY_DISCOVER_ID);
            $comments = Db::table(DiscoverComment::TABLE_NAME)
                ->where(DiscoverComment::COLUMN_DISCOVER_ID, $discover_id)
                ->where(DiscoverComment::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                ->order(DiscoverComment::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        }
        if ($comments == null) {
            return Response::newIllegalInstance();;
        }
        if ($comments->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($time_stamp, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = self::buildCommentListData($comments);
        return Response::newSuccessInstance($data);
    }

    private static function buildCommentListData($comments) {
        // 组装数据返回
        $result = array();
        foreach ($comments as $key => $comment) {
            $temp = new CommentResponseBean();
            $temp->id = $comment['id'];
            $temp->content = $comment['content'];
            $temp->publishTime = $comment['publish_time'];
            $result[$key] = $temp;
            // 获取用户的基本数据
            $user = User::get($comment['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

}
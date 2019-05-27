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
        $compare_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $first_or_last_id = $request->get(Config::PARAM_KEY_COMMENT_ID);
        $comments = null;
        if ($page_id == Config::PAGE_ID_ACTIVITY_COMMENT) {
            $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
            $comments = Db::table(ActivityComment::TABLE_NAME)
                ->where(ActivityComment::COLUMN_ACTIVITY_ID, $activity_id)
                ->where(ActivityComment::COLUMN_ID, $compare_opt, $first_or_last_id)
                ->order(ActivityComment::COLUMN_ID, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        } else if ($page_id == Config::PAGE_ID_DISCOVER_COMMENT) {
            $discover_id = $request->get(Config::PARAM_KEY_DISCOVER_ID);
            $comments = Db::table(DiscoverComment::TABLE_NAME)
                ->where(DiscoverComment::COLUMN_DISCOVER_ID, $discover_id)
                ->where(DiscoverComment::COLUMN_ID, $compare_opt, $first_or_last_id)
                ->order(DiscoverComment::COLUMN_ID, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        }
        if ($comments == null) {
            return Response::newIllegalInstance();;
        }
        if ($comments->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($first_or_last_id, Config::DEFAULT_TIME_STAMP) == 0) {
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

    /**
     * 添加评论（活动/动态）
     */
    public function add() {
        $request = Request::instance();
        $uid = $request->post(Config::PARAM_KEY_UID);
        $comment_type = $request->post(Config::PARAM_KEY_COMMENT_TYPE);
        $target_id = $comment_type == Config::COMMENT_TYPE_ACTIVITY
            ? $request->post(Config::PARAM_KEY_ACTIVITY_ID)
            : $request->post(Config::PARAM_KEY_DISCOVER_ID);
        $text = $request->post(Config::PARAM_KEY_COMMENT);
        if ($uid == null || $target_id == null || $text == null || $comment_type == null) {
            return Response::newIllegalInstance();
        }
        $comment = null;
        switch ($comment_type) {
            case Config::COMMENT_TYPE_ACTIVITY:
                $comment = new ActivityComment();
                $comment->content = $text;
                $comment->publisher_uid = $uid;
                $comment->activity_id = $target_id;
                $comment->save();
                $comment = ActivityComment::get($comment->id);

                $message = Message::get([
                    Message::COLUMN_TYPE => Config::MESSAGE_TYPE_ACTIVITY_COMMENT,
                    Message::COLUMN_TARGET_ID => $target_id,
                    Message::COLUMN_CREATOR_UID => $uid
                ]);
                if ($message == null) {
                    // 生成评论消息
                    $discover = Activity::get($target_id);
                    $pictureRelation = ActivityPictureRelation::get([
                        ActivityPictureRelation::COLUMN_ACTIVITY_ID => $target_id,
                        ActivityPictureRelation::COLUMN_ORDER_NUMBER => 0,
                    ]);
                    $message = new Message();
                    $message->type = Config::MESSAGE_TYPE_ACTIVITY_COMMENT;
                    $message->content = $text;
                    $message->target_id = $discover->id;
                    $message->target_title = $discover->title;
                    $message->target_content = $discover->content;
                    $message->receiver_uid = $discover->publisher_uid;
                    $message->creator_uid = $uid;
                    if ($pictureRelation != null) {
                        $message->target_cover = $pictureRelation->getData(ActivityPictureRelation::COLUMN_URL);
                    }
                    $message->save();
                }
                break;
            case Config::COMMENT_TYPE_DISCOVER:
                $comment = new DiscoverComment();
                $comment->content = $text;
                $comment->publisher_uid = $uid;
                $comment->discover_id = $target_id;
                $comment->save();
                $comment = DiscoverComment::get($comment->id);

                $message = Message::get([
                    Message::COLUMN_TYPE => Config::MESSAGE_TYPE_DISCOVER_COMMENT,
                    Message::COLUMN_TARGET_ID => $target_id,
                    Message::COLUMN_CREATOR_UID => $uid
                ]);
                if ($message == null) {
                    // 生成评论消息
                    $discover = Discover::get($target_id);
                    $pictureRelation = DiscoverPictureRelation::get([
                        DiscoverPictureRelation::COLUMN_DISCOVER_ID => $target_id,
                        DiscoverPictureRelation::COLUMN_ORDER_NUMBER => 0,
                    ]);
                    $message = new Message();
                    $message->type = Config::MESSAGE_TYPE_DISCOVER_COMMENT;
                    $message->content = $text;
                    $message->target_id = $discover->id;
                    $message->target_content = $discover->content;
                    $message->receiver_uid = $discover->publisher_uid;
                    $message->creator_uid = $uid;
                    if ($pictureRelation != null) {
                        $message->target_cover = $pictureRelation->getData(DiscoverPictureRelation::COLUMN_URL);
                    }
                    $message->save();
                }
                break;
            default:
                break;
        }
        if ($comment == null) {
            return Response::newIllegalInstance();
        }
        $data = self::buildSingleCommentData($comment);
        return Response::newSuccessInstance($data);
    }

    /**
     * 删除评论（活动/动态）
     */
    public function delete() {
        $request = Request::instance();
        $comment_id = $request->get(Config::PARAM_KEY_COMMENT_ID);
        $comment_type = $request->get(Config::PARAM_KEY_COMMENT_TYPE);
        if ($comment_id == null || $comment_type == null) {
            return Response::newIllegalInstance();
        }
        $comment = null;
        switch ($comment_type) {
            case Config::COMMENT_TYPE_ACTIVITY:
                $comment = ActivityComment::get($comment_id);
                if ($comment == null) {
                    return Response::newIllegalInstance();
                }
                $comment->delete();
                break;
            case Config::COMMENT_TYPE_DISCOVER:
                $comment = DiscoverComment::get($comment_id);
                if ($comment == null) {
                    return Response::newIllegalInstance();
                }
                $comment->delete();
                break;
            default:
                break;
        }
        return Response::newSuccessInstance($comment);
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

    private static function buildSingleCommentData($comment) {
        $temp = new CommentResponseBean();
        $temp->id = $comment->id;
        $temp->content = $comment->content;
        $temp->publishTime = $comment->publish_time;
        // 获取用户的基本数据
        $user = User::get($comment->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        return $temp;
    }

}
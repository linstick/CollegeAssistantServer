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
use app\index\model\Topic;
use app\index\model\TopicJoinRelation;
use app\index\model\User;
use app\index\response\ActivityResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\Response;
use think\Db;
use think\Paginator;
use think\Request;

class Discovers
{
    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $time_opt = $request->get(Config::PARAM_KEY_PULL_TYPE) == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $discover = null;
        switch ($page_id) {
            case Config::PAGE_ID_DISCOVER_ALL:
                // 全部活动查询
                $discover = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_SELF:
                // 用户自己的活动查询
                $discover = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISHER_UID, $uid)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_OTHER_USER:
                // 其他用户的活动查询
                $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
                $discover = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISHER_UID, $other_uid)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_SEARCH:
                // 搜索动态查询
                $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
                $field = Discover::COLUMN_CONTENT.'|'.Discover::COLUMN_LOCATION;
                $condition = '%'.$keyword.'%';
                $discover = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->where($field,Config::WORD_LIKE,  $condition)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_TOPIC:
                // 参与话题的动态数据查询
                $topic_id = $request->get(Config::PARAM_KEY_TOPIC_ID);
                $view_join_condition = TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_DISCOVER_ID.'='.Discover::TABLE_NAME.'.'.Discover::COLUMN_ID;
                $discover = Db::view(TopicJoinRelation::TABLE_NAME, TopicJoinRelation::COLUMN_DISCOVER_ID)
                    ->view(Discover::TABLE_NAME, '*', $view_join_condition)
                    ->where(TopicJoinRelation::COLUMN_TOPIC_ID, $topic_id)
                    ->where(Discover::TABLE_NAME.'.'.Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            default:
                break;
        }
        if ($discover == null) {
            return Response::newIllegalInstance();
        }
        if ($discover->isEmpty()){
            return Response::newEmptyInstance();
        }
        $data = self::buildTopicListData($discover, $uid);
        return Response::newSuccessInstance($data);
    }

    // 单个活动数据获取
    public function fetchDetail() {
        $request = Request::instance();
        $activity_id = $request->get(Config::PARAM_KEY_DISCOVER_ID);
        $curUid = $request->get(Config::PARAM_KEY_UID);
        $discover = Discover::get($activity_id);
        if ($discover == null) {
            $response = new Response();
            $response->code = Config::CODE_NO_DATA;
            $response->status = Config::STATUS_NO_DATA;
            return $response;
        }
        $temp = new DiscoverResponseBean();
        $temp->id = $discover->id;
        $temp->content = $discover->content;
        $temp->location = $discover->location;
        $temp->publishTime = $discover->publish_time;
        $temp->likeCount = self::getLikeCount($discover->id);
        $temp->commentCount = self::getCommentCount($discover->id);
        $topic_name = self::getRelatedTopicName($discover->related_topic_id);
        if ($topic_name != null) {
            $temp->topicId = $discover->related_topic_id;
            $temp->topic = $topic_name;
        } else {
            $temp->topicId = -1;
        }
        $temp->pictureList = Discovers::getPictureList($discover->id);
        $temp->hasLike = self::hasLiked($discover->id, $curUid);
        // 获取用户的基本数据
        $user = User::get($discover->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        $temp->college = self::getCollegeName($discover['publisher_uid']);
        return Response::newSuccessInstance($temp);
    }

    private static function buildTopicListData($discover, $curUid) {
        // 组装数据返回
        $result = array();
        foreach ($discover as $key => $discover) {
            $temp = new DiscoverResponseBean();
            $temp->id = $discover['id'];
            $temp->content = $discover['content'];
            $temp->location = $discover['location'];
            $temp->publishTime = $discover['publish_time'];
            $result[$key] = $temp;
            // 获取收藏数量
            $temp->likeCount = self::getLikeCount($discover['id']);
            // 获取评论数量
            $temp->commentCount = self::getCommentCount($discover['id']);
            // 获取话题名称
            $topic_name = self::getRelatedTopicName($discover['related_topic_id']);
            if ($topic_name != null) {
                $temp->topicId = $discover['related_topic_id'];
                $temp->topic = $topic_name;
            } else {
                $temp->topicId = -1;
            }
            // 获取图片资源
            $temp->pictureList = self::getPictureList($discover['id']);
            // 获取请求用户是否参与收藏
            $temp->hasLike = self::hasLiked($discover['id'], $curUid);

            // 获取用户的基本数据
            $user = User::get($discover['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
            $temp->college = self::getCollegeName($discover['publisher_uid']);
        }
        return $result;
    }

    private static function getLikeCount($discover_id) {
        return Db::table(DiscoverLikeRelation::TABLE_NAME)
            ->where(DiscoverLikeRelation::COLUMN_DISCOVER_ID, $discover_id)
            ->count('*');
    }

    private static function getCommentCount($discover_id) {
        return Db::table(DiscoverComment::TABLE_NAME)
            ->where(DiscoverComment::COLUMN_DISCOVER_ID, $discover_id)
            ->count('*');
    }

    private static function hasLiked($discover_id, $curUid) {
        $hasCollected = false;
        if ($curUid != -1) {
            $relation = Db::table(DiscoverLikeRelation::TABLE_NAME)
                ->where(DiscoverLikeRelation::COLUMN_DISCOVER_ID, $discover_id)
                ->where(DiscoverLikeRelation::COLUMN_LIKER_UID, $curUid)
                ->limit(1)
                ->find();
            $hasCollected = ($relation != null);
        }
        return $hasCollected;
    }

    private static function getRelatedTopicName($related_topic_id) {
        $topic_name = null;
        if ($related_topic_id != null) {
            $topic = Topic::get($related_topic_id);
            $topic_name = $topic != null ? $topic->name : null;
        }
        return $topic_name;
    }

    private static function getPictureList($discover_id) {
        $result = array();
        $relation = DiscoverPictureRelation::where(DiscoverPictureRelation::COLUMN_DISCOVER_ID, $discover_id)->select();
        foreach ($relation as $key => $value) {
            $result[$key] = $value->url;
        }
        return $result;
    }

    private static function getCollegeName($uid) {
        $college = Db::table(CollegeInfo::TABLE_NAME)
            ->where(CollegeInfo::COLUMN_UID, $uid)
            ->limit(1)
            ->column(CollegeInfo::COLUMN_NAME);
        if (count($college) > 0) {
            return $college[0];
        }
        return null;
    }
}
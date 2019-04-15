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
use app\index\model\Topic;
use app\index\model\User;
use app\index\response\ActivityResponseBean;
use app\index\response\Response;
use think\Db;
use think\Request;

class Activities
{
    public function index() {
        return Activity::get(101001);
    }

    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $time_opt = $request->get(Config::PARAM_KEY_PULL_TYPE) == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $activities = null;
        if ($page_id == Config::PAGE_ID_ACTIVITY_SELF_COLLECT) {
            // 活动收藏数据查询
            $view_join_condition = ActivityCollectRelation::TABLE_NAME.'.'.ActivityCollectRelation::COLUMN_ACTIVITY_ID.'='.Activity::TABLE_NAME.'.'.Activity::COLUMN_ID;
            $activities = Db::view(ActivityCollectRelation::TABLE_NAME, ActivityCollectRelation::COLUMN_COLLECTOR_UID)
                ->view(Activity::TABLE_NAME, '*', $view_join_condition)
                ->where(ActivityCollectRelation::COLUMN_COLLECTOR_UID, $uid)
                ->where(ActivityCollectRelation::COLUMN_CREATE_TIME, $time_opt, $time_stamp)
                ->order(ActivityCollectRelation::COLUMN_CREATE_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        } else if ($page_id > 0 && $page_id < Config::PAGE_ID_ACTIVITY_ONE_KIND){
            // 某一类活动查询
            $type = $request->get(Config::PARAM_KEY_TYPE);
            $activities = Db::table(Activity::TABLE_NAME)
                ->where(Activity::COLUMN_TYPE, $type)
                ->where(Activity::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        } else {
            switch ($page_id) {
                case Config::PAGE_ID_ACTIVITY_ALL:
                    // 全部活动查询
                    $activities = Db::table(Activity::TABLE_NAME)
                        ->where(Activity::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                        ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                        ->limit($request_count)
                        ->select();
                    break;
                case Config::PAGE_ID_ACTIVITY_SELF:
                    // 用户自己的活动查询
                    $activities = Db::table(Activity::TABLE_NAME)
                        ->where(Activity::COLUMN_PUBLISHER_UID, $uid)
                        ->where(Activity::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                        ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                        ->limit($request_count)
                        ->select();
                    break;
                case Config::PAGE_ID_ACTIVITY_OTHER_USER:
                    // 其他用户的活动查询
                    $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
                    $activities = Db::table(Activity::TABLE_NAME)
                        ->where(Activity::COLUMN_PUBLISHER_UID, $other_uid)
                        ->where(Activity::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                        ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                        ->limit($request_count)
                        ->select();
                    break;
                case Config::PAGE_ID_ACTIVITY_SEARCH:
                    // 搜索活动查询
                    $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
                    $field = Activity::COLUMN_TITLE.'|'.Activity::COLUMN_TITLE.'|'.Activity::COLUMN_LOCATION;
                    $condition = '%'.$keyword.'%';
                    $activities = Db::table(Activity::TABLE_NAME)
                        ->where(Activity::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                        ->where($field,Config::WORD_LIKE,  $condition)
                        ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                        ->limit($request_count)
                        ->select();
                    break;
                default:
                    break;
            }
        }
        if ($activities == null) {
            // 非法访问
            return Response::newIllegalInstance();
        }
        if ($activities->isEmpty()){
            // 无数据
            return Response::newEmptyInstance();
        }
        $data = Activities::buildActivityListData($activities, $uid);
        return Response::newSuccessInstance($data);
    }

    // 单个活动数据获取
    public function fetchDetail() {
        $request = Request::instance();
        $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
        $curUid = $request->get(Config::PARAM_KEY_UID);
        $activity = Activity::get($activity_id);
        if ($activity == null) {
            return Response::newNoDataInstance();
        }
        $temp = new ActivityResponseBean();
        $temp->id = $activity->id;
        $temp->type = $activity->type;
        $temp->title = $activity->title;
        $temp->content = $activity->content;
        $temp->host = $activity->host;
        $temp->time = $activity->time;
        $temp->address = $activity->address;
        $temp->remark = $activity->remark;
        $temp->location = $activity->location;
        $temp->publishTime = $activity->publish_time;
        $temp->collectCount = self::getCollectCount($activity->id);
        $temp->commentCount = self::getCommentCount($activity->id);
        $temp->additionCount = self::getAdditionCount($activity->id);
        $topic_name = self::getRelatedTopicName($activity->related_topic_id);
        if ($topic_name != null) {
            $temp->topicId = $activity->related_topic_id;
            $temp->topic = $topic_name;
        } else {
            $temp->topicId = -1;
        }
        $temp->pictureList = self::getPictureList($activity->id);
        $temp->hasCollect = self::hasCollected($activity->id, $curUid);
        // 获取用户的基本数据
        $user = User::get($activity->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        return Response::newSuccessInstance($temp);
    }

    private static function buildActivityListData($activities, $curUid) {
        // 组装数据返回
        $result = array();
        foreach ($activities as $key => $activity) {
            $temp = new ActivityResponseBean();
            $temp->id = $activity['id'];
            $temp->type = $activity['type'];
            $temp->title = $activity['title'];
            $temp->content = $activity['content'];
            $temp->host = $activity['host'];
            $temp->time = $activity['time'];
            $temp->address = $activity['address'];
            $temp->remark = $activity['remark'];
            $temp->location = $activity['location'];
            $temp->publishTime = $activity['publish_time'];
            $result[$key] = $temp;
            // 获取收藏数量
            $temp->collectCount = self::getCollectCount($activity['id']);
            // 获取评论数量
            $temp->commentCount = self::getCommentCount($activity['id']);
            // 获取评论数量
            $temp->additionCount = self::getAdditionCount($activity['id']);
            // 获取话题名称
            $topic_name = self::getRelatedTopicName($activity['related_topic_id']);
            if ($topic_name != null) {
                $temp->topicId = $activity['related_topic_id'];
                $temp->topic = $topic_name;
            } else {
                $temp->topicId = -1;
            }
            // 获取图片资源
            $temp->pictureList = self::getPictureList($activity['id']);
            // 获取请求用户是否参与收藏
            $temp->hasCollect = self::hasCollected($activity['id'], $curUid);

            // 获取用户的基本数据
            $user = User::get($activity['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

    private static function getCollectCount($activity_id) {
        return Db::table(ActivityCollectRelation::TABLE_NAME)
            ->where(ActivityCollectRelation::COLUMN_ACTIVITY_ID, $activity_id)
            ->count('*');
    }

    private static function getCommentCount($activity_id) {
        return Db::table(ActivityComment::TABLE_NAME)
            ->where(ActivityComment::COLUMN_ACTIVITY_ID, $activity_id)
            ->count('*');
    }

    private static function getAdditionCount($activity_id) {
        return Db::table(ActivityAddition::TABLE_NAME)
            ->where(ActivityAddition::COLUMN_ACTIVITY_ID, $activity_id)
            ->count('*');
    }

    private static function hasCollected($activity_id, $curUid) {
        $hasCollected = false;
        if ($curUid != -1) {
            $relation = Db::table(ActivityCollectRelation::TABLE_NAME)
                ->where(ActivityCollectRelation::COLUMN_ACTIVITY_ID, $activity_id)
                ->where(ActivityCollectRelation::COLUMN_COLLECTOR_UID, $curUid)
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

    private static function getPictureList($activity_id) {
        $result = array();
        $relation = ActivityPictureRelation::where(ActivityPictureRelation::COLUMN_ACTIVITY_ID, $activity_id)->select();
        foreach ($relation as $key => $value) {
            $result[$key] = $value->url;
        }
        return $result;
    }
}
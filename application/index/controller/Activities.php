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
use app\index\response\CreateActivityResultResponseBean;
use app\index\response\Response;
use think\Db;
use think\migration\command\seed\Create;
use think\Request;

class Activities
{
    public function index() {
        $request = Request::instance();
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $result = strcmp(Config::DEFAULT_TIME_STAMP, $time_stamp);
        return $result;
    }

    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $pull_type = $request->get(Config::PARAM_KEY_PULL_TYPE);
        $time_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
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
                    $offset = $request->get(Config::PARAM_KEY_OFFSET);
                    $activities = self::search($keyword, $offset, $request_count);
                    if ($activities->isEmpty()) {
                        return Response::newNoSearchResult();
                    }
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
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($time_stamp, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = Activities::buildActivityListData($activities, $uid);
        return Response::newSuccessInstance($data);
    }

    // 单个活动数据获取
    public function fetchDetail() {
        $request = Request::instance();
        $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $activity = Activity::get($activity_id);
        if ($activity == null) {
            return Response::newNoDataInstance();
        }
        $data = self::buildSingleActivityData($activity, $uid);
        return Response::newSuccessInstance($data);
    }

    public function create() {
        $request = Request::instance();
        $activity_json = $request->post(Config::PARAM_KEY_ACTIVITY);
        $topic_json = $request->post(Config::PARAM_KEY_TOPIC);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($activity_json == null) {
            return Response::newIllegalInstance();
        }
        $topic = null;
        $activity = null;
        $has_topic_cover = false;
        $images = array();
        $topic_source = $topic_json != null ? json_decode($topic_json) : null;
        if ($topic_source != null) {
            // 创建活动并创建话题
            if (Topics::checkNameExists($topic_source->name)) {
                return Response::newTopicNameExistsInstance();
            }
            if ($topic_source->cover != null) {
                // 存在话题封面
                $has_topic_cover = true;
                $file = $files[0];
                $cover = Upload::uploadImage($file);
                if ($cover == null) {
                    return Response::newErrorInstance(Config::STATUS_CREATE_ACTIVITY_FAIL);
                }
                $topic_source->cover = $cover;
            }
        }
        if ($files) {
            $i = $has_topic_cover ? 1 : 0;
            foreach ($files as $key => $file) {
                if ($key < $i) {
                    continue;
                }
                $imageUrl = Upload::uploadImage($file);
                if ($imageUrl == null) {
                    return Response::newErrorInstance(Config::STATUS_CREATE_ACTIVITY_FAIL);
                }
                $images[] = $imageUrl;
            }
        }
        // 创建话题
        if ($topic_source != null) {
            $topic = Topics::createTopic($topic_source);
        }
        // 创建活动
        $activity_source = json_decode($activity_json);
        $activity = new Activity();
        $activity->type = $activity_source->type;
        $activity->title = $activity_source->title;
        $activity->content = $activity_source->content;
        $activity->host = $activity_source->host;
        $activity->time = $activity_source->time;
        $activity->address = $activity_source->address;
        $activity->remark = $activity_source->remark;
        if ($topic != null) {
            $activity->related_topic_id = $topic->id;
        } else if ($activity_source->topicId != null){
            $activity->related_topic_id = $activity_source->topicId;
        }
        $activity->location = $activity_source->location;
        $activity->publisher_uid = $activity_source->uid;
        $activity->save();
        // 保存活动图片
        foreach ($images as $key => $image) {
            $relation = new ActivityPictureRelation();
            $relation->activity_id = $activity->id;
            $relation->url = $image;
            $relation->order_number = $key;
            $relation->save();
        }
        $data = new CreateActivityResultResponseBean();
        $data->activity = self::buildSingleActivityData(Activity::get($activity->id), $activity->publisher_uid);
        $data->topic = $topic;
        return Response::newSuccessInstance($data);
    }

    private static function buildActivityListData($activities, $uid) {
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
            $temp->hasCollect = self::hasCollected($activity['id'], $uid);

            // 获取用户的基本数据
            $user = User::get($activity['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

    private static function buildSingleActivityData($activity, $uid) {
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
        $temp->hasCollect = self::hasCollected($activity->id, $uid);
        // 获取用户的基本数据
        $user = User::get($activity->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        return $temp;
    }

    public static function search($keyword, $offset, $request_count) {
        $field = Activity::COLUMN_TITLE.'|'.Activity::COLUMN_TITLE.'|'.Activity::COLUMN_LOCATION;
        $condition = "%$keyword%";
        $activities = Db::table(Activity::TABLE_NAME)
            ->where($field,Config::WORD_LIKE,  $condition)
            ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->limit($offset, $request_count)
            ->select();
        return $activities;
    }

    public static function searchHot($request_count) {
        $hot_activities = ActivityComment::field('activity_id as id, count(activity_id) as commentCount')
            ->group(ActivityComment::COLUMN_ACTIVITY_ID)
            ->order('commentCount', Config::WORD_DESC)
            ->limit($request_count)
            ->select();
        foreach ($hot_activities as $activity) {
            $activity['title'] = self::getActivityTitle($activity['id']);
        }
        return $hot_activities;
    }

    public static function searchSimple($keyword, $request_count) {
        $field = Activity::COLUMN_TITLE;
        $condition = "%$keyword%";
        $activities = Activity::where($field,Config::WORD_LIKE,  $condition)
            ->order(Activity::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->field(Activity::COLUMN_ID.','.Activity::COLUMN_TITLE)
            ->limit($request_count)
            ->select();
        foreach ($activities as $activity) {
            $activity['commentCount'] = self::getCommentCount($activity['id']);
        }
    }

    public static function searchAndBuildSimpleList($keyword, $offset, $request_count, $uid) {
        $activities = self::search($keyword, $offset, $request_count);
        return self::buildActivityListData($activities, $uid);
    }

    private static function getActivityTitle($activity_id) {
        return Activity::get($activity_id)->title;
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
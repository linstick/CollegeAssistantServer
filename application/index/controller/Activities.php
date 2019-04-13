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
        $activities = Db::table(Activity::TABLE_NAME)
            ->where('title|content','like',  '%编号：1%')
            ->where('publish_time', '>', '1990-10-10 00:00')
            ->order('publish_time', 'desc')
            ->limit(20)
            ->select();
        return $activities;
    }


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
        $response = new Response();
        if ($activities == null) {
            // 非法访问
            $response->code = Config::CODE_ILLEGAL_ACCESS;
            $response->status = Config::STATUS_ERROR_ILLEGAL_ACCESS;
            return $response;
        }
        if ($activities->isEmpty()){
            // 无数据
            $response->code = Config::CODE_OK_BUT_EMPTY;
            $response->status = Config::STATUS_OK_BUT_EMPTY;
            return $response;
        }
        $response->code = Config::CODE_OK;
        $response->status = Config::STATUS_OK;
        $response->data = Activities::buildActivitySimpleData($activities, $uid);
        return $response;
    }

    private function buildActivitySimpleData($activities, $curUid) {
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
            $temp->collectCount = Db::table(ActivityCollectRelation::TABLE_NAME)
                ->where(ActivityCollectRelation::COLUMN_ACTIVITY_ID, $activity['id'])
                ->count('*');
            // 获取评论数量
            $temp->commentCount = Db::table(ActivityComment::TABLE_NAME)
                ->where(ActivityComment::COLUMN_ACTIVITY_ID, $activity['id'])
                ->count('*');
            // 获取评论数量
            $temp->additionCount = Db::table(ActivityAddition::TABLE_NAME)
                ->where(ActivityAddition::COLUMN_ACTIVITY_ID, $activity['id'])
                ->count('*');
            // 获取话题名称
            $temp->topicId = -1;
            if ($activity['related_topic_id'] != null) {
                $topic = Db::table(Topic::TABLE_NAME)
                    ->where(Topic::COLUMN_ID, $activity['related_topic_id'])
                    ->column('name');
                if (count($topic)) {
                    $temp->topicId = $activity['related_topic_id'];
                    $temp->topic = $topic[0];
                }
            }
            // 获取图片资源
            $temp->pictureList = Db::table(ActivityPictureRelation::TABLE_NAME)
                ->where(ActivityPictureRelation::COLUMN_ACTIVITY_ID, $activity['id'])
                ->column(ActivityPictureRelation::COLUMN_URL);
            // 获取请求用户是否参与收藏
            $temp->hasCollect = false;
            if ($curUid != -1) {
                $relation = Db::table(ActivityCollectRelation::TABLE_NAME)
                    ->where(ActivityCollectRelation::COLUMN_ACTIVITY_ID, $activity['id'])
                    ->where(ActivityCollectRelation::COLUMN_COLLECTOR_UID, $curUid)
                    ->find();
                if (count($relation) > 0) {
                    $temp->hasCollect = true;
                }
            }
            // 获取用户的基本数据
            $user = User::get($activity['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;

        }
        return $result;
    }
}
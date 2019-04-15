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
use app\index\model\TopicVisitRelation;
use app\index\model\User;
use app\index\response\ActivityResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\Response;
use app\index\response\TopicResponseBean;
use think\Db;
use think\Paginator;
use think\Request;

class Topics
{
    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $time_opt = $request->get(Config::PARAM_KEY_PULL_TYPE) == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $topic = null;
        switch ($page_id) {
            case Config::PAGE_ID_TOPIC_ALL:
                // 全部话题查询
                $topic =  Topic::where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_SELF:
                // 用户自己的话题查询
                $topic =  Topic::where(Topic::COLUMN_PUBLISHER_UID, $uid)
                    ->where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_OTHER_USER:
                // 其他用户的话题查询
                $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
                $topic = Topic::where(Topic::COLUMN_PUBLISHER_UID, $other_uid)
                    ->where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_SEARCH:
                // 搜索动态查询
                $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
                $field = Topic::COLUMN_NAME.'|'.Topic::COLUMN_DESCRIPTION;
                $condition = '%'.$keyword.'%';
                $topic = Topic::where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->where($field,Config::WORD_LIKE,  $condition)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            default:
                break;
        }
        if ($topic == null) {
            return Response::newIllegalInstance();
        }
        if ($topic->isEmpty()){
           return Response::newEmptyInstance();
        }
        $data = self::buildTopicListData($topic, $uid);
        return Response::newSuccessInstance($data);
    }

    // 单个活动数据获取
    public function fetchDetail() {
        $request = Request::instance();
        $topic_id = $request->get(Config::PARAM_KEY_TOPIC_ID);
        $topic = Topic::get($topic_id);
        if ($topic == null) {
            $response = new Response();
            $response->code = Config::CODE_NO_DATA;
            $response->status = Config::STATUS_NO_DATA;
            return $response;
        }
        $temp = new TopicResponseBean();
        $temp->id = $topic->id;
        $temp->name = $topic->name;
        $temp->coverUrl = $topic->cover;
        $temp->introduction = $topic->description;
        $temp->publishTime = $topic->publish_time;
        $temp->joinCount = self::getJoinCount($topic->id);
        $temp->visitCount = self::getVisitCount($topic->id);
        $temp->discoverList = self::getDiscoverList($topic->id);
        // 获取用户的基本数据
        $user = User::get($topic->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        return Response::newSuccessInstance($temp);
    }

    public function fetchSimpleList() {
        $request = Request::instance();
        $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        if ($keyword == null) {
            return Response::newNoDataInstance();
        }
        $topics = Topic::where(Topic::COLUMN_NAME, Config::WORD_LIKE, $keyword.'%')
            ->limit($request_count)
            ->field(Topic::COLUMN_ID.','.Topic::COLUMN_NAME)
            ->select();
        if ($topics == null) {
            return Response::newNoDataInstance();
        }
        self::complegeSimpleListData($topics);
        return Response::newSuccessInstance($topics);
    }

    /**
     * 判断话题是否存在
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkName() {
        $request = Request::instance();
        $topic_name = $request->get(Config::PARAM_KEY_TOPIC_NAME);
        if ($topic_name == null) {
            return Response::newIllegalInstance();
        }
        $topic = Topic::where(Topic::COLUMN_NAME, $topic_name)->find();
        if ($topic == null) {
            return Response::newSuccessInstance(null);
        }
        return Response::newTopicNameExistsInstance();
    }

    private static function buildTopicListData($topics, $curUid) {
        // 组装数据返回
        $result = array();
        foreach ($topics as $key => $topic) {
            $temp = new TopicResponseBean();
            $temp->id = $topic['id'];
            $temp->name = $topic['name'];
            $temp->coverUrl = $topic['cover'];
            $temp->introduction = $topic['description'];
            $temp->publishTime = $topic['publish_time'];
            $result[$key] = $temp;
            // 获取收藏数量
            $temp->joinCount = self::getJoinCount($topic['id']);
            // 获取评论数量
            $temp->visitCount = self::getVisitCount($topic['id']);
            // 获取图片资源
            $temp->discoverList = self::getDiscoverList($topic['id']);

            // 获取用户的基本数据
            $user = User::get($topic['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

    private static function complegeSimpleListData($topics) {
        $result = array();
        foreach ($topics as $key => $topic) {
            $temp = new TopicResponseBean();
            $topic['joinCount'] = self::getJoinCount($topic['id']);
            $topic['visitCount'] = self::getVisitCount($topic['id']);
            $result[$key] = $temp;
        }
        return $result;
    }

    private static function getJoinCount($topic_id) {
        return Db::table(TopicJoinRelation::TABLE_NAME)
            ->where(TopicJoinRelation::COLUMN_TOPIC_ID, $topic_id)
            ->count('*');
    }

    private static function getVisitCount($topic_id) {
        return Db::table(TopicVisitRelation::TABLE_NAME)
            ->where(TopicVisitRelation::COLUMN_TOPIC_ID, $topic_id)
            ->count('*');
    }

    private static function getDiscoverList($topic_id) {
        $view_join_condition = TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_DISCOVER_ID.'='.Discover::TABLE_NAME.'.'.Discover::COLUMN_ID;
        return Db::view(TopicJoinRelation::TABLE_NAME, TopicJoinRelation::COLUMN_DISCOVER_ID.','.TopicJoinRelation::COLUMN_PUBLISH_TIME)
            ->view(Discover::TABLE_NAME, Discover::COLUMN_CONTENT, $view_join_condition)
            ->where(TopicJoinRelation::COLUMN_TOPIC_ID, $topic_id)
            ->order(TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->limit(5)
            ->select();
    }
}
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
        $pull_type = $request->get(Config::PARAM_KEY_PULL_TYPE);
        $time_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $topics = null;
        switch ($page_id) {
            case Config::PAGE_ID_TOPIC_ALL:
                // 全部话题查询
                $topics =  Topic::where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_SELF:
                // 用户自己的话题查询
                $topics =  Topic::where(Topic::COLUMN_PUBLISHER_UID, $uid)
                    ->where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_OTHER_USER:
                // 其他用户的话题查询
                $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
                $topics = Topic::where(Topic::COLUMN_PUBLISHER_UID, $other_uid)
                    ->where(Topic::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_TOPIC_SEARCH:
                // 搜索动态查询
                $offset = $request->get(Config::PARAM_KEY_OFFSET);
                $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
                $topics = self::search($keyword, $offset, $request_count);
                if ($topics->isEmpty()) {
                    return Response::newNoSearchResult();
                }
                break;
            default:
                break;
        }
        if ($topics == null) {
            return Response::newIllegalInstance();
        }
        if ($topics->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($time_stamp, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = self::buildTopicListData($topics);
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
        $temp->discoverList = self::getNormalDiscoverList($topic->id);
        // 获取用户的基本数据
        $user = User::get($topic->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        return Response::newSuccessInstance($temp);
    }

    public function fetchHotSimpleList() {
        $request = Request::instance();
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $request_count = $request_count == null ? 10 : $request_count;
        $hot_topics = TopicJoinRelation::field('topic_id as id, count(topic_id) as joinCount')
            ->group(TopicJoinRelation::COLUMN_TOPIC_ID)
            ->order('joinCount', Config::WORD_DESC)
            ->limit($request_count)
            ->select();
        if (count($hot_topics) == 0) {
            return Response::newEmptyInstance();
        }
        self::buildHotSimpleListData($hot_topics);
        return Response::newSuccessInstance($hot_topics);
    }

    public function fetchSimpleList() {
        $request = Request::instance();
        $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $page = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        if ($keyword == null) {
            return Response::newIllegalInstance();
        }
        $exist_topic = Topic::where(Topic::COLUMN_NAME, $keyword)
            ->field(Topic::COLUMN_ID.','.Topic::COLUMN_NAME)->
            find();
        $topics = Topic::where(Topic::COLUMN_NAME, Config::WORD_LIKE, $keyword.'%')
            ->limit($page, $request_count)
            ->field(Topic::COLUMN_ID.','.Topic::COLUMN_NAME)
            ->select()
            ->toArray();
        if ($exist_topic != null) {
            array_unshift($topics, $exist_topic);
        }
        if (count($topics) == 0) {
            return Response::newNoSearchResult();
        }
        self::completeSimpleListData($topics);
        return Response::newSuccessInstance($topics);
    }

    /**
     * 判断话题是否存在
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function checkNameExist() {
        $request = Request::instance();
        $topic_name = $request->get(Config::PARAM_KEY_TOPIC_NAME);
        if ($topic_name == null) {
            return Response::newIllegalInstance();
        }
        $topic = Topic::where(Topic::COLUMN_NAME, $topic_name)->find();
        return Response::newSuccessInstance($topic == null ? false : true);
    }

    public static function search($keyword, $offset, $request_count) {
        $field = Topic::COLUMN_NAME.'|'.Topic::COLUMN_DESCRIPTION;
        $condition = "%$keyword%";
        $topics = Topic::where($field,Config::WORD_LIKE,  $condition)
            ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->limit($offset, $request_count)
            ->select();
        return $topics;
    }

    public static function searchHot($request_count) {
        $hot_topics = TopicJoinRelation::field('topic_id as id, count(topic_id) as joinCount')
            ->group(TopicJoinRelation::COLUMN_TOPIC_ID)
            ->order('joinCount', Config::WORD_DESC)
            ->limit($request_count)
            ->select();
        foreach ($hot_topics as $topic) {
            $topic['name'] = self::getTopicName($topic['id']);
        }
        return $hot_topics;
    }

    public static function searchSimple($keyword, $request_count) {
        $field = Topic::COLUMN_NAME;
        $condition = "%$keyword%";
        $topics = Topic::where($field,Config::WORD_LIKE,  $condition)
            ->order(Topic::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->field(Topic::COLUMN_ID.','.Topic::COLUMN_NAME)
            ->limit($request_count)
            ->select();
        foreach ($topics as $topic) {
            $topic['joinCount'] = self::getJoinCount($topic['id']);
        }
        return $topics;
    }

    public static function searchAndBuildSimpleList($keyword, $offset, $request_count) {
        $topics = self::search($keyword, $offset, $request_count);
        return self::buildTopicListData($topics);
    }

    private static function buildTopicListData($topics) {
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
            $temp->discoverList = self::getHotDiscoverList($topic['id']);

            // 获取用户的基本数据
            $user = User::get($topic['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
        }
        return $result;
    }

    private static function buildHotSimpleListData($topics) {
        $result = array();
        foreach ($topics as $key => $topic) {
            $temp = new TopicResponseBean();
            $topic['name'] = self::getTopicName($topic['id']);
            $topic['visitCount'] = self::getVisitCount($topic['id']);
            $result[$key] = $temp;
        }
        return $result;
    }

    private static function completeSimpleListData($topics) {
        foreach ($topics as $key => $topic) {
            $topic['joinCount'] = self::getJoinCount($topic['id']);
            $topic['visitCount'] = self::getVisitCount($topic['id']);
        }
    }

    private static function getTopicName($topic_id) {
        $topic = Topic::get($topic_id);
        return $topic != null ? $topic->name : null;
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

    private static function getNormalDiscoverList($topic_id) {
        $view_join_condition = TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_DISCOVER_ID.'='.Discover::TABLE_NAME.'.'.Discover::COLUMN_ID;
        return Db::view(TopicJoinRelation::TABLE_NAME, TopicJoinRelation::COLUMN_DISCOVER_ID.','.TopicJoinRelation::COLUMN_PUBLISH_TIME)
            ->view(Discover::TABLE_NAME, Discover::COLUMN_CONTENT, $view_join_condition)
            ->where(TopicJoinRelation::COLUMN_TOPIC_ID, $topic_id)
            ->order(TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->limit(5)
            ->select();
    }

    private static function getHotDiscoverList($topic_id) {
        $request_count = 5;
        return Db::table('discover d')
            ->join('discover_comment c', 'd.id=c.discover_id', 'left')
            ->where('d.id', 'in', function ($query) use ($topic_id) {
                $query->table('topic_join_relation')->where('topic_id', $topic_id)->field('discover_id');
            })
            ->field('d.*, count(c.discover_id) as count')
            ->group('c.discover_id')
            ->order('count', 'desc')
            ->limit($request_count)
            ->select();
    }
}
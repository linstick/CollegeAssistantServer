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
use app\index\response\CreateDiscoverResultResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\Response;
use think\Db;
use think\Paginator;
use think\Request;

class Discovers
{
    public function index() {
        $topic_id = 434;
        // 话题相关动态的集合
        return Db::table('discover d')
            ->join('discover_comment c', 'd.id=c.discover_id', 'left')
            ->where('d.id', 'in', function ($query) use ($topic_id) {
                $query->table('topic_join_relation')->where('topic_id', $topic_id)->field('discover_id');
            })
            ->field('d.*, count(c.discover_id) as count')
            ->group('c.discover_id')
            ->order('count', 'desc')
            ->select();
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
        $offset = $request->get(Config::PARAM_KEY_OFFSET);
        $discovers = null;
        switch ($page_id) {
            case Config::PAGE_ID_DISCOVER_ALL:
                // 全部活动查询
                $discovers = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_SELF:
                // 用户自己的活动查询
                $discovers = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISHER_UID, $uid)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_OTHER_USER:
                // 其他用户的活动查询
                $other_uid = $request->get(Config::PARAM_KEY_OTHER_UID);
                $discovers = Db::table(Discover::TABLE_NAME)
                    ->where(Discover::COLUMN_PUBLISHER_UID, $other_uid)
                    ->where(Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_SEARCH:
                // 搜索动态查询
                $offset = $request->get(Config::PARAM_KEY_OFFSET);
                $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
                $discovers = self::search($keyword, $offset, $request_count, $uid);
                if ($discovers->isEmpty()) {
                    return Response::newNoSearchResult();
                }
                break;
            case Config::PAGE_ID_DISCOVER_TOPIC_LASTED:
                // 参与话题的动态数据查询
                $topic_id = $request->get(Config::PARAM_KEY_TOPIC_ID);
                $view_join_condition = TopicJoinRelation::TABLE_NAME.'.'.TopicJoinRelation::COLUMN_DISCOVER_ID.'='.Discover::TABLE_NAME.'.'.Discover::COLUMN_ID;
                $discovers = Db::view(TopicJoinRelation::TABLE_NAME, TopicJoinRelation::COLUMN_DISCOVER_ID)
                    ->view(Discover::TABLE_NAME, '*', $view_join_condition)
                    ->where(TopicJoinRelation::COLUMN_TOPIC_ID, $topic_id)
                    ->where(Discover::TABLE_NAME.'.'.Discover::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                    ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                    ->limit($request_count)
                    ->select();
                break;
            case Config::PAGE_ID_DISCOVER_TOPIC_HOT:
                $topic_id = $request->get(Config::PARAM_KEY_TOPIC_ID);
                $discovers = Db::table('discover d')
                    ->join('discover_comment c', 'd.id=c.discover_id', 'left')
                    ->where('d.id', 'in', function ($query) use ($topic_id) {
                        $query->table('topic_join_relation')->where('topic_id', $topic_id)->field('discover_id');
                    })
                    ->field('d.*, count(c.discover_id) as count')
                    ->group('c.discover_id')
                    ->order('count', 'desc')
                    ->limit($offset, $request_count)
                    ->select();
                break;
            default:
                break;
        }
        if ($discovers == null) {
            return Response::newIllegalInstance();
        }
        if ($discovers->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($time_stamp, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = self::buildDiscoverListData($discovers, $uid);
        return Response::newSuccessInstance($data);
    }

    // 单个活动数据获取
    public function fetchDetail() {
        $request = Request::instance();
        $activity_id = $request->get(Config::PARAM_KEY_DISCOVER_ID);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $discover = Discover::get($activity_id);
        if ($discover == null) {
            return Response::newNoDataInstance();
        }
        $data = self::buildSingleDiscoverData($discover, $uid);
        return Response::newSuccessInstance($data);
    }

    /**
     * 创建动态（可能会同时创建话题）
     * @return Response
     * @throws \think\exception\DbException
     */
    public function create() {
        $request = Request::instance();
        $discover_json = $request->post(Config::PARAM_KEY_DISCOVER);
        $topic_json = $request->post(Config::PARAM_KEY_TOPIC);
        $files = $request->file(Config::PARAM_KEY_IMAGE);
        if ($discover_json == null) {
            return Response::newIllegalInstance();
        }
        $topic = null;
        $discover = null;
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
                    return Response::newErrorInstance(Config::STATUS_CREATE_DISCOVER_FAIL);
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
                    return Response::newErrorInstance(Config::STATUS_CREATE_DISCOVER_FAIL);
                }
                $images[] = $imageUrl;
            }
        }
        // 创建话题
        if ($topic_source != null) {
            $topic = Topics::createTopic($topic_source);
        }
        // 创建活动
        $discover_source = json_decode($discover_json);
        $discover = new Discover();
        $discover->content = $discover_source->content;
        if ($topic != null) {
            $discover->related_topic_id = $topic->id;
        } else if ($discover_source->topicId != null){
            $discover->related_topic_id = $discover_source->topicId;
        }
        $discover->location = $discover_source->location;
        $discover->publisher_uid = $discover_source->uid;
        $discover->save();
        // 保存活动图片
        foreach ($images as $key => $image) {
            $relation = new DiscoverPictureRelation();
            $relation->discover_id = $discover->id;
            $relation->url = $image;
            $relation->order_number = $key;
            $relation->save();
        }
        $data = new CreateDiscoverResultResponseBean();
        $data->discover = self::buildSingleDiscoverData(Discover::get($discover->id), $discover->publisher_uid);
        $data->topic = $topic;
        return Response::newSuccessInstance($data);
    }

    public static function search($keyword, $offset, $request_count) {
        $field = Discover::COLUMN_CONTENT.'|'.Discover::COLUMN_LOCATION;
        $condition = "%$keyword%";
        $discovers = Db::table(Discover::TABLE_NAME)
            ->where($field,Config::WORD_LIKE,  $condition)
            ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->limit($offset, $request_count)
            ->select();
        return $discovers;
    }

    public static function searchHot($request_count) {
        $hot_discovers = DiscoverComment::field('discover_id as id, count(discover_id) as commonCount')
            ->group(DiscoverComment::COLUMN_DISCOVER_ID)
            ->order('commonCount', Config::WORD_DESC)
            ->limit($request_count)
            ->select();
        foreach ($hot_discovers as $discover) {
            $discover['content'] = self::getContent($discover['id']);
        }
        return $hot_discovers;
    }

    public static function searchSimple($keyword, $request_count) {
        $field = Discover::COLUMN_CONTENT;
        $condition = "%$keyword%";
        $discovers = Discover::where($field,Config::WORD_LIKE,  $condition)
            ->order(Discover::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
            ->field(Discover::COLUMN_ID.','.Discover::COLUMN_CONTENT)
            ->limit($request_count)
            ->select();
        foreach ($discovers as $discover) {
            $discover['commentCount'] = self::getCommentCount($discover['id']);
        }
        return $discovers;
    }

    public static function searchAndBuildSimpleList($keyword, $offset, $request_count, $uid) {
        $discovers = self::search($keyword, $offset, $request_count);
        return self::buildDiscoverListData($discovers, $uid);
    }

    private static function buildDiscoverListData($discovers, $uid) {
        // 组装数据返回
        $result = array();
        foreach ($discovers as $key => $discover) {
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
            $temp->hasLike = self::hasLiked($discover['id'], $uid);

            // 获取用户的基本数据
            $user = User::get($discover['publisher_uid']);
            $temp->uid = $user->uid;
            $temp->nickname = $user->nickname;
            $temp->avatarUrl = $user->avatar;
            $temp->college = self::getCollegeName($discover['publisher_uid']);
        }
        return $result;
    }

    private function buildSingleDiscoverData($discover, $uid) {
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
        $temp->hasLike = self::hasLiked($discover->id, $uid);
        // 获取用户的基本数据
        $user = User::get($discover->publisher_uid);
        $temp->uid = $user->uid;
        $temp->nickname = $user->nickname;
        $temp->avatarUrl = $user->avatar;
        $temp->college = self::getCollegeName($discover['publisher_uid']);
        return $temp;
    }

    private static function getContent($discover_id) {
        return Discover::get($discover_id)->content;
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
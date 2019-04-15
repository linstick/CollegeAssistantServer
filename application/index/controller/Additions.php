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
use app\index\response\AdditionResponseBean;
use app\index\response\CommentResponseBean;
use app\index\response\DiscoverResponseBean;
use app\index\response\MessageResponseBean;
use app\index\response\Response;
use think\Db;
use think\Paginator;
use think\Request;

class Additions
{
    // 活动列表数据获取
    public function pull() {
        $request = Request::instance();
        $page_id = $request->get(Config::PARAM_KEY_PAGE_ID);
        $time_opt = $request->get(Config::PARAM_KEY_PULL_TYPE) == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $time_stamp = $request->get(Config::PARAM_KEY_TIME_STAMP);
        $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
        $additions = null;
        if ($page_id == Config::PAGE_ID_ACTIVITY_ADDITION) {
            $additions = Db::table(ActivityAddition::TABLE_NAME)
                ->where(ActivityAddition::COLUMN_ACTIVITY_ID, $activity_id)
                ->where(ActivityAddition::COLUMN_PUBLISH_TIME, $time_opt, $time_stamp)
                ->order(ActivityAddition::COLUMN_PUBLISH_TIME, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        }
        if ($additions == null) {
            return Response::newIllegalInstance();;
        }
        if ($additions->isEmpty()){
            return Response::newEmptyInstance();
        }
        $data = self::buildAdditionListData($additions);
        return Response::newSuccessInstance($data);
    }

    private static function buildAdditionListData($comments) {
        // 组装数据返回
        $result = array();
        foreach ($comments as $key => $comment) {
            $temp = new AdditionResponseBean();
            $temp->id = $comment['id'];
            $temp->activityId = $comment['activity_id'];
            $temp->content = $comment['content'];
            $temp->publishTime = $comment['publish_time'];
            $result[$key] = $temp;
        }
        return $result;
    }

}
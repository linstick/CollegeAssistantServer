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
        $pull_type = $request->get(Config::PARAM_KEY_PULL_TYPE);
        $compare_opt = $pull_type == Config::PULL_TYPE_REFRESH ? '>' : '<';
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $first_or_last_id = $request->get(Config::PARAM_KEY_ADDITION_ID);
        $activity_id = $request->get(Config::PARAM_KEY_ACTIVITY_ID);
        $additions = null;
        if ($page_id == Config::PAGE_ID_ACTIVITY_ADDITION) {
            $additions = Db::table(ActivityAddition::TABLE_NAME)
                ->where(ActivityAddition::COLUMN_ACTIVITY_ID, $activity_id)
                ->where(ActivityAddition::COLUMN_ID, $compare_opt, $first_or_last_id)
                ->order(ActivityAddition::COLUMN_ID, Config::WORD_DESC)
                ->limit($request_count)
                ->select();
        }
        if ($additions == null) {
            return Response::newIllegalInstance();;
        }
        if ($additions->isEmpty()){
            if ($pull_type == Config::PULL_TYPE_REFRESH && strcmp($first_or_last_id, Config::DEFAULT_TIME_STAMP) == 0) {
                // 第一次请求
                return Response::newNoDataInstance();
            }
            // 非第一次请求
            // 无更多数据数据
            return Response::newEmptyInstance();
        }
        $data = self::buildAdditionListData($additions);
        return Response::newSuccessInstance($data);
    }

    /**
     * 添加评论（活动/动态）
     */
    public function add() {
        $request = Request::instance();
        $activity_id =  $request->post(Config::PARAM_KEY_ACTIVITY_ID);
        $text = $request->post(Config::PARAM_KEY_ADDITION);
        if ($activity_id == null || $text == null) {
            return Response::newIllegalInstance();
        }
        $addition = new ActivityAddition();
        $addition->content = $text;
        $addition->activity_id = $activity_id;
        $addition->save();
        $data = self::buildSingleAdditionData(ActivityAddition::get($addition->id));
        return Response::newSuccessInstance($data);
    }

    /**
     * 删除评论（活动/动态）
     */
    public function delete() {
        $request = Request::instance();
        $addition_id = $request->get(Config::PARAM_KEY_ADDITION_ID);
        if ($addition_id == null) {
            return Response::newIllegalInstance();
        }
        $addition = ActivityAddition::get($addition_id);
        if ($addition == null) {
            return Response::newIllegalInstance();
        }
        $addition->delete();
        return Response::newSuccessInstance($addition);
    }

    private static function buildAdditionListData($additions) {
        // 组装数据返回
        $result = array();
        foreach ($additions as $key => $addition) {
            $temp = new AdditionResponseBean();
            $temp->id = $addition['id'];
            $temp->activityId = $addition['activity_id'];
            $temp->content = $addition['content'];
            $temp->publishTime = $addition['publish_time'];
            $result[] = $temp;
        }
        return $result;
    }

    private static function buildSingleAdditionData($addition) {
        $temp = new AdditionResponseBean();
        $temp->id = $addition->id;
        $temp->activityId = $addition->activity_id;
        $temp->content = $addition->content;
        $temp->publishTime = $addition->publish_time;
        return $temp;
    }

}
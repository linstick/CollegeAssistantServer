<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/17/017
 * Time: 13:49
 */

namespace app\index\controller;


use app\index\config\Config;
use app\index\response\CompositeSearchResponseBean;
use app\index\response\Response;
use think\Request;

class Search
{

    /**
     * 联合查询
     * 查询活动/用户/话题/动态
     * 默认最多返回符合条件的四种数据：
     * 1. 相关活动
     * 2. 相关用户
     * 3. 相关话题
     * 4. 相关动态
     */
    public function composite() {
        $request = Request::instance();
        $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
        $uid = $request->get(Config::PARAM_KEY_UID);
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $request_count = max($request_count, 0);
        $request_count = min($request_count, Config::MAX_REQUEST_COUNT);
        if ($keyword == null) {
            return Response::newIllegalInstance();
        }
        $activities = Activities::searchAndBuildSimpleList($keyword, 0, $request_count, $uid);
        $topics = Topics::searchAndBuildSimpleList($keyword, 0, $request_count);
        $users = Users::search($keyword, 0, $request_count);
        $discovers = Discovers::searchAndBuildSimpleList($keyword, 0, $request_count, $uid);
        if (empty($activities) && empty($topics) && empty($users) && empty($discovers)) {
            return Response::newNoSearchResult();
        }
        $data = new CompositeSearchResponseBean();
        $data->activities = $activities;
        $data->topics = $topics;
        $data->users = $users;
        $data->discovers = $discovers;
        return Response::newSuccessInstance($data);
    }

    public function compositeSimple() {
        $request = Request::instance();
        $keyword = $request->get(Config::PARAM_KEY_KEYWORD);
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $request_count = max($request_count, 0);
        $request_count = min($request_count, Config::MAX_REQUEST_COUNT);
        if ($keyword == null) {
            return Response::newIllegalInstance();
        }
        $activities = Activities::searchSimple($keyword, $request_count)->toArray();
        $topics = Topics::searchSimple($keyword, $request_count)->toArray();
        $users = Users::searchSimple($keyword,  $request_count)->toArray();
        $discovers = Discovers::searchSimple($keyword, $request_count)->toArray();
        if (empty($activities) && empty($topics) && empty($users) && empty($discovers)) {
            return Response::newNoSearchResult();
        }
        $data = new CompositeSearchResponseBean();
        $data->activities = $activities;
        $data->topics = $topics;
        $data->users = $users;
        $data->discovers = $discovers;
        return Response::newSuccessInstance($data);
    }

    public function compositeHot() {
        $request = Request::instance();
        $request_count = $request->get(Config::PARAM_KEY_REQUEST_COUNT);
        $request_count = max($request_count, 0);
        $request_count = min($request_count, Config::MAX_REQUEST_COUNT);
        $activities = Activities::searchHot($request_count)->toArray();
        $topics = Topics::searchHot($request_count)->toArray();
        $users = Users::searchHot($request_count)->toArray();
        $discovers = Discovers::searchHot($request_count)->toArray();
        if (empty($activities) && empty($topics) && empty($users) && empty($discovers)) {
            return Response::newNoSearchResult();
        }
        $data = new CompositeSearchResponseBean();
        $data->activities = $activities;
        $data->topics = $topics;
        $data->users = $users;
        $data->discovers = $discovers;
        return Response::newSuccessInstance($data);
    }
}
<?php
namespace app\index\controller;

use app\index\model\Activity;
use app\index\model\ActivityCollectRelation;
use app\index\model\ActivityPictureRelation;
use app\index\model\Discover;
use app\index\model\DiscoverLikeRelation;
use app\index\model\DiscoverPictureRelation;
use app\index\model\TopicVisitRelation;
use app\index\model\User;
use think\Db;

class Index
{
    public function index()
    {
        return User::get(1);
    }
}

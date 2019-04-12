<?php
namespace app\index\controller;

use app\index\model\ActivityPictureRelation;

class Index
{
    public function index()
    {
        $relation = new ActivityPictureRelation();
        // 查询数据集
        $array =  $relation
            ->where('activity_id', '1')
            ->limit(10)
            ->order('order_number', 'asc')
            ->select();
        return $array;
    }
}

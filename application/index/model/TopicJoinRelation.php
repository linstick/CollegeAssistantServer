<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:59
 */

namespace app\index\model;


use think\Model;

class TopicJoinRelation extends Model
{
    const TABLE_NAME = 'topic_join_relation';
    const COLUMN_TOPIC_ID = 'topic_id';
    const COLUMN_DISCOVER_ID = 'discover_id';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
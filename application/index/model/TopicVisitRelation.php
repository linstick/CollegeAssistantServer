<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:59
 */

namespace app\index\model;


use think\Model;

class TopicVisitRelation extends Model
{
    const TABLE_NAME = 'topic_visit_relation';
    const TOPIC_ID = 'topic_id';
    const VISITOR_UID = 'visitor_uid';
}
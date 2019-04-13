<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:57
 */

namespace app\index\model;


use think\Model;

class ActivityCollectRelation extends Model
{
    protected $table = 'activity_collect_relation';
    const TABLE_NAME = 'activity_collect_relation';
    const COLUMN_ACTIVITY_ID = 'activity_id';
    const COLUMN_COLLECTOR_UID = 'collector_uid';
    const COLUMN_CREATE_TIME = 'create_time';
}
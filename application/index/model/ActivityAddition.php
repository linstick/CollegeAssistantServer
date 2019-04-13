<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/13/013
 * Time: 11:07
 */

namespace app\index\model;


use think\Model;

class ActivityAddition extends Model
{
    const TABLE_NAME = 'activity_addition';
    const COLUMN_ID = 'id';
    const COLUMN_CONTENT = 'content';
    const COLUMN_ACTIVITY_ID = 'activity_id';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
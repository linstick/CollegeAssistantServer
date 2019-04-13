<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:56
 */

namespace app\index\model;


use think\Model;

class ActivityComment extends Model
{
    const TABLE_NAME = 'activity_comment';
    const COLUMN_ID = 'id';
    const COLUMN_CONTENT = 'content';
    const COLUMN_ACTIVITY_ID = 'activity_id';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
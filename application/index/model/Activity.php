<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:56
 */

namespace app\index\model;


use think\Model;

class Activity extends Model
{
    const TABLE_NAME = 'activity';

    const COLUMN_ID = 'id';
    const COLUMN_TYPE = 'type';
    const COLUMN_TITLE = 'title';
    const COLUMN_CONTENT = 'content';
    const COLUMN_HOST = 'host';
    const COLUMN_TIME = 'time';
    const COLUMN_ADDRESS = 'address';
    const COLUMN_REMARK = 'remark';
    const COLUMN_LOCATION = 'location';
    const COLUMN_RELATED_TOPIC_ID = 'related_topic_id';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
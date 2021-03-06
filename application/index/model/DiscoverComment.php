<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:58
 */

namespace app\index\model;


use think\Model;

class DiscoverComment extends Model
{
    const TABLE_NAME = 'discover_comment';
    const COLUMN_ID = 'id';
    const COLUMN_CONTENT = 'content';
    const COLUMN_DISCOVER_ID = 'discover_id';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/5/9/009
 * Time: 14:13
 */

namespace app\index\model;


use think\Model;

class Feedback extends Model
{
    const TABLE_NAME = 'feedback';

    const COLUMN_ID = 'id';
    const COLUMN_TYPE = 'type';
    const COLUMN_DESCRIPTION = 'description';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';
}
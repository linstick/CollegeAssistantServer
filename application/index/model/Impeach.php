<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/5/9/009
 * Time: 14:13
 */

namespace app\index\model;


use think\Model;

class Impeach extends Model
{
    const TABLE_NAME = 'impeach';

    const COLUMN_ID = 'id';
    const COLUMN_REASON_TYPE = 'reason_type';
    const COLUMN_DESCRIPTION = 'description';
    const COLUMN_TARGET_TYPE = 'target_type';
    const COLUMN_TARGET_ID = 'target_id';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';

}
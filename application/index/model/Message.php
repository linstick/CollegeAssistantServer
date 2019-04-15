<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/13/013
 * Time: 0:45
 */

namespace app\index\model;


use app\index\config\Config;
use think\Model;

class Message extends Model
{
    const TABLE_NAME = 'message';
    const COLUMN_ID = 'id';
    const COLUMN_TYPE = 'type';
    const COLUMN_CONTENT = 'content';
    const COLUMN_TARGET_ID = 'target_id';
    const COLUMN_TARGET_TITLE = 'target_title';
    const COLUMN_TARGET_CONTENT = 'target_content';
    const COLUMN_TARGET_COVER = 'target_cover';
    const COLUMN_RECEIVER_UID = 'receiver_uid';
    const COLUMN_CREATOR_UID = 'creator_uid';
    const COLUMN_CREATE_TIME = 'create_time';

    public function getTargetCoverAttr($value) {
        if ($value == null) {
            return null;
        }
        return Config::IMAGE_PREFIX_URL.$value;
    }
}
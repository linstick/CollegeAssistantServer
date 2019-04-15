<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:57
 */

namespace app\index\model;


use app\index\config\Config;
use think\Model;

class Topic extends Model
{
    const TABLE_NAME = 'topic';
    const COLUMN_ID = 'id';
    const COLUMN_NAME = 'name';
    const COLUMN_DESCRIPTION = 'description';
    const COLUMN_COVER = 'cover';
    const COLUMN_PUBLISHER_UID = 'publisher_uid';
    const COLUMN_PUBLISH_TIME = 'publish_time';

    public function getCoverAttr($value) {
        if ($value == null) {
            return null;
        }
        return Config::IMAGE_PREFIX_URL.$value;
    }
}
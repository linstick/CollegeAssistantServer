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

class ActivityPictureRelation extends Model
{
    const TABLE_NAME = 'activity_picture_relation';
    const COLUMN_ACTIVITY_ID = 'activity_id';
    const COLUMN_URL = 'url';
    const COLUMN_ORDER_NUMBER = 'order_number';

    public function getUrlAttr($value) {
        if ($value == null) {
            return null;
        }
        return Config::IMAGE_PREFIX_URL.$value;
    }
}
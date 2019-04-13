<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/4/12/012
 * Time: 22:58
 */

namespace app\index\model;


use think\Model;

class DiscoverPictureRelation extends Model
{
    const TABLE_NAME = 'discover_picture_relation';
    const COLUMN_ACTIVITY_ID = 'discover_id';
    const COLUMN_URL = 'url';
    const COLUMN_ORDER_NUMBER = 'order_number';
}
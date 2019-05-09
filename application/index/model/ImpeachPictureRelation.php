<?php
/**
 * Created by PhpStorm.
 * User: luoruiyong
 * Date: 2019/5/9/009
 * Time: 14:16
 */

namespace app\index\model;


use think\Model;

class ImpeachPictureRelation extends Model
{
    const TABLE_NAME = 'impeach_picture_relation';

    const COLUMN_IMPEACH_ID = 'impeach_id';
    const COLUMN_URL = 'url';
    const COLUMN_ORDER_NUMBER = 'order_number';
}